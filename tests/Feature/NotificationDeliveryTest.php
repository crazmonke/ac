<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Board;
use App\Models\BoardCategory;
use App\Models\FcmToken;
use App\Models\Post;
use App\Models\User;
use App\Models\UserRole;
use App\Services\PointService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotificationDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_like_creates_notification_history_and_push(): void
    {
        [$owner, $actor, $post] = $this->makePostContext();

        FcmToken::query()->create([
            'user_id' => $owner->id,
            'token' => 'owner-device-token',
            'enabled' => true,
        ]);

        $this->configureFirebaseForTests();

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'access-token',
            ]),
            'https://fcm.googleapis.com/v1/projects/*/messages:send' => Http::response([
                'name' => 'projects/apaind/messages/like',
            ]),
        ]);

        $response = $this->actingAs($actor)->from('/community/posts/' . $post->id)
            ->post('/community/posts/' . $post->id . '/likes');

        $response->assertRedirect('/community/posts/' . $post->id);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $owner->id,
            'type' => 'like',
            'source_type' => 'post_like',
            'link' => '/community/posts/' . $post->id,
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/messages:send')
                && $request['message']['token'] === 'owner-device-token'
                && $request['message']['notification']['title'] === '게시글에 좋아요가 달렸습니다';
        });
    }

    public function test_admin_point_grant_creates_notification_history_and_push(): void
    {
        $user = User::factory()->create();

        FcmToken::query()->create([
            'user_id' => $user->id,
            'token' => 'point-device-token',
            'enabled' => true,
        ]);

        $this->configureFirebaseForTests();

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'access-token',
            ]),
            'https://fcm.googleapis.com/v1/projects/*/messages:send' => Http::response([
                'name' => 'projects/apaind/messages/point',
            ]),
        ]);

        app(PointService::class)->adminGrant($user, 500, '테스트 지급');

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $user->id,
            'type' => 'point',
            'source_type' => 'point_transaction',
            'title' => '포인트가 지급되었습니다',
            'link' => '/points',
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/messages:send')
                && $request['message']['token'] === 'point-device-token'
                && $request['message']['notification']['title'] === '포인트가 지급되었습니다';
        });
    }

    public function test_admin_broadcast_is_saved_to_notification_history(): void
    {
        $admin = User::factory()->create();
        $recipient = User::factory()->create();
        $apartment = Apartment::query()->create([
            'name' => '관리 아파트',
            'road_address' => '서울특별시 테스트로 9',
            'jibun_address' => null,
            'sido' => '서울특별시',
            'sigungu' => '강남구',
            'eupmyeondong' => '역삼동',
        ]);

        UserRole::query()->create([
            'user_id' => $admin->id,
            'apartment_id' => $apartment->id,
            'role' => 'admin',
            'granted_at' => now(),
        ]);

        FcmToken::query()->create([
            'user_id' => $recipient->id,
            'token' => 'broadcast-device-token',
            'enabled' => true,
        ]);

        $this->configureFirebaseForTests();

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'access-token',
            ]),
            'https://fcm.googleapis.com/v1/projects/*/messages:send' => Http::response([
                'name' => 'projects/apaind/messages/topic',
            ]),
        ]);

        $response = $this->actingAs($admin)->post('/admin/notifications', [
            'topic' => 'comment',
            'title' => '관리자 테스트',
            'body' => '테스트 메시지',
        ]);

        $response->assertRedirect('/admin/notifications');

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $recipient->id,
            'type' => 'broadcast',
            'title' => '관리자 테스트',
            'body' => '테스트 메시지',
            'link' => '/notifications',
            'source_type' => 'broadcast_message',
        ]);
    }

    private function makePostContext(): array
    {
        $owner = User::factory()->create();
        $actor = User::factory()->create();
        $apartment = Apartment::query()->create([
            'name' => '테스트 아파트',
            'road_address' => '서울특별시 테스트로 1',
            'jibun_address' => null,
            'sido' => '서울특별시',
            'sigungu' => '강남구',
            'eupmyeondong' => '삼성동',
        ]);

        UserRole::query()->create([
            'user_id' => $owner->id,
            'apartment_id' => $apartment->id,
            'role' => 'resident',
            'granted_at' => now(),
        ]);

        UserRole::query()->create([
            'user_id' => $actor->id,
            'apartment_id' => $apartment->id,
            'role' => 'resident',
            'granted_at' => now(),
        ]);

        $category = BoardCategory::query()->create([
            'name' => '테스트',
            'slug' => 'test',
            'sort_order' => 0,
            'is_public' => true,
        ]);

        $board = Board::query()->create([
            'category_id' => $category->id,
            'apartment_id' => $apartment->id,
            'name' => '테스트 게시판',
            'slug' => 'test-board',
            'description' => null,
            'board_type' => 'community',
            'read_role' => 'resident',
            'write_role' => 'resident',
            'comment_role' => 'resident',
            'allow_file' => true,
            'allow_anonymous' => true,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $post = Post::query()->create([
            'board_id' => $board->id,
            'apartment_id' => $apartment->id,
            'user_id' => $owner->id,
            'title' => '테스트 게시글',
            'body' => '본문',
            'visibility' => 'resident_only',
            'audience_scope' => 'apartment',
            'is_notice' => false,
            'is_anonymous' => false,
            'is_guest_visible' => false,
        ]);

        return [$owner, $actor, $post];
    }

    private function configureFirebaseForTests(): void
    {
        $privateKey = '';
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($resource, $privateKey);

        config()->set('services.firebase.project_id', 'apaind');
        config()->set('services.firebase.client_email', 'firebase@test.local');
        config()->set('services.firebase.private_key', $privateKey);
    }
}