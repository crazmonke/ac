<?php

namespace Tests\Feature\Api;

use App\Models\Board;
use App\Models\BoardCategory;
use App\Models\Apartment;
use App\Models\Comment;
use App\Models\Post;
use App\Models\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FcmDispatchTest extends TestCase
{
    use RefreshDatabase;

    private int $currentApartmentId = 0;

    public function test_new_post_dispatches_notice_topic_when_marked_as_notice(): void
    {
        $user = User::factory()->create();
        $apartment = Apartment::query()->create([
            'name' => '테스트 아파트',
            'road_address' => '서울특별시 테스트로 1',
            'jibun_address' => null,
            'sido' => '서울특별시',
            'sigungu' => '강남구',
            'eupmyeondong' => '삼성동',
        ]);
        UserRole::query()->create([
            'user_id' => $user->id,
            'apartment_id' => $apartment->id,
            'role' => 'resident',
            'granted_at' => now(),
        ]);
        $this->configureFirebaseForTests();
        Sanctum::actingAs($user);

        $board = $this->makeBoard($apartment->id);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'access-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ]),
            'https://fcm.googleapis.com/v1/projects/*/messages:send' => Http::response([
                'name' => 'projects/apaind/messages/123',
            ]),
        ]);

        $response = $this->postJson('/api/boards/' . $board->id . '/posts', [
            'title' => '공지 테스트',
            'body' => '공지 본문',
            'is_notice' => true,
        ]);

        $response->assertCreated();

        Http::assertSentCount(2);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://fcm.googleapis.com/v1/projects/apaind/messages:send'
                && $request['message']['topic'] === 'notice'
                && $request['message']['data']['type'] === 'notice';
        });
    }

    public function test_comment_dispatches_comment_topic(): void
    {
        $user = User::factory()->create();
        $apartment = Apartment::query()->create([
            'name' => '테스트 아파트',
            'road_address' => '서울특별시 테스트로 1',
            'jibun_address' => null,
            'sido' => '서울특별시',
            'sigungu' => '강남구',
            'eupmyeondong' => '삼성동',
        ]);
        UserRole::query()->create([
            'user_id' => $user->id,
            'apartment_id' => $apartment->id,
            'role' => 'resident',
            'granted_at' => now(),
        ]);
        $this->configureFirebaseForTests();
        Sanctum::actingAs($user);

        $board = $this->makeBoard($apartment->id);
        $post = Post::query()->create([
            'board_id' => $board->id,
            'apartment_id' => $board->apartment_id,
            'user_id' => $user->id,
            'title' => '게시글',
            'body' => '본문',
            'visibility' => 'resident_only',
            'audience_scope' => 'apartment',
            'is_notice' => false,
            'is_anonymous' => false,
            'is_guest_visible' => false,
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'access-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ]),
            'https://fcm.googleapis.com/v1/projects/*/messages:send' => Http::response([
                'name' => 'projects/apaind/messages/456',
            ]),
        ]);

        $response = $this->postJson('/api/posts/' . $post->id . '/comments', [
            'body' => '댓글 테스트',
        ]);

        $response->assertCreated();

        Http::assertSentCount(2);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://fcm.googleapis.com/v1/projects/apaind/messages:send'
                && $request['message']['topic'] === 'comment'
                && $request['message']['data']['type'] === 'comment';
        });
    }

    private function makeBoard(int $apartmentId): Board
    {
        $this->currentApartmentId = $apartmentId;

        $category = BoardCategory::query()->create([
            'name' => '테스트',
            'slug' => 'test',
            'sort_order' => 0,
            'is_public' => true,
        ]);

        return Board::query()->create([
            'category_id' => $category->id,
            'apartment_id' => $this->currentApartmentId,
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