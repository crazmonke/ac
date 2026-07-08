<?php

namespace App\Services;

use App\Models\Apartment;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class GoogleLocationVerificationService
{
    private const MAX_DISTANCE_METERS = 3000;

    public function verifyNearApartment(float $latitude, float $longitude, Apartment $apartment): array
    {
        return $this->verifyNearResidenceProfile(
            $latitude,
            $longitude,
            (string) $apartment->sido,
            (string) $apartment->sigungu,
            (string) $apartment->eupmyeondong,
            (string) $apartment->road_address,
            null,
            null,
            self::MAX_DISTANCE_METERS
        );
    }

    public function verifyNearResidenceProfile(
        float $latitude,
        float $longitude,
        string $sido,
        string $sigungu,
        string $dong,
        string $roadAddress,
        ?float $targetLatitude = null,
        ?float $targetLongitude = null,
        int $maxDistanceMeters = self::MAX_DISTANCE_METERS
    ): array {
        $apiKey = trim((string) config('services.google_maps.api_key'));
        $distanceOnlyResult = $this->verifyByDistanceOnly(
            $latitude,
            $longitude,
            $targetLatitude,
            $targetLongitude,
            $maxDistanceMeters
        );

        if ($apiKey === '') {
            return $distanceOnlyResult ?? [
                'verified' => false,
                'reason' => 'google_api_key_missing',
            ];
        }

        $response = Http::timeout(8)
            ->retry(1, 400, throw: false)
            ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'latlng' => $latitude . ',' . $longitude,
                'language' => 'ko',
                'key' => $apiKey,
            ]);

        if (! $response->successful()) {
            return $distanceOnlyResult ?? [
                'verified' => false,
                'reason' => 'google_api_http_error',
            ];
        }

        $payload = $response->json();

        if (! is_array($payload) || (string) Arr::get($payload, 'status', '') !== 'OK') {
            return $distanceOnlyResult ?? [
                'verified' => false,
                'reason' => 'google_api_status_not_ok',
            ];
        }

        $components = collect(Arr::get($payload, 'results', []))
            ->flatMap(fn ($row) => Arr::get($row, 'address_components', []));

        $componentByType = function (array $types) use ($components): string {
            $entry = $components->first(function ($component) use ($types) {
                $componentTypes = (array) ($component['types'] ?? []);
                foreach ($types as $type) {
                    if (in_array($type, $componentTypes, true)) {
                        return true;
                    }
                }

                return false;
            });

            return trim((string) ($entry['long_name'] ?? ''));
        };

        $gpsSido = $componentByType(['administrative_area_level_1']);
        $gpsSigungu = $componentByType(['administrative_area_level_2', 'locality', 'administrative_area_level_3']);
        $gpsDong = $componentByType(['sublocality_level_1', 'sublocality_level_2', 'sublocality', 'neighborhood']);

        $normalizedGpsSido = $this->normalizeRegion($gpsSido);
        $normalizedAptSido = $this->normalizeRegion($sido);
        $normalizedGpsSigungu = $this->normalizeRegion($gpsSigungu);
        $normalizedAptSigungu = $this->normalizeRegion($sigungu);

        $matchesSido = $normalizedGpsSido !== ''
            && $normalizedAptSido !== ''
            && (str_contains($normalizedGpsSido, $normalizedAptSido) || str_contains($normalizedAptSido, $normalizedGpsSido));

        $matchesSigungu = $normalizedGpsSigungu !== ''
            && $normalizedAptSigungu !== ''
            && (str_contains($normalizedGpsSigungu, $normalizedAptSigungu) || str_contains($normalizedAptSigungu, $normalizedGpsSigungu));

        $apartmentDong = $this->normalizeRegion($dong);
        $gpsDongNorm = $this->normalizeRegion($gpsDong);
        $matchesDong = $apartmentDong !== '' && $gpsDongNorm !== ''
            ? (str_contains($gpsDongNorm, $apartmentDong) || str_contains($apartmentDong, $gpsDongNorm))
            : false;

        // 시군구까지 일치하면 동명은 오차 허용으로 승인한다.
        $verifiedByRegion = $matchesSido && $matchesSigungu;

        if ($verifiedByRegion) {
            return [
                'verified' => true,
                'reason' => $matchesDong ? 'matched_by_google_geocode' : 'matched_by_region_with_dong_tolerance',
                'gps_sido' => $gpsSido,
                'gps_sigungu' => $gpsSigungu,
                'gps_dong' => $gpsDong,
            ];
        }

        $apartmentCoords = null;

        if ($targetLatitude !== null && $targetLongitude !== null) {
            $apartmentCoords = [
                'lat' => $targetLatitude,
                'lng' => $targetLongitude,
            ];
        } else {
            $apartmentCoords = $this->geocodeAddressCoordinates($sido, $sigungu, $roadAddress, $apiKey);
        }

        if ($apartmentCoords !== null) {
            $distanceMeters = $this->haversineMeters(
                $latitude,
                $longitude,
                (float) $apartmentCoords['lat'],
                (float) $apartmentCoords['lng']
            );

            if ($distanceMeters <= $maxDistanceMeters) {
                return [
                    'verified' => true,
                    'reason' => 'matched_by_distance_fallback',
                    'gps_sido' => $gpsSido,
                    'gps_sigungu' => $gpsSigungu,
                    'gps_dong' => $gpsDong,
                    'distance_meters' => (int) round($distanceMeters),
                ];
            }
        }

        return [
            'verified' => false,
            'reason' => 'region_not_matched',
            'gps_sido' => $gpsSido,
            'gps_sigungu' => $gpsSigungu,
            'gps_dong' => $gpsDong,
        ];
    }

    private function verifyByDistanceOnly(
        float $latitude,
        float $longitude,
        ?float $targetLatitude,
        ?float $targetLongitude,
        int $maxDistanceMeters
    ): ?array {
        if ($targetLatitude === null || $targetLongitude === null) {
            return null;
        }

        $distanceMeters = $this->haversineMeters(
            $latitude,
            $longitude,
            (float) $targetLatitude,
            (float) $targetLongitude
        );

        if ($distanceMeters <= $maxDistanceMeters) {
            return [
                'verified' => true,
                'reason' => 'matched_by_distance_without_geocode',
                'distance_meters' => (int) round($distanceMeters),
            ];
        }

        return [
            'verified' => false,
            'reason' => 'distance_out_of_range_without_geocode',
            'distance_meters' => (int) round($distanceMeters),
        ];
    }

    private function geocodeAddressCoordinates(string $sido, string $sigungu, string $roadAddress, string $apiKey): ?array
    {
        $address = trim(implode(' ', array_filter([
            $sido,
            $sigungu,
            $roadAddress,
        ])));

        if ($address === '') {
            return null;
        }

        $response = Http::timeout(8)
            ->retry(1, 400, throw: false)
            ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $address,
                'language' => 'ko',
                'key' => $apiKey,
            ]);

        if (! $response->successful()) {
            return null;
        }

        $payload = $response->json();

        if (! is_array($payload) || (string) Arr::get($payload, 'status', '') !== 'OK') {
            return null;
        }

        $lat = Arr::get($payload, 'results.0.geometry.location.lat');
        $lng = Arr::get($payload, 'results.0.geometry.location.lng');

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        return [
            'lat' => (float) $lat,
            'lng' => (float) $lng,
        ];
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

    private function normalizeRegion(?string $value): string
    {
        $text = trim((string) $value);
        $text = preg_replace('/\s+/u', '', $text) ?? '';

        return mb_strtolower($text);
    }
}
