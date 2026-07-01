<?php

namespace App\Services;

use App\Models\Apartment;
use App\Models\ApartmentAlias;
use App\Models\ApartmentMatchReview;
use App\Models\ResidentVerificationRequest;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ApartmentSelectionService
{
    public function __construct(private readonly GoogleLocationVerificationService $locationVerificationService)
    {
    }

    public function search(string $query, int $limit = 8): Collection
    {
        $keyword = trim($query);

        if ($keyword === '') {
            return collect();
        }

        $apartments = Apartment::query()
            ->where('is_active', true)
            ->where(function (Builder $builder) use ($keyword) {
                $builder->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('road_address', 'like', '%' . $keyword . '%')
                    ->orWhere('sigungu', 'like', '%' . $keyword . '%')
                    ->orWhere('eupmyeondong', 'like', '%' . $keyword . '%')
                    ->orWhereHas('aliases', function (Builder $aliasQuery) use ($keyword) {
                        $aliasQuery->where('alias', 'like', '%' . $keyword . '%');
                    });
            })
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return $apartments->map(function (Apartment $apartment) {
            return [
                'id' => (int) $apartment->id,
                'name' => $apartment->name,
                'label' => $apartment->name . ' · ' . $apartment->sido . ' ' . $apartment->sigungu,
                'region' => trim($apartment->sido . ' ' . $apartment->sigungu . ' ' . $apartment->eupmyeondong),
                'road_address' => $apartment->road_address,
            ];
        })->values();
    }

    public function applySelection(User $user, ?int $apartmentId, string $apartmentQuery, string $source, ?float $latitude = null, ?float $longitude = null): array
    {
        $query = trim($apartmentQuery);
        $selectedApartment = null;
        $matchReview = null;
        $autoVerified = false;
        $verificationReason = null;

        if ($apartmentId) {
            $selectedApartment = Apartment::query()->findOrFail($apartmentId);
            $user->preferred_apartment_id = $selectedApartment->id;
            $user->home_sido = $selectedApartment->sido;
            $user->home_sigungu = $selectedApartment->sigungu;
            $user->home_eupmyeondong = $selectedApartment->eupmyeondong;
            $user->home_apartment_name = $selectedApartment->name;
            $user->save();

            if ($latitude !== null && $longitude !== null) {
                $verification = $this->tryAutoApproveResidentByLocation(
                    $user,
                    $selectedApartment,
                    $latitude,
                    $longitude,
                    $source
                );
                $autoVerified = (bool) ($verification['approved'] ?? false);
                $verificationReason = (string) ($verification['reason'] ?? 'region_not_matched');
            }

            ApartmentMatchReview::query()
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'resolved',
                    'resolved_apartment_id' => $selectedApartment->id,
                    'resolved_at' => now(),
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
        }

        return [
            'selected_apartment' => $selectedApartment,
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
        $verification = $this->locationVerificationService->verifyNearApartment($latitude, $longitude, $apartment);

        if (($verification['verified'] ?? false) !== true) {
            return [
                'approved' => false,
                'reason' => (string) ($verification['reason'] ?? 'region_not_matched'),
                'details' => $verification,
            ];
        }

        UserRole::query()->firstOrCreate([
            'user_id' => $user->id,
            'apartment_id' => $apartment->id,
            'role' => 'resident',
        ], [
            'granted_at' => now(),
            'granted_by' => null,
        ]);

        ResidentVerificationRequest::query()->updateOrCreate([
            'user_id' => $user->id,
            'apartment_id' => $apartment->id,
            'status' => 'approved',
        ], [
            'request_note' => 'GPS 기반 자동 인증 승인',
            'admin_note' => 'Google Geocoding 위치 매칭으로 자동 승인됨 (source: '.$source.')',
            'reviewed_by' => null,
            'reviewed_at' => now(),
        ]);

        // Unique(user_id, apartment_id, status) 제약 때문에 pending을 approved로 변경하면
        // 기존 approved 행과 충돌할 수 있어, 대기 요청은 자동승인 이후 정리한다.
        ResidentVerificationRequest::query()
            ->where('user_id', $user->id)
            ->where('apartment_id', $apartment->id)
            ->where('status', 'pending')
            ->delete();

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
}
