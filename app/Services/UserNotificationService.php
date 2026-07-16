<?php

namespace App\Services;

use App\Models\FcmToken;
use App\Models\UserNotification;

class UserNotificationService
{
    public function __construct(
        private readonly FcmMessagingService $fcmMessagingService,
    ) {
    }

    public function notifyUser(
        int $userId,
        string $type,
        string $title,
        string $body = '',
        ?string $link = null,
        ?string $sourceType = null,
        ?int $sourceId = null,
        array $data = []
    ): UserNotification {
        $notification = $this->storeForUser($userId, $type, $title, $body, $link, $sourceType, $sourceId, $data);

        $payload = array_merge($data, [
            'type' => $type,
            'notification_id' => (string) $notification->id,
        ]);

        if ($link) {
            $payload['url'] = $link;
            $payload['deep_link'] = $link;
            $payload['link'] = $link;
        }

        $this->fcmMessagingService->sendToUserDevices($userId, $title, $body, $payload);

        return $notification;
    }

    public function storeForUser(
        int $userId,
        string $type,
        string $title,
        string $body = '',
        ?string $link = null,
        ?string $sourceType = null,
        ?int $sourceId = null,
        array $data = []
    ): UserNotification {
        return UserNotification::query()->create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'link' => $link,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'data' => $data,
        ]);
    }

    public function storeForActiveTokenUsers(
        string $type,
        string $title,
        string $body = '',
        ?string $link = null,
        ?string $sourceType = null,
        ?int $sourceId = null,
        array $data = []
    ): void {
        $userIds = FcmToken::query()
            ->where('enabled', true)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        foreach ($userIds as $userId) {
            $this->storeForUser((int) $userId, $type, $title, $body, $link, $sourceType, $sourceId, $data);
        }
    }
}