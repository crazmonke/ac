<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Services\MessageService;
use Illuminate\Http\Request;

class MessageWebController extends Controller
{
    public function __construct(
        private readonly MessageService $messageService,
    ) {
    }

    /** GET /messages — 쪽지함 (대화 목록 / 받은 쪽지 / 보낸 쪽지) */
    public function inbox(Request $request)
    {
        $user = $request->user();
        $box = in_array($request->query('box'), ['received', 'sent'], true)
            ? $request->query('box')
            : 'conversations';

        $conversations = collect();
        $messages = null;

        if ($box === 'conversations') {
            $conversations = $this->messageService->conversationsFor($user->id);
        } else {
            $messages = Message::query()
                ->where($box === 'received' ? 'receiver_id' : 'sender_id', $user->id)
                ->with(['sender:id,name', 'receiver:id,name'])
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();
        }

        return view('messages.inbox', [
            'user' => $user,
            'box' => $box,
            'conversations' => $conversations,
            'messages' => $messages,
            'apartmentId' => (int) ($user->preferred_apartment_id ?? request()->query('apartment_id', 1)),
        ]);
    }

    /** GET /messages/{peerId} — 대화 화면 */
    public function conversation(Request $request, int $peerId)
    {
        $user = $request->user();

        if ($peerId === $user->id) {
            return redirect('/messages');
        }

        $peer = User::query()->findOrFail($peerId);

        $messages = Message::query()
            ->between($user->id, $peer->id)
            ->orderByDesc('id')
            ->paginate(30);

        $this->messageService->markConversationRead($user->id, $peer->id);

        return view('messages.conversation', [
            'user' => $user,
            'peer' => $peer,
            'messages' => $messages,
            'canReply' => $this->messageService->canReceive($peer),
            'apartmentId' => (int) ($user->preferred_apartment_id ?? 1),
        ]);
    }

    /** DELETE /messages/conversations/{peerId} — 대화 전체 삭제 */
    public function deleteConversation(Request $request, int $peerId)
    {
        $user = $request->user();
        Message::query()->between($user->id, $peerId)->delete();
        return response()->json(['ok' => true]);
    }

    /** GET /messages/compose — 쪽지 작성 (?to=userId 로 수신자 미리 선택) */
    public function compose(Request $request)
    {
        $user = $request->user();
        $recipient = null;

        $toId = (int) $request->query('to', 0);
        if ($toId > 0 && $toId !== $user->id) {
            $candidate = User::query()->find($toId);
            if ($candidate && $this->messageService->canReceive($candidate)) {
                $recipient = $candidate;
            }
        }

        if ($request->query('to') === 'admin') {
            $recipient = $this->messageService->primaryAdmin();
        }

        return view('messages.compose', [
            'user' => $user,
            'recipient' => $recipient,
            'adminUser' => $this->messageService->primaryAdmin(),
            'apartmentId' => (int) ($user->preferred_apartment_id ?? 1),
        ]);
    }

    /** POST /messages — 쪽지 발송 */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'receiver_id' => ['required', 'integer', 'exists:users,id'],
            'content' => ['required', 'string', 'max:2000'],
        ], [
            'receiver_id.required' => '받는 사람을 선택해 주세요.',
            'content.required' => '쪽지 내용을 입력해 주세요.',
            'content.max' => '쪽지는 2000자 이내로 작성해 주세요.',
        ]);

        $sender = $request->user();
        $receiverId = (int) $validated['receiver_id'];

        if ($receiverId === $sender->id) {
            return back()->withErrors(['receiver_id' => '자기 자신에게는 쪽지를 보낼 수 없습니다.'])->withInput();
        }

        $receiver = User::query()->findOrFail($receiverId);

        if (! $this->messageService->canReceive($receiver)) {
            return back()->withErrors(['receiver_id' => '쪽지를 받을 수 없는 사용자입니다.'])->withInput();
        }

        $this->messageService->send($sender, $receiver, $validated['content']);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true]);
        }

        return redirect('/messages/'.$receiver->id)->with('status', '쪽지를 보냈습니다.');
    }

    /** GET /messages/users/search?q= — 수신자 검색 */
    public function searchUsers(Request $request)
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:50'],
        ]);

        $keyword = trim($validated['q']);

        $users = User::query()
            ->where('id', '!=', $request->user()->id)
            ->where('access_allowed', true)
            ->whereNull('withdrawn_at')
            ->where('name', 'like', $keyword.'%')
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name']);

        return response()->json(['data' => $users]);
    }
}
