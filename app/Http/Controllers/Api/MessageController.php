<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(
        private readonly MessageService $messageService,
    ) {
    }

    /**
     * GET /api/messages
     * box=conversations(기본)|received|sent
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $box = $request->query('box', 'conversations');

        if ($box === 'received' || $box === 'sent') {
            $messages = Message::query()
                ->where($box === 'received' ? 'receiver_id' : 'sender_id', $user->id)
                ->with(['sender:id,name', 'receiver:id,name'])
                ->orderByDesc('id')
                ->paginate(20);

            return response()->json($messages);
        }

        $conversations = $this->messageService->conversationsFor($user->id)
            ->map(fn (array $conversation) => [
                'conversation_id' => $conversation['peer_id'],
                'peer' => [
                    'id' => $conversation['peer']->id,
                    'name' => $conversation['peer']->name,
                ],
                'last_message' => [
                    'id' => $conversation['last_message']->id,
                    'sender_id' => $conversation['last_message']->sender_id,
                    'content' => $conversation['last_message']->content,
                    'created_at' => $conversation['last_message']->created_at,
                ],
                'unread_count' => $conversation['unread_count'],
            ]);

        return response()->json(['data' => $conversations]);
    }

    /**
     * POST /api/messages
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'receiver_id' => ['required', 'integer', 'exists:users,id'],
            'content' => ['required', 'string', 'max:2000'],
            'parent_message_id' => ['nullable', 'integer', 'exists:messages,id'],
        ]);

        $sender = $request->user();

        if ((int) $validated['receiver_id'] === $sender->id) {
            return response()->json(['message' => '자기 자신에게는 쪽지를 보낼 수 없습니다.'], 422);
        }

        $receiver = User::query()->findOrFail((int) $validated['receiver_id']);

        if (! $this->messageService->canReceive($receiver)) {
            return response()->json(['message' => '쪽지를 받을 수 없는 사용자입니다.'], 422);
        }

        $message = $this->messageService->send(
            $sender,
            $receiver,
            $validated['content'],
            isset($validated['parent_message_id']) ? (int) $validated['parent_message_id'] : null
        );

        return response()->json(['data' => $message], 201);
    }

    /**
     * GET /api/messages/{conversationId}
     * conversationId = 상대 사용자 id. 조회 시 받은 쪽지는 읽음 처리된다.
     */
    public function show(Request $request, int $conversationId): JsonResponse
    {
        $user = $request->user();
        $peer = User::query()->find($conversationId);

        if (! $peer) {
            return response()->json(['message' => '사용자를 찾을 수 없습니다.'], 404);
        }

        $messages = Message::query()
            ->between($user->id, $peer->id)
            ->with(['sender:id,name', 'receiver:id,name'])
            ->orderByDesc('id')
            ->paginate(30);

        $this->messageService->markConversationRead($user->id, $peer->id);

        return response()->json([
            'peer' => ['id' => $peer->id, 'name' => $peer->name],
            'messages' => $messages,
        ]);
    }

    /**
     * PUT /api/messages/{messageId}/read
     */
    public function markAsRead(Request $request, int $messageId): JsonResponse
    {
        $message = Message::query()->findOrFail($messageId);

        if ($message->receiver_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($message->read_at === null) {
            $message->update(['read_at' => now()]);
        }

        return response()->json(['data' => $message->fresh()]);
    }

    /**
     * GET /api/messages/unread-count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'unread_count' => Message::unreadCountFor($request->user()->id),
        ]);
    }
}
