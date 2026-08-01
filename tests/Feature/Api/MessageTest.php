<?php

namespace Tests\Feature\Api;

use App\Models\Message;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_send_message_and_receiver_gets_notification(): void
    {
        $this->configureFirebaseForTests();
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'access-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ]),
            'https://fcm.googleapis.com/*' => Http::response(['name' => 'projects/apaind/messages/1']),
        ]);

        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        Sanctum::actingAs($sender);

        $response = $this->postJson('/api/messages', [
            'receiver_id' => $receiver->id,
            'content' => '안녕하세요, 문의드립니다.',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('messages', [
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'content' => '안녕하세요, 문의드립니다.',
            'read_at' => null,
        ]);

        $notification = UserNotification::query()
            ->where('user_id', $receiver->id)
            ->where('type', 'message')
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame('/messages/'.$sender->id, $notification->link);
    }

    public function test_user_cannot_send_message_to_self(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/messages', [
            'receiver_id' => $user->id,
            'content' => '나에게 쓰는 쪽지',
        ])->assertStatus(422);
    }

    public function test_conversation_show_marks_received_messages_as_read(): void
    {
        $me = User::factory()->create();
        $peer = User::factory()->create();

        $incoming = Message::query()->create([
            'sender_id' => $peer->id,
            'receiver_id' => $me->id,
            'content' => '받은 쪽지',
        ]);

        Sanctum::actingAs($me);

        $this->getJson('/api/messages/'.$peer->id)
            ->assertOk()
            ->assertJsonPath('peer.id', $peer->id);

        $this->assertNotNull($incoming->fresh()->read_at);
    }

    public function test_conversation_list_returns_unread_count(): void
    {
        $me = User::factory()->create();
        $peer = User::factory()->create();

        Message::query()->create(['sender_id' => $peer->id, 'receiver_id' => $me->id, 'content' => '하나']);
        Message::query()->create(['sender_id' => $peer->id, 'receiver_id' => $me->id, 'content' => '둘']);

        Sanctum::actingAs($me);

        $this->getJson('/api/messages')
            ->assertOk()
            ->assertJsonPath('data.0.conversation_id', $peer->id)
            ->assertJsonPath('data.0.unread_count', 2);

        $this->getJson('/api/messages/unread-count')
            ->assertOk()
            ->assertJsonPath('unread_count', 2);
    }

    public function test_only_receiver_can_mark_message_as_read(): void
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $other = User::factory()->create();

        $message = Message::query()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'content' => '읽음 테스트',
        ]);

        Sanctum::actingAs($other);
        $this->putJson('/api/messages/'.$message->id.'/read')->assertForbidden();

        Sanctum::actingAs($receiver);
        $this->putJson('/api/messages/'.$message->id.'/read')->assertOk();

        $this->assertNotNull($message->fresh()->read_at);
    }

    public function test_admin_inquiries_requires_admin_role(): void
    {
        $member = User::factory()->create();
        Sanctum::actingAs($member);

        $this->getJson('/api/admin/inquiries')->assertForbidden();
    }

    public function test_admin_can_list_member_inquiries(): void
    {
        $admin = User::factory()->create();
        $apartment = \App\Models\Apartment::query()->create([
            'name' => '테스트 아파트',
            'road_address' => '서울특별시 테스트로 1',
            'jibun_address' => null,
            'sido' => '서울특별시',
            'sigungu' => '강남구',
            'eupmyeondong' => '삼성동',
        ]);
        UserRole::query()->create([
            'user_id' => $admin->id,
            'apartment_id' => $apartment->id,
            'role' => 'admin',
            'granted_at' => now(),
        ]);

        $member = User::factory()->create();

        Message::query()->create([
            'sender_id' => $member->id,
            'receiver_id' => $admin->id,
            'content' => '관리자님께 문의합니다.',
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/inquiries')
            ->assertOk()
            ->assertJsonPath('data.0.member.id', $member->id)
            ->assertJsonPath('data.0.unread_by_admin', 1);

        $this->getJson('/api/admin/inquiries/'.$member->id)
            ->assertOk()
            ->assertJsonPath('member.id', $member->id)
            ->assertJsonCount(1, 'messages.data');
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
