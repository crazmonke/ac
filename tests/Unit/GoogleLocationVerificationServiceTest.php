<?php

namespace Tests\Unit;

use App\Services\GoogleLocationVerificationService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleLocationVerificationServiceTest extends TestCase
{
    public function test_it_verifies_by_distance_when_google_key_is_missing(): void
    {
        config(['services.google_maps.api_key' => '']);

        $service = new GoogleLocationVerificationService();

        $result = $service->verifyNearResidenceProfile(
            37.497952,
            127.027619,
            '서울특별시',
            '강남구',
            '역삼동',
            '테헤란로 123',
            37.498000,
            127.027700,
            300
        );

        $this->assertTrue($result['verified']);
        $this->assertSame('matched_by_distance_without_geocode', $result['reason']);
        $this->assertArrayHasKey('distance_meters', $result);
    }

    public function test_it_returns_distance_out_of_range_when_google_key_is_missing_and_far(): void
    {
        config(['services.google_maps.api_key' => '']);

        $service = new GoogleLocationVerificationService();

        $result = $service->verifyNearResidenceProfile(
            37.497952,
            127.027619,
            '서울특별시',
            '강남구',
            '역삼동',
            '테헤란로 123',
            37.566680,
            126.978414,
            300
        );

        $this->assertFalse($result['verified']);
        $this->assertSame('distance_out_of_range_without_geocode', $result['reason']);
        $this->assertArrayHasKey('distance_meters', $result);
    }

    public function test_it_verifies_by_distance_when_google_http_fails(): void
    {
        config(['services.google_maps.api_key' => 'test-key']);

        Http::fake([
            'https://maps.googleapis.com/maps/api/geocode/json*' => Http::response([], 500),
        ]);

        $service = new GoogleLocationVerificationService();

        $result = $service->verifyNearResidenceProfile(
            37.497952,
            127.027619,
            '서울특별시',
            '강남구',
            '역삼동',
            '테헤란로 123',
            37.498000,
            127.027700,
            300
        );

        $this->assertTrue($result['verified']);
        $this->assertSame('matched_by_distance_without_geocode', $result['reason']);
    }
}
