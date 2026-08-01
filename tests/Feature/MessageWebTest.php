<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbox_requires_login(): void
    {
        $this->get('/messages')->assertRedirect('/login');
    }

    public function test_inbox_and_tabs_render(): void
    {
        $me = User::factory()->create();
        $peer = User::factory()->create();

        Message::query()->create([
            'sender_id' => $peer->id,
            'receiver_id' => $me->id,
            'content' => '안녕하세요',
        ]);

        $this->actingAs($me)->get('/messages')
            ->assertOk()
            ->assertSee('쪽지함')
            ->assertSee($peer->name);

        $this->actingAs($me)->get('/messages?box=received')->assertOk()->assertSee('안녕하세요');
        $this->actingAs($me)->get('/messages?box=sent')->assertOk();
    }

    public function test_conversation_renders_and_marks_read(): void
    {
        $me = User::factory()->create();
        $peer = User::factory()->create();

        $message = Message::query()->create([
            'sender_id' => $peer->id,
            'receiver_id' => $me->id,
            'content' => '대화 테스트 쪽지',
        ]);

        $this->actingAs($me)->get('/messages/'.$peer->id)
            ->assertOk()
            ->assertSee('대화 테스트 쪽지');

        $this->assertNotNull($message->fresh()->read_at);
    }

    public function test_compose_preselects_recipient(): void
    {
        $me = User::factory()->create();
        $peer = User::factory()->create();

        $this->actingAs($me)->get('/messages/compose?to='.$peer->id)
            ->assertOk()
            ->assertSee($peer->name);
    }

    public function test_store_sends_message_and_redirects_to_conversation(): void
    {
        $me = User::factory()->create();
        $peer = User::factory()->create();

        $this->actingAs($me)
            ->post('/messages', [
                'receiver_id' => $peer->id,
                'content' => '웹에서 보낸 쪽지',
            ])
            ->assertRedirect('/messages/'.$peer->id);

        $this->assertDatabaseHas('messages', [
            'sender_id' => $me->id,
            'receiver_id' => $peer->id,
            'content' => '웹에서 보낸 쪽지',
        ]);
    }

    public function test_user_search_returns_matching_users(): void
    {
        $me = User::factory()->create(['name' => '나자신']);
        User::factory()->create(['name' => '김철수']);
        User::factory()->create(['name' => '김철호']);
        User::factory()->create(['name' => '박민수']);

        $this->actingAs($me)
            ->getJson('/messages/users/search?q='.urlencode('김철'))
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->actingAs($me)
            ->getJson('/messages/users/search?q='.urlencode('김'))
            ->assertStatus(422);
    }
}
