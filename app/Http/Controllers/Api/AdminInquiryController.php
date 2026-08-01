<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 회원-관리자 쪽지(1:1 문의) 관리자용 조회.
 */
class AdminInquiryController extends Controller
{
    public function __construct(
        private readonly MessageService $messageService,
    ) {
    }

    /**
     * GET /api/admin/inquiries
     * 관리자와 주고받은 쪽지를 회원별 대화로 묶어서 반환한다.
     */
    public function index(Request $request): JsonResponse
    {
        $adminIds = $this->messageService->adminUserIds();

        if ($adminIds === []) {
            return response()->json(['data' => []]);
        }

        $messages = Message::query()
            ->where(function ($q) use ($adminIds) {
                $q->whereIn('sender_id', $adminIds)->orWhereIn('receiver_id', $adminIds);
            })
            ->orderByDesc('id')
            ->limit(1000)
            ->get();

        $conversations = [];
        foreach ($messages as $message) {
            $memberId = in_array($message->sender_id, $adminIds, true)
                ? $message->receiver_id
                : $message->sender_id;

            if (in_array($memberId, $adminIds, true)) {
                continue; // 관리자끼리 주고받은 쪽지는 문의 목록에서 제외
            }

            if (! isset($conversations[$memberId])) {
                $conversations[$memberId] = [
                    'member_id' => $memberId,
                    'last_message' => $message,
                    'unread_by_admin' => 0,
                    'message_count' => 0,
                ];
            }

            $conversations[$memberId]['message_count']++;

            if (in_array($message->receiver_id, $adminIds, true) && $message->read_at === null) {
                $conversations[$memberId]['unread_by_admin']++;
            }
        }

        $members = User::query()
            ->whereIn('id', array_keys($conversations))
            ->get(['id', 'name', 'email'])
            ->keyBy('id');

        $data = collect($conversations)
            ->filter(fn (array $conversation) => $members->has($conversation['member_id']))
            ->map(function (array $conversation) use ($members) {
                $member = $members->get($conversation['member_id']);

                return [
                    'member' => [
                        'id' => $member->id,
                        'name' => $member->name,
                        'email' => $member->email,
                    ],
                    'last_message' => [
                        'id' => $conversation['last_message']->id,
                        'sender_id' => $conversation['last_message']->sender_id,
                        'content' => $conversation['last_message']->content,
                        'created_at' => $conversation['last_message']->created_at,
                    ],
                    'unread_by_admin' => $conversation['unread_by_admin'],
                    'message_count' => $conversation['message_count'],
                ];
            })
            ->values();

        return response()->json(['data' => $data]);
    }

    /**
     * GET /api/admin/inquiries/{memberId}
     * 특정 회원과 관리자들 간의 쪽지 내역.
     */
    public function show(Request $request, int $memberId): JsonResponse
    {
        $member = User::query()->find($memberId);

        if (! $member) {
            return response()->json(['message' => '사용자를 찾을 수 없습니다.'], 404);
        }

        $adminIds = $this->messageService->adminUserIds();

        $messages = Message::query()
            ->where(function ($q) use ($adminIds, $memberId) {
                $q->where(function ($inner) use ($adminIds, $memberId) {
                    $inner->where('sender_id', $memberId)->whereIn('receiver_id', $adminIds);
                })->orWhere(function ($inner) use ($adminIds, $memberId) {
                    $inner->whereIn('sender_id', $adminIds)->where('receiver_id', $memberId);
                });
            })
            ->with(['sender:id,name', 'receiver:id,name'])
            ->orderByDesc('id')
            ->paginate(30);

        return response()->json([
            'member' => ['id' => $member->id, 'name' => $member->name, 'email' => $member->email],
            'messages' => $messages,
        ]);
    }
}
