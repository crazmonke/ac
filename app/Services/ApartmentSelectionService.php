<?php

namespace App\Services;

use App\Models\Apartment;
use App\Models\ApartmentAlias;
use App\Models\ApartmentMatchReview;
use App\Models\ResidenceBuilding;
use App\Models\ResidenceComplex;
use App\Models\ResidenceMergeCandidate;
use App\Models\ResidenceUnit;
use App\Models\ResidentVerificationRequest;
use App\Models\User;
use App\Models\UserResidence;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class ApartmentSelectionService
{
    public function __construct(
        private readonly GoogleLocationVerificationService $locationVerificationService,
        private readonly ResidenceNamingService $residenceNamingService,
        private readonly OperationalMetricsService $operationalMetricsService
    )
    {
    }

    public function search(string $query, int $limit = 8): Collection
    {
        $keyword = trim($query);
        $normalizedKeyword = $this->normalizeText($keyword);
        $isAddressQuery = $this->looksLikeAddressQuery($keyword);
        $searchTerms = $this->buildNameSearchTerms($keyword);

        if ($keyword === '') {
            return collect();
        }

        $candidateLimit = max($limit, min(80, $limit * 20));

        $apartments = Apartment::query()
            ->where(function (Builder $builder) {
                $builder->where('is_active', true)
                    ->orWhereNull('is_active');
            })
            ->where(function (Builder $builder) use ($keyword, $normalizedKeyword, $isAddressQuery, $searchTerms) {
                $builder->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('normalized_name', 'like', '%' . $normalizedKeyword . '%')
                    ->orWhereHas('aliases', function (Builder $aliasQuery) use ($keyword, $normalizedKeyword) {
                        $aliasQuery->where('alias', 'like', '%' . $keyword . '%')
                            ->orWhere('normalized_alias', 'like', '%' . $normalizedKeyword . '%');
                    });

                if (! empty($searchTerms)) {
                    $builder->orWhere(function (Builder $termBuilder) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $normalizedTerm = $this->normalizeText($term);

                            $termBuilder->orWhere(function (Builder $matchBuilder) use ($term, $normalizedTerm) {
                                $matchBuilder->where('name', 'like', '%' . $term . '%')
                                    ->orWhere('normalized_name', 'like', '%' . $normalizedTerm . '%')
                                    ->orWhereHas('aliases', function (Builder $aliasQuery) use ($term, $normalizedTerm) {
                                        $aliasQuery->where('alias', 'like', '%' . $term . '%')
                                            ->orWhere('normalized_alias', 'like', '%' . $normalizedTerm . '%');
                                    });
                            });
                        }
                    });
                }

                if ($isAddressQuery) {
                    $builder->orWhere('road_address', 'like', '%' . $keyword . '%')
                        ->orWhere('sigungu', 'like', '%' . $keyword . '%')
                        ->orWhere('eupmyeondong', 'like', '%' . $keyword . '%');
                }
            })
            ->orderBy('name')
            ->limit($candidateLimit)
            ->get()
            ->sortByDesc(function (Apartment $apartment) use ($keyword, $normalizedKeyword, $searchTerms) {
                return $this->scoreApartmentMatch($apartment, $keyword, $normalizedKeyword, $searchTerms);
            })
            ->take($limit)
            ->values();

        $apartmentRows = $apartments->map(function (Apartment $apartment) {
            $complex = $this->findOrCreateComplexFromApartment($apartment);
            $building = ResidenceBuilding::query()
                ->where('complex_id', $complex->id)
                ->orderBy('id')
                ->first();

            return [
                'id' => (int) $apartment->id,
                'complex_id' => (int) $complex->id,
                'building_id' => (int) ($building?->id ?? 0),
                'name' => $apartment->name,
                'label' => $apartment->name . ' · ' . $apartment->sido . ' ' . $apartment->sigungu,
                'region' => trim($apartment->sido . ' ' . $apartment->sigungu . ' ' . $apartment->eupmyeondong),
                'road_address' => $apartment->road_address,
                'housing_type' => 'apartment',
            ];
        })->values();

        if ($apartmentRows->isNotEmpty()) {
            return $apartmentRows;
        }

        $residences = ResidenceBuilding::query()
            ->with('complex')
            ->whereHas('complex', function (Builder $builder) use ($isAddressQuery) {
                $builder->where('status', 'active');

                if (! $isAddressQuery) {
                    $builder->where('housing_type', '!=', 'mixed');
                }
            })
            ->where(function (Builder $builder) use ($keyword, $isAddressQuery, $searchTerms) {
                $builder->where('building_name', 'like', '%' . $keyword . '%')
                    ->orWhereHas('complex', function (Builder $complexQuery) use ($keyword) {
                        $complexQuery->where('official_name', 'like', '%' . $keyword . '%')
                            ->orWhere('alias_name', 'like', '%' . $keyword . '%')
                            ->orWhere('auto_display_name', 'like', '%' . $keyword . '%');
                    });

                if (! empty($searchTerms)) {
                    $builder->orWhere(function (Builder $termBuilder) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $termBuilder->orWhere(function (Builder $matchBuilder) use ($term) {
                                $matchBuilder->where('building_name', 'like', '%' . $term . '%')
                                    ->orWhereHas('complex', function (Builder $complexQuery) use ($term) {
                                        $complexQuery->where('official_name', 'like', '%' . $term . '%')
                                            ->orWhere('alias_name', 'like', '%' . $term . '%')
                                            ->orWhere('auto_display_name', 'like', '%' . $term . '%');
                                    });
                            });
                        }
                    });
                }

                if ($isAddressQuery) {
                    $builder->orWhere('road_address', 'like', '%' . $keyword . '%')
                        ->orWhere('jibun_address', 'like', '%' . $keyword . '%')
                        ->orWhereHas('complex', function (Builder $complexQuery) use ($keyword) {
                            $complexQuery->where('road_address', 'like', '%' . $keyword . '%')
                                ->orWhere('jibun_address', 'like', '%' . $keyword . '%');
                        });
                }
            })
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(function (ResidenceBuilding $building) {
                $complex = $building->complex;
                if (! $complex) {
                    return null;
                }

                $displayName = $complex->displayName();

                return [
                    'id' => (int) ($complex->legacy_apartment_id ?? 0),
                    'complex_id' => (int) $complex->id,
                    'building_id' => (int) $building->id,
                    'name' => $displayName,
                    'label' => $displayName . ' · ' . ($building->road_address ?: $complex->road_address),
                    'region' => trim((string) ($complex->jibun_address ?: $complex->road_address)),
                    'road_address' => (string) ($building->road_address ?: $complex->road_address),
                    'housing_type' => $complex->housing_type,
                ];
            })
            ->filter()
            ->unique(fn (array $row) => mb_strtolower(trim((string) ($row['name'] ?? ''))) . '|' . mb_strtolower(trim((string) ($row['road_address'] ?? ''))))
            ->values();

        if ($residences->isNotEmpty()) {
            $this->operationalMetricsService->log('residence_search_hit', null, null, null, [
                'keyword' => $keyword,
                'count' => $residences->count(),
            ]);

            return $residences;
        }

        $fallbackRows = collect();

        foreach ($this->buildFallbackSearchKeywords($keyword) as $fallbackKeyword) {
            if ($fallbackRows->count() >= $limit) {
                break;
            }

            $rows = $this->searchRoadCandidatesFromGoogle($fallbackKeyword, $limit);

            if ($rows->isEmpty()) {
                $rows = $this->searchRoadCandidatesFromNominatim($fallbackKeyword, $limit);
            }

            if ($rows->isNotEmpty()) {
                $fallbackRows = $fallbackRows->merge($rows);
            }
        }

        if ($fallbackRows->isNotEmpty()) {
            $fallbackRows = $fallbackRows
                ->unique(fn (array $row) => mb_strtolower(trim((string) ($row['name'] ?? ''))) . '|' . mb_strtolower(trim((string) ($row['road_address'] ?? ''))))
                ->values();

            if (! $isAddressQuery) {
                $filteredRows = $fallbackRows
                    ->filter(function (array $row) use ($normalizedKeyword) {
                        $name = $this->normalizeText((string) ($row['name'] ?? ''));
                        $road = $this->normalizeText((string) ($row['road_address'] ?? ''));

                        return $normalizedKeyword !== ''
                            && (str_contains($name, $normalizedKeyword) || str_contains($road, $normalizedKeyword));
                    })
                    ->values();

                if ($filteredRows->isNotEmpty()) {
                    $fallbackRows = $filteredRows;
                }

                $fallbackRows = $fallbackRows
                    ->sortByDesc(function (array $row) use ($normalizedKeyword) {
                        $name = $this->normalizeText((string) ($row['name'] ?? ''));
                        $road = $this->normalizeText((string) ($row['road_address'] ?? ''));
                        $isPlaceholder = str_contains((string) ($row['name'] ?? ''), '공동주택');

                        $score = 0;
                        if ($normalizedKeyword !== '' && str_starts_with($name, $normalizedKeyword)) {
                            $score += 40;
                        }
                        if ($normalizedKeyword !== '' && str_contains($name, $normalizedKeyword)) {
                            $score += 30;
                        }
                        if ($normalizedKeyword !== '' && str_contains($road, $normalizedKeyword)) {
                            $score += 10;
                        }
                        if (! $isPlaceholder) {
                            $score += 15;
                        }

                        return $score;
                    })
                    ->values();
            }

            $this->operationalMetricsService->log('residence_search_fallback_hit', null, null, null, [
                'keyword' => $keyword,
                'count' => $fallbackRows->count(),
            ]);
        }

        return $fallbackRows;
    }

    public function applySelection(
        User $user,
        ?int $apartmentId,
        string $apartmentQuery,
        string $source,
        ?float $latitude = null,
        ?float $longitude = null,
        ?int $residenceBuildingId = null,
        ?string $unitDong = null,
        ?string $unitHo = null
    ): array
    {
        $query = trim($apartmentQuery);
        $selectedApartment = null;
        $selectedComplex = null;
        $selectedBuilding = null;
        $selectedUnit = null;
        $matchReview = null;
        $autoVerified = false;
        $verificationReason = null;

        if ($residenceBuildingId) {
            $selectedBuilding = ResidenceBuilding::query()->with('complex')->findOrFail($residenceBuildingId);
            $selectedComplex = $selectedBuilding->complex;
            $selectedApartment = $selectedComplex?->legacyApartment;
        } elseif ($apartmentId) {
            $selectedApartment = Apartment::query()->findOrFail($apartmentId);
            $selectedComplex = $this->findOrCreateComplexFromApartment($selectedApartment);
            $selectedBuilding = ResidenceBuilding::query()
                ->where('complex_id', $selectedComplex->id)
                ->orderBy('id')
                ->first();
        }

        if ($selectedComplex && $selectedBuilding) {
            $selectedUnit = $this->resolveUnit($selectedBuilding, $unitDong, $unitHo);
            $regionSnapshot = $this->extractResidenceRegionSnapshot($selectedComplex, $selectedBuilding);

            $user->preferred_apartment_id = $selectedApartment?->id;
            $user->preferred_residence_complex_id = $selectedComplex->id;
            $user->preferred_residence_building_id = $selectedBuilding->id;
            $user->preferred_residence_unit_id = $selectedUnit?->id;
            $user->home_sido = $selectedApartment?->sido ?: ($regionSnapshot['sido'] ?: null);
            $user->home_sigungu = $selectedApartment?->sigungu ?: ($regionSnapshot['sigungu'] ?: null);
            $user->home_eupmyeondong = $selectedApartment?->eupmyeondong ?: ($regionSnapshot['eupmyeondong'] ?: null);
            $user->home_apartment_name = $selectedComplex->displayName();
            $user->save();

            UserResidence::query()
                ->where('user_id', $user->id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);

            $userResidence = UserResidence::query()->updateOrCreate([
                'user_id' => $user->id,
                'complex_id' => $selectedComplex->id,
            ], [
                'building_id' => $selectedBuilding->id,
                'unit_id' => $selectedUnit?->id,
                'verification_method' => 'gps',
                'verification_status' => 'pending',
                'is_primary' => true,
            ]);

            if ($latitude !== null && $longitude !== null) {
                $verification = $this->tryAutoApproveResidentByResidence(
                    $user,
                    $selectedComplex,
                    $selectedBuilding,
                    $latitude,
                    $longitude,
                    $source,
                    $selectedApartment
                );
                $autoVerified = (bool) ($verification['approved'] ?? false);
                $verificationReason = (string) ($verification['reason'] ?? 'region_not_matched');

                if ($autoVerified) {
                    $userResidence->fill([
                        'verification_status' => 'verified',
                        'gps_verified_at' => now(),
                        'distance_m' => isset($verification['details']['distance_meters'])
                            ? (int) $verification['details']['distance_meters']
                            : null,
                        'evidence_meta' => $verification['details'] ?? null,
                    ])->save();
                } else {
                    $userResidence->fill([
                        'verification_status' => 'pending',
                        'evidence_meta' => [
                            'latitude' => $latitude,
                            'longitude' => $longitude,
                            'reason' => $verificationReason,
                            'details' => $verification['details'] ?? null,
                        ],
                    ])->save();
                }
            }

            ApartmentMatchReview::query()
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'resolved',
                    'resolved_apartment_id' => $selectedApartment?->id,
                    'resolved_at' => now(),
                ]);

            $this->queueMergeCandidates($selectedComplex);
            $this->operationalMetricsService->log('residence_selected', $user->id, $selectedComplex->id, $selectedBuilding->id, [
                'source' => $source,
                'auto_verified' => $autoVerified,
            ]);
        } elseif ($query !== '') {
            $matchReview = ApartmentMatchReview::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'raw_apartment_name' => $query,
                    'status' => 'pending',
                ],
                [
                    'source' => $source,
                    'raw_region' => null,
                    'suggested_apartment_id' => $this->suggestApartmentId($query),
                    'admin_note' => null,
                ]
            );

            $this->operationalMetricsService->log('residence_selection_pending_review', $user->id, null, null, [
                'query' => $query,
                'source' => $source,
            ]);
        }

        return [
            'selected_apartment' => $selectedApartment,
            'selected_complex' => $selectedComplex,
            'selected_building' => $selectedBuilding,
            'selected_unit' => $selectedUnit,
            'match_review' => $matchReview,
            'auto_verified' => $autoVerified,
            'verification_reason' => $verificationReason,
        ];
    }

    public function tryAutoApproveResidentByLocation(
        User $user,
        Apartment $apartment,
        float $latitude,
        float $longitude,
        string $source = 'unknown'
    ): array {
        $complex = $this->findOrCreateComplexFromApartment($apartment);
        $building = ResidenceBuilding::query()->firstOrCreate([
            'normalized_key' => $this->residenceNamingService->normalize(
                $complex->normalized_key . '|' . $apartment->name . '|' . $apartment->road_address
            ),
        ], [
            'complex_id' => $complex->id,
            'building_name' => $apartment->name,
            'road_address' => $apartment->road_address,
            'jibun_address' => $apartment->jibun_address,
        ]);

        return $this->tryAutoApproveResidentByResidence(
            $user,
            $complex,
            $building,
            $latitude,
            $longitude,
            $source,
            $apartment
        );
    }

    public function tryAutoApproveResidentByResidence(
        User $user,
        ResidenceComplex $complex,
        ResidenceBuilding $building,
        float $latitude,
        float $longitude,
        string $source = 'unknown',
        ?Apartment $legacyApartment = null
    ): array {
        $legacyApartment ??= $complex->legacyApartment;
        $regionSnapshot = $this->extractResidenceRegionSnapshot($complex, $building);

        $verificationSido = (string) ($legacyApartment?->sido ?: ($regionSnapshot['sido'] ?? ''));
        $verificationSigungu = (string) ($legacyApartment?->sigungu ?: ($regionSnapshot['sigungu'] ?? ''));
        $verificationDong = (string) ($legacyApartment?->eupmyeondong ?: ($regionSnapshot['eupmyeondong'] ?? ''));

        $baseDistanceThreshold = match ($complex->housing_type) {
            'apartment' => 300,
            'officetel' => 120,
            'villa' => 100,
            'urban_living' => 100,
            default => 150,
        };

        $configuredDistanceThreshold = (int) config('community.gps_auto_approve.distance_meters', 3000);
        $distanceThreshold = max($baseDistanceThreshold, $configuredDistanceThreshold);

        $targetLatitude = $building->latitude !== null
            ? (float) $building->latitude
            : ($complex->latitude !== null ? (float) $complex->latitude : null);
        $targetLongitude = $building->longitude !== null
            ? (float) $building->longitude
            : ($complex->longitude !== null ? (float) $complex->longitude : null);

        $verification = $this->locationVerificationService->verifyNearResidenceProfile(
            $latitude,
            $longitude,
            $verificationSido,
            $verificationSigungu,
            $verificationDong,
            (string) ($building->road_address ?: $complex->road_address),
            $targetLatitude,
            $targetLongitude,
            $distanceThreshold
        );

        if (($verification['verified'] ?? false) !== true) {
            $this->operationalMetricsService->log('gps_verification_failed', $user->id, $complex->id, $building->id, [
                'reason' => (string) ($verification['reason'] ?? 'region_not_matched'),
                'source' => $source,
            ]);

            return [
                'approved' => false,
                'reason' => (string) ($verification['reason'] ?? 'region_not_matched'),
                'details' => $verification,
            ];
        }

        if ($legacyApartment) {
            UserRole::query()->firstOrCreate([
                'user_id' => $user->id,
                'apartment_id' => $legacyApartment->id,
                'role' => 'resident',
            ], [
                'granted_at' => now(),
                'granted_by' => null,
            ]);

            ResidentVerificationRequest::query()->updateOrCreate([
                'user_id' => $user->id,
                'apartment_id' => $legacyApartment->id,
                'status' => 'approved',
            ], [
                'residence_complex_id' => $complex->id,
                'residence_building_id' => $building->id,
                'verification_method' => 'gps',
                'distance_m' => isset($verification['distance_meters']) ? (int) $verification['distance_meters'] : null,
                'request_note' => 'GPS 기반 자동 인증 승인',
                'admin_note' => 'Google Geocoding 위치 매칭으로 자동 승인됨 (source: '.$source.')',
                'reviewed_by' => null,
                'reviewed_at' => now(),
            ]);

            ResidentVerificationRequest::query()
                ->where('user_id', $user->id)
                ->where('apartment_id', $legacyApartment->id)
                ->where('status', 'pending')
                ->delete();
        }

        $this->operationalMetricsService->log('gps_verification_succeeded', $user->id, $complex->id, $building->id, [
            'reason' => (string) ($verification['reason'] ?? 'matched_by_google_geocode'),
            'source' => $source,
        ]);

        return [
            'approved' => true,
            'reason' => (string) ($verification['reason'] ?? 'matched_by_google_geocode'),
            'details' => $verification,
        ];
    }

    public function suggestApartmentId(string $query): ?int
    {
        return ApartmentAlias::query()
            ->where('normalized_alias', $this->normalizeText($query))
            ->value('apartment_id')
            ?? Apartment::query()
                ->where('normalized_name', $this->normalizeText($query))
                ->value('id');
    }

    public function normalizeText(string $value): string
    {
        $text = trim(mb_strtolower($value));
        $text = preg_replace('/[\s\-\_\(\)\[\]\.,]+/u', '', $text);

        return $text ?? '';
    }

    private function findOrCreateComplexFromApartment(Apartment $apartment): ResidenceComplex
    {
        $normalizedKey = $this->residenceNamingService->normalize(implode('|', [
            (string) $apartment->sido,
            (string) $apartment->sigungu,
            (string) $apartment->eupmyeondong,
            (string) $apartment->road_address,
            (string) $apartment->name,
        ]));

        $name = $this->residenceNamingService->buildDisplayName(
            $apartment->name,
            null,
            $apartment->road_address,
            $apartment->jibun_address,
            'apartment'
        );

        $complex = ResidenceComplex::query()->updateOrCreate([
            'legacy_apartment_id' => $apartment->id,
        ], [
            'housing_type' => 'apartment',
            'official_name' => $apartment->name,
            'alias_name' => null,
            'auto_display_name' => $name['display_name'],
            'display_name_source' => $name['source'],
            'road_address' => $apartment->road_address,
            'jibun_address' => $apartment->jibun_address,
            'normalized_key' => $normalizedKey,
            'status' => ((bool) ($apartment->is_active ?? true)) ? 'active' : 'hidden',
        ]);

        ResidenceBuilding::query()->firstOrCreate([
            'normalized_key' => $this->residenceNamingService->normalize($normalizedKey . '|' . $apartment->name),
        ], [
            'complex_id' => $complex->id,
            'building_name' => $apartment->name,
            'road_address' => $apartment->road_address,
            'jibun_address' => $apartment->jibun_address,
        ]);

        return $complex;
    }

    private function resolveUnit(ResidenceBuilding $building, ?string $unitDong, ?string $unitHo): ?ResidenceUnit
    {
        $dong = trim((string) $unitDong);
        $ho = trim((string) $unitHo);

        if ($dong === '' && $ho === '') {
            return null;
        }

        $normalized = $this->residenceNamingService->normalize($dong . '-' . $ho);
        $label = trim(implode(' ', array_filter([$dong !== '' ? ($dong . '동') : null, $ho !== '' ? ($ho . '호') : null])));

        return ResidenceUnit::query()->updateOrCreate([
            'building_id' => $building->id,
            'normalized_unit_key' => $normalized,
        ], [
            'dong' => $dong !== '' ? $dong : null,
            'ho' => $ho !== '' ? $ho : null,
            'unit_label_generated' => $label !== '' ? $label : '세대 미지정',
        ]);
    }

    private function queueMergeCandidates(ResidenceComplex $complex): void
    {
        $nearby = ResidenceComplex::query()
            ->where('id', '!=', $complex->id)
            ->where('status', 'active')
            ->when($complex->legal_dong_code, function (Builder $builder) use ($complex) {
                $builder->where('legal_dong_code', $complex->legal_dong_code);
            })
            ->limit(20)
            ->get();

        $nameA = $this->residenceNamingService->normalize($complex->displayName());

        foreach ($nearby as $target) {
            $score = 0.0;
            $distance = null;

            if ($complex->latitude !== null && $complex->longitude !== null && $target->latitude !== null && $target->longitude !== null) {
                $distance = $this->haversineMeters(
                    (float) $complex->latitude,
                    (float) $complex->longitude,
                    (float) $target->latitude,
                    (float) $target->longitude
                );

                if ($distance <= 80) {
                    $score += 50;
                } elseif ($distance <= 150) {
                    $score += 30;
                }
            }

            $nameB = $this->residenceNamingService->normalize($target->displayName());
            similar_text($nameA, $nameB, $nameSimilarity);
            $score += $nameSimilarity * 0.5;

            if ($complex->normalized_key === $target->normalized_key) {
                $score += 30;
            }

            if ($score < 60) {
                continue;
            }

            ResidenceMergeCandidate::query()->updateOrCreate([
                'source_complex_id' => min($complex->id, $target->id),
                'target_complex_id' => max($complex->id, $target->id),
            ], [
                'score' => round($score, 2),
                'reason' => [
                    'distance_m' => $distance,
                    'name_similarity' => round($nameSimilarity, 2),
                    'key_match' => $complex->normalized_key === $target->normalized_key,
                ],
                'status' => 'pending',
            ]);
        }
    }

    private function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earthRadius * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function extractResidenceRegionSnapshot(ResidenceComplex $complex, ResidenceBuilding $building): array
    {
        $sourceAddress = trim((string) ($building->road_address ?: $complex->road_address ?: $building->jibun_address ?: $complex->jibun_address));

        if ($sourceAddress === '') {
            return [
                'sido' => null,
                'sigungu' => null,
                'eupmyeondong' => null,
            ];
        }

        $tokens = preg_split('/\s+/u', str_replace(',', ' ', $sourceAddress)) ?: [];
        $tokens = array_values(array_filter(array_map(function ($token) {
            $token = trim((string) $token);

            return $token !== '' ? $token : null;
        }, $tokens)));

        $tokens = array_values(array_filter($tokens, fn ($token) => $token !== '대한민국'));

        $sido = null;
        $cityToken = null;
        $districtToken = null;
        $dong = null;

        foreach ($tokens as $token) {
            if ($sido === null && preg_match('/(도|특별시|광역시|자치시)$/u', $token)) {
                $sido = $token;
                continue;
            }

            if ($cityToken === null && preg_match('/시$/u', $token)) {
                $cityToken = $token;
                continue;
            }

            if ($districtToken === null && preg_match('/(구|군)$/u', $token)) {
                $districtToken = $token;
                continue;
            }

            if ($dong === null && preg_match('/(동|읍|면|가)$/u', $token)) {
                $dong = $token;
            }
        }

        if ($sido === null && $cityToken !== null) {
            $sido = $cityToken;
        }

        $sigungu = null;
        if ($cityToken !== null && $districtToken !== null) {
            $sigungu = trim($cityToken . ' ' . $districtToken);
        } elseif ($districtToken !== null) {
            $sigungu = $districtToken;
        } elseif ($cityToken !== null) {
            $sigungu = $cityToken;
        }

        return [
            'sido' => $sido,
            'sigungu' => $sigungu,
            'eupmyeondong' => $dong,
        ];
    }

    private function searchRoadCandidatesFromGoogle(string $keyword, int $limit): Collection
    {
        $apiKey = trim((string) config('services.google_maps.api_key'));

        if ($apiKey === '') {
            return collect();
        }

        $response = Http::timeout(8)
            ->retry(1, 400, throw: false)
            ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $keyword,
                'language' => 'ko',
                'region' => 'kr',
                'key' => $apiKey,
            ]);

        if (! $response->successful()) {
            return collect();
        }

        $payload = $response->json();

        if (! is_array($payload) || (string) Arr::get($payload, 'status') !== 'OK') {
            return collect();
        }

        $results = collect((array) Arr::get($payload, 'results', []))->take($limit);

        return $results->map(function ($row) {
            $formattedAddress = trim((string) Arr::get($row, 'formatted_address', ''));
            if ($formattedAddress === '') {
                return null;
            }

            $components = collect((array) Arr::get($row, 'address_components', []));
            $find = function (array $types) use ($components): ?string {
                $entry = $components->first(function ($component) use ($types) {
                    $componentTypes = (array) ($component['types'] ?? []);
                    foreach ($types as $type) {
                        if (in_array($type, $componentTypes, true)) {
                            return true;
                        }
                    }

                    return false;
                });

                $name = trim((string) ($entry['long_name'] ?? ''));

                return $name !== '' ? $name : null;
            };

            $road = trim(implode(' ', array_filter([
                $find(['route']),
                $find(['street_number']),
            ])));

            $jibun = trim(implode(' ', array_filter([
                $find(['sublocality_level_1', 'sublocality_level_2', 'sublocality', 'neighborhood']),
                $find(['premise']),
            ])));

            $lat = Arr::get($row, 'geometry.location.lat');
            $lng = Arr::get($row, 'geometry.location.lng');

            $normalizedKey = $this->residenceNamingService->normalize($formattedAddress);
            if ($normalizedKey === '') {
                return null;
            }

            $name = $this->residenceNamingService->buildDisplayName(
                null,
                null,
                $road !== '' ? $road : $formattedAddress,
                $jibun !== '' ? $jibun : null,
                'mixed'
            );

            $complex = ResidenceComplex::query()->firstOrCreate([
                'normalized_key' => $normalizedKey,
            ], [
                'housing_type' => 'mixed',
                'official_name' => null,
                'alias_name' => null,
                'auto_display_name' => $name['display_name'],
                'display_name_source' => $name['source'],
                'road_address' => $road !== '' ? $road : $formattedAddress,
                'jibun_address' => $jibun !== '' ? $jibun : null,
                'legal_dong_code' => null,
                'postal_code' => null,
                'latitude' => is_numeric($lat) ? (float) $lat : null,
                'longitude' => is_numeric($lng) ? (float) $lng : null,
                'status' => 'active',
            ]);

            $buildingKey = $this->residenceNamingService->normalize($complex->normalized_key . '|' . (string) ($road !== '' ? $road : $formattedAddress));

            $building = ResidenceBuilding::query()->firstOrCreate([
                'normalized_key' => $buildingKey,
            ], [
                'complex_id' => $complex->id,
                'building_no' => null,
                'building_name' => null,
                'road_address' => $road !== '' ? $road : $formattedAddress,
                'jibun_address' => $jibun !== '' ? $jibun : null,
                'bld_main_no' => null,
                'bld_sub_no' => null,
                'legal_dong_code' => null,
                'latitude' => is_numeric($lat) ? (float) $lat : null,
                'longitude' => is_numeric($lng) ? (float) $lng : null,
            ]);

            return [
                'id' => (int) ($complex->legacy_apartment_id ?? 0),
                'complex_id' => (int) $complex->id,
                'building_id' => (int) $building->id,
                'name' => $complex->displayName(),
                'label' => $complex->displayName() . ' · ' . ($building->road_address ?: $complex->road_address),
                'region' => trim((string) ($complex->jibun_address ?: $complex->road_address)),
                'road_address' => (string) ($building->road_address ?: $complex->road_address),
                'housing_type' => $complex->housing_type,
            ];
        })->filter()->values();
    }

    private function searchRoadCandidatesFromNominatim(string $keyword, int $limit): Collection
    {
        $response = Http::timeout(8)
            ->retry(1, 400, throw: false)
            ->withHeaders([
                'User-Agent' => 'apaind-residence-search/1.0',
                'Accept-Language' => 'ko-KR,ko;q=0.9,en;q=0.8',
            ])
            ->get('https://nominatim.openstreetmap.org/search', [
                'q' => $keyword,
                'format' => 'jsonv2',
                'addressdetails' => 1,
                'countrycodes' => 'kr',
                'limit' => max(1, min($limit, 10)),
            ]);

        if (! $response->successful()) {
            return collect();
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            return collect();
        }

        return collect($payload)
            ->take($limit)
            ->map(function ($row) {
                if (! is_array($row)) {
                    return null;
                }

                $displayName = trim((string) Arr::get($row, 'display_name', ''));
                if ($displayName === '') {
                    return null;
                }

                $residenceName = $this->extractNominatimResidenceName($row, $displayName);

                $address = Arr::get($row, 'address', []);
                if (! is_array($address)) {
                    $address = [];
                }

                $road = trim((string) implode(' ', array_filter([
                    Arr::get($address, 'road'),
                    Arr::get($address, 'house_number'),
                ])));

                $jibun = trim((string) implode(' ', array_filter([
                    Arr::get($address, 'suburb') ?: Arr::get($address, 'quarter') ?: Arr::get($address, 'neighbourhood'),
                    Arr::get($address, 'city_district') ?: Arr::get($address, 'town') ?: Arr::get($address, 'city'),
                ])));

                $normalizedKey = $this->residenceNamingService->normalize($displayName);
                if ($normalizedKey === '') {
                    return null;
                }

                $name = $this->residenceNamingService->buildDisplayName(
                    $residenceName,
                    null,
                    $road !== '' ? $road : $displayName,
                    $jibun !== '' ? $jibun : null,
                    'mixed'
                );

                $lat = Arr::get($row, 'lat');
                $lng = Arr::get($row, 'lon');

                $complex = ResidenceComplex::query()->updateOrCreate([
                    'normalized_key' => $normalizedKey,
                ], [
                    'housing_type' => 'mixed',
                    'official_name' => $residenceName,
                    'alias_name' => null,
                    'auto_display_name' => $name['display_name'],
                    'display_name_source' => $name['source'],
                    'road_address' => $road !== '' ? $road : $displayName,
                    'jibun_address' => $jibun !== '' ? $jibun : null,
                    'legal_dong_code' => null,
                    'postal_code' => null,
                    'latitude' => is_numeric($lat) ? (float) $lat : null,
                    'longitude' => is_numeric($lng) ? (float) $lng : null,
                    'status' => 'active',
                ]);

                $buildingKey = $this->residenceNamingService->normalize($complex->normalized_key . '|' . (string) ($road !== '' ? $road : $displayName));

                $building = ResidenceBuilding::query()->firstOrCreate([
                    'normalized_key' => $buildingKey,
                ], [
                    'complex_id' => $complex->id,
                    'building_no' => null,
                    'building_name' => null,
                    'road_address' => $road !== '' ? $road : $displayName,
                    'jibun_address' => $jibun !== '' ? $jibun : null,
                    'bld_main_no' => null,
                    'bld_sub_no' => null,
                    'legal_dong_code' => null,
                    'latitude' => is_numeric($lat) ? (float) $lat : null,
                    'longitude' => is_numeric($lng) ? (float) $lng : null,
                ]);

                return [
                    'id' => (int) ($complex->legacy_apartment_id ?? 0),
                    'complex_id' => (int) $complex->id,
                    'building_id' => (int) $building->id,
                    'name' => $complex->official_name ?: $complex->displayName(),
                    'label' => ($complex->official_name ?: $complex->displayName()) . ' · ' . ($building->road_address ?: $complex->road_address),
                    'region' => trim((string) ($complex->jibun_address ?: $complex->road_address)),
                    'road_address' => (string) ($building->road_address ?: $complex->road_address),
                    'housing_type' => $complex->housing_type,
                ];
            })
            ->filter()
            ->values();
    }

    private function looksLikeAddressQuery(string $keyword): bool
    {
        $text = trim($keyword);

        if ($text === '') {
            return false;
        }

        if (preg_match('/\d/', $text) === 1) {
            return true;
        }

        return preg_match('/(로|길|번길|대로|거리|번지|동\s*\d|호\s*\d|읍|면|리)$/u', $text) === 1;
    }

    private function extractNominatimResidenceName(array $row, string $displayName): ?string
    {
        $address = Arr::get($row, 'address', []);
        if (! is_array($address)) {
            $address = [];
        }

        $candidates = [
            Arr::get($address, 'building'),
            Arr::get($address, 'amenity'),
            explode(',', $displayName)[0] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $name = trim((string) $candidate);
            if ($name === '') {
                continue;
            }

            if (preg_match('/(아파트|오피스텔|빌라|연립|타운하우스|맨션|주택)/u', $name) === 1) {
                return $name;
            }
        }

        return null;
    }

    private function buildFallbackSearchKeywords(string $keyword): array
    {
        $base = trim($keyword);

        if ($base === '') {
            return [];
        }

        $candidates = [$base];

        $withoutSuffix = trim((string) preg_replace('/(아파트|오피스텔|빌라|연립주택|연립|타운하우스|맨션)$/u', '', $base));
        if ($withoutSuffix !== '' && $withoutSuffix !== $base) {
            $candidates[] = $withoutSuffix;
        }

        $normalizedSpaces = preg_replace('/\s+/u', ' ', $base);
        if (is_string($normalizedSpaces)) {
            $normalizedSpaces = trim($normalizedSpaces);
            if ($normalizedSpaces !== '' && $normalizedSpaces !== $base) {
                $candidates[] = $normalizedSpaces;
            }

            $collapsed = str_replace(' ', '', $normalizedSpaces);
            if ($collapsed !== '' && $collapsed !== $base) {
                $candidates[] = $collapsed;
            }
        }

        return array_values(array_unique($candidates));
    }

    private function buildNameSearchTerms(string $keyword): array
    {
        $terms = [];

        foreach ($this->buildFallbackSearchKeywords($keyword) as $candidate) {
            $candidate = trim($candidate);
            if ($candidate === '') {
                continue;
            }

            $terms[] = $candidate;

            $parts = preg_split('/\s+/u', $candidate) ?: [];
            foreach ($parts as $part) {
                $part = trim((string) $part);
                if ($part !== '' && mb_strlen($part) >= 2) {
                    $terms[] = $part;
                }
            }
        }

        return array_values(array_unique($terms));
    }

    private function scoreApartmentMatch(Apartment $apartment, string $keyword, string $normalizedKeyword, array $searchTerms): int
    {
        $name = $this->normalizeText((string) $apartment->name);
        $road = $this->normalizeText((string) $apartment->road_address);
        $score = 0;

        if ($normalizedKeyword !== '') {
            if (str_starts_with($name, $normalizedKeyword)) {
                $score += 120;
            }

            if (str_contains($name, $normalizedKeyword)) {
                $score += 80;
            }

            if (str_contains($road, $normalizedKeyword)) {
                $score += 20;
            }
        }

        foreach ($searchTerms as $term) {
            $normalizedTerm = $this->normalizeText((string) $term);
            if ($normalizedTerm === '') {
                continue;
            }

            if (str_starts_with($name, $normalizedTerm)) {
                $score += 35;
            }

            if (str_contains($name, $normalizedTerm)) {
                $score += 25;
            }

            if (str_contains($road, $normalizedTerm)) {
                $score += 10;
            }
        }

        // Prefer tighter matches when scores are close.
        $score -= (int) floor(mb_strlen((string) $apartment->name) / 10);

        return $score;
    }
}
