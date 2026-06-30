<?php

namespace App\Services;

use App\Models\Apartment;
use App\Models\ApartmentAlias;
use App\Models\ApartmentMatchReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ApartmentSelectionService
{
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

    public function applySelection(User $user, ?int $apartmentId, string $apartmentQuery, string $source): array
    {
        $query = trim($apartmentQuery);
        $selectedApartment = null;
        $matchReview = null;

        if ($apartmentId) {
            $selectedApartment = Apartment::query()->findOrFail($apartmentId);
            $user->preferred_apartment_id = $selectedApartment->id;
            $user->save();

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
