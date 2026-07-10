<?php

namespace App\Services;

use App\Models\FcmToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmMessagingService
{
    private const GOOGLE_OAUTH_TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const FIREBASE_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function sendComment(int $postId, ?int $apartmentId = null, array $extraData = []): void
    {
        $this->sendTopicNotification(
            'comment',
            '새 댓글이 달렸습니다',
            '게시글에 새 댓글이 도착했습니다.',
            $this->buildPayload('comment', $postId, $apartmentId, $extraData)
        );
    }

    public function sendCommentToUser(int $userId, int $postId, ?int $apartmentId = null, array $extraData = []): void
    {
        $this->sendToUserDevices(
            $userId,
            '새 댓글이 달렸습니다',
            '게시글에 새 댓글이 도착했습니다.',
            $this->buildPayload('comment', $postId, $apartmentId, $extraData)
        );
    }

    public function sendLikeToUser(int $userId, int $postId, ?int $apartmentId = null, array $extraData = []): void
    {
        $this->sendToUserDevices(
            $userId,
            '게시글에 좋아요가 달렸습니다',
            '내 게시글을 누군가 좋아합니다.',
            $this->buildPayload('like', $postId, $apartmentId, $extraData)
        );
    }

    public function sendToUserDevices(int $userId, string $title, string $body, array $data = []): void
    {
        $tokens = FcmToken::query()
            ->where('user_id', $userId)
            ->where('enabled', true)
            ->pluck('token');

        foreach ($tokens as $token) {
            $this->sendToToken($token, $title, $body, $data);
        }
    }

    public function sendToToken(string $token, string $title, string $body, array $data = []): void
    {
        $projectId = config('services.firebase.project_id');

        if (! $projectId) {
            return;
        }

        $accessToken = $this->getAccessToken();

        if (! $accessToken) {
            return;
        }

        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $this->normalizeData($data),
            ],
        ];

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $payload);

        if (! $response->successful()) {
            Log::warning('FCM token notification failed.', [
                'token' => substr($token, 0, 20) . '...',
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }

    public function sendNewPost(int $postId, ?int $apartmentId = null, array $extraData = []): void
    {
        $this->sendTopicNotification(
            'new_post',
            '새 글이 등록되었습니다',
            '커뮤니티에 새 글이 올라왔습니다.',
            $this->buildPayload('new_post', $postId, $apartmentId, $extraData)
        );
    }

    public function sendNotice(int $postId, ?int $apartmentId = null, array $extraData = []): void
    {
        $this->sendTopicNotification(
            'notice',
            '공지사항이 등록되었습니다',
            '새 공지사항을 확인해 주세요.',
            $this->buildPayload('notice', $postId, $apartmentId, $extraData)
        );
    }

    public function sendTopicNotification(string $topic, string $title, string $body, array $data = []): void
    {
        $projectId = config('services.firebase.project_id');

        if (! $projectId) {
            Log::warning('FCM notification skipped: missing Firebase project id.', compact('topic'));

            return;
        }

        $accessToken = $this->getAccessToken();

        if (! $accessToken) {
            Log::warning('FCM notification skipped: unable to acquire access token.', compact('topic', 'projectId'));

            return;
        }

        $payload = [
            'message' => [
                'topic' => $topic,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $this->normalizeData($data),
            ],
        ];

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $payload);

        if (! $response->successful()) {
            Log::warning('FCM notification failed.', [
                'topic' => $topic,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }

    private function buildPayload(string $type, int $postId, ?int $apartmentId, array $extraData = []): array
    {
        return array_merge([
            'type' => $type,
            'notificationType' => $type,
            'post_id' => (string) $postId,
            'url' => $this->buildPostUrl($postId, $apartmentId),
            'deep_link' => $this->buildPostUrl($postId, $apartmentId),
            'link' => $this->buildPostUrl($postId, $apartmentId),
        ], $extraData);
    }

    private function buildPostUrl(int $postId, ?int $apartmentId): string
    {
        $query = [];

        if ($apartmentId !== null) {
            $query['apartment_id'] = $apartmentId;
        }

        return '/community/posts/' . $postId . ($query ? ('?' . http_build_query($query)) : '');
    }

    private function normalizeData(array $data): object
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            if (is_bool($value)) {
                $normalized[$key] = $value ? 'true' : 'false';
                continue;
            }

            if ($value === null) {
                continue;
            }

            $normalized[$key] = is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (object) $normalized;
    }

    private function getAccessToken(): ?string
    {
        $projectId = config('services.firebase.project_id');
        $clientEmail = config('services.firebase.client_email');
        $privateKey = $this->normalizePrivateKey((string) config('services.firebase.private_key'));

        if (! $projectId || ! $clientEmail || ! $privateKey) {
            return null;
        }

        $cacheKey = 'firebase.access_token.' . sha1($projectId . '|' . $clientEmail);

        return Cache::remember($cacheKey, now()->addMinutes(45), function () use ($clientEmail, $privateKey) {
            $now = time();

            $header = $this->base64UrlEncode(json_encode([
                'alg' => 'RS256',
                'typ' => 'JWT',
            ], JSON_UNESCAPED_SLASHES));

            $claimSet = $this->base64UrlEncode(json_encode([
                'iss' => $clientEmail,
                'scope' => self::FIREBASE_SCOPE,
                'aud' => self::GOOGLE_OAUTH_TOKEN_URL,
                'iat' => $now,
                'exp' => $now + 3600,
            ], JSON_UNESCAPED_SLASHES));

            $signatureInput = $header . '.' . $claimSet;
            $signature = '';

            if (! openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
                return null;
            }

            $jwt = $signatureInput . '.' . $this->base64UrlEncode($signature);

            $response = Http::asForm()->post(self::GOOGLE_OAUTH_TOKEN_URL, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if (! $response->successful()) {
                Log::warning('Unable to fetch Firebase access token.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            return $response->json('access_token');
        });
    }

    private function normalizePrivateKey(string $privateKey): string
    {
        $privateKey = trim($privateKey);

        if ($privateKey === '') {
            return '';
        }

        return str_replace('\\n', "\n", $privateKey);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}