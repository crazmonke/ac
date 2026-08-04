<?php

namespace App\Services;

use App\Models\Message;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MessageService
{
    public function __construct(
        private readonly UserNotificationService $userNotificationService,
    ) {
    }

    /**
     * 쪽지를 발송하고 수신자에게 FCM 푸시 알림을 보낸다.
     */
    public function send(User $sender, User $receiver, string $content, ?int $parentMessageId = null): Message
    {
        $message = Message::query()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'parent_message_id' => $parentMessageId,
            'content' => $content,
        ]);

        try {
            $this->userNotificationService->notifyUser(
                $receiver->id,
                'message',
                $sender->name.'님의 쪽지',
                Str::limit($content, 60),
                '/messages/'.$sender->id,
                'message',
                $message->id,
                [
                    'conversation_id' => (string) $sender->id,
                    'message_id' => (string) $message->id,
                    'sender_id' => (string) $sender->id,
                ]
            );
        } catch (\Throwable $e) {
            report($e); // 알림 실패가 쪽지 발송 자체를 막지 않도록 한다.
        }

        return $message;
    }

    /**
     * 사용자의 대화 목록: 상대별 최신 쪽지 1건 + 읽지 않은 수.
     *
     * @return Collection<int, array{peer: User, last_message: Message, unread_count: int}>
     */
    public function conversationsFor(int $userId, int $scanLimit = 500): Collection
    {
        $messages = Message::query()
            ->involving($userId)
            ->visibleTo($userId)
            ->orderByDesc('id')
            ->limit($scanLimit)
            ->get();

        $conversations = [];
        foreach ($messages as $message) {
            $peerId = $message->sender_id === $userId ? $message->receiver_id : $message->sender_id;

            if (! isset($conversations[$peerId])) {
                $conversations[$peerId] = [
                    'peer_id' => $peerId,
                    'last_message' => $message,
                    'unread_count' => 0,
                ];
            }

            if ($message->receiver_id === $userId && $message->read_at === null) {
                $conversations[$peerId]['unread_count']++;
            }
        }

        $peers = User::query()
            ->whereIn('id', array_keys($conversations))
            ->get()
            ->keyBy('id');

        return collect($conversations)
            ->map(function (array $conversation) use ($peers) {
                $conversation['peer'] = $peers->get($conversation['peer_id']);

                return $conversation;
            })
            ->filter(fn (array $conversation) => $conversation['peer'] !== null)
            ->values();
    }

    /**
     * 두 사용자 간 대화에서 내가 받은 쪽지를 모두 읽음 처리한다.
     */
    public function markConversationRead(int $userId, int $peerId): int
    {
        return Message::query()
            ->where('receiver_id', $userId)
            ->where('sender_id', $peerId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * 대화를 요청자 쪽지함에서만 감춘다 (상대방 쪽지함에는 그대로 유지).
     * 이후 새로 주고받는 쪽지는 다시 보인다.
     */
    public function hideConversationFor(int $userId, int $peerId): void
    {
        Message::query()
            ->between($userId, $peerId)
            ->where('sender_id', $userId)
            ->whereNull('sender_hidden_at')
            ->update(['sender_hidden_at' => now()]);

        Message::query()
            ->between($userId, $peerId)
            ->where('receiver_id', $userId)
            ->whereNull('receiver_hidden_at')
            ->update(['receiver_hidden_at' => now()]);
    }

    /**
     * 관리자 역할을 가진 사용자 id 목록 (만료되지 않은 역할).
     *
     * @return array<int>
     */
    public function adminUserIds(): array
    {
        return UserRole::query()
            ->where('role', 'admin')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->distinct()
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * 회원-관리자 문의의 기본 수신자(대표 관리자).
     */
    public function primaryAdmin(): ?User
    {
        $adminIds = $this->adminUserIds();

        if ($adminIds === []) {
            return null;
        }

        return User::query()
            ->whereIn('id', $adminIds)
            ->where('access_allowed', true)
            ->whereNull('withdrawn_at')
            ->orderBy('id')
            ->first();
    }

    /**
     * 쪽지를 받을 수 있는 사용자인지 검증한다.
     */
    public function canReceive(User $user): bool
    {
        return $user->access_allowed && $user->withdrawn_at === null;
    }
}
