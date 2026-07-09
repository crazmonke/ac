<?php

namespace Tests\Feature\Api;

use App\Models\FcmToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FcmTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_fcm_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/fcm-token', [
            'token' => 'sample-fcm-token',
            'platform' => 'ios',
            'device_id' => 'device-123',
            'device_name' => 'iPhone',
            'app_version' => '1.0.0',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.token', 'sample-fcm-token');

        $this->assertDatabaseHas('fcm_tokens', [
            'user_id' => $user->id,
            'token' => 'sample-fcm-token',
            'platform' => 'ios',
            'device_id' => 'device-123',
        ]);
    }

    public function test_user_can_delete_registered_fcm_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        FcmToken::query()->create([
            'user_id' => $user->id,
            'token' => 'sample-fcm-token',
            'platform' => 'android',
            'device_id' => 'device-456',
            'device_name' => 'Pixel',
            'app_version' => '1.0.0',
            'last_seen_at' => now(),
            'enabled' => true,
        ]);

        $response = $this->deleteJson('/api/v1/fcm-token', [
            'token' => 'sample-fcm-token',
        ]);

        $response->assertNoContent();

        $this->assertDatabaseMissing('fcm_tokens', [
            'user_id' => $user->id,
            'token' => 'sample-fcm-token',
        ]);
    }

    public function test_same_user_and_device_updates_existing_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/fcm-token', [
            'token' => 'token-old',
            'platform' => 'ios',
            'device_id' => 'device-123',
            'device_name' => 'iPhone',
            'app_version' => '1.0.0',
        ])->assertOk();

        $this->postJson('/api/v1/fcm-token', [
            'token' => 'token-new',
            'platform' => 'ios',
            'device_id' => 'device-123',
            'device_name' => 'iPhone',
            'app_version' => '1.0.1',
        ])->assertOk();

        $this->assertDatabaseCount('fcm_tokens', 1);
        $this->assertDatabaseHas('fcm_tokens', [
            'user_id' => $user->id,
            'device_id' => 'device-123',
            'token' => 'token-new',
            'app_version' => '1.0.1',
        ]);
    }
}