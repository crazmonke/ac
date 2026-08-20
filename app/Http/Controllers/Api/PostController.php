<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\BlockedUser;
use App\Models\Post;
use App\Models\PostTemplate;
use App\Services\PermissionService;
use App\Services\FcmMessagingService;
use App\Services\PostTemplateRenderer;
use Illuminate\Http\Request;
use App\Models\User;

class PostController extends Controller
{
    public function __construct(
        private readonly PermissionService $permissionService,
        private readonly FcmMessagingService $fcmMessagingService,
        private readonly PostTemplateRenderer $postTemplateRenderer,
    ) {
    }

    public function index(int $boardId)
    {
        $posts = Post::query()
            ->where('board_id', $boardId)
            ->where('visibility', '!=', 'deleted')
            ->whereNotIn('user_id', function ($query) {
                $query->select('blocked_id')
                    ->from('blocked_users')
                    ->where('blocker_id', request()->user()->id);
            })
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('post_hides')
                    ->whereColumn('post_hides.post_id', 'posts.id')
                    ->where('post_hides.user_id', request()->user()->id);
            })
            ->latest()
            ->paginate(20);

        return response()->json($posts);
    }

    public function store(Request $request, int $boardId)
    {
        $board = Board::query()->findOrFail($boardId);

        $templateSubmission = $this->resolveTemplateSubmission($request, $board);

        $data = $request->validate([
            'title' => [$templateSubmission ? 'nullable' : 'required', 'string', 'max:160'],
            'body' => [$templateSubmission ? 'nullable' : 'required', 'string'],
            'is_notice' => ['sometimes', 'boolean'],
            'is_anonymous' => ['sometimes', 'boolean'],
            'visibility' => ['sometimes', 'in:public,resident_only,deleted'],
        ]);

        if ($templateSubmission) {
            [$template, $answers, $data['title'], $data['body']] = $templateSubmission;
            $data['post_template_id'] = $template->id;
            $data['template_answers'] = $answers;
        }

        $data['user_id'] = $request->user()->id;
        $data['board_id'] = $board->id;
        $data['apartment_id'] = (int) $board->apartment_id;
        $data['is_notice'] = $data['is_notice'] ?? false;
        $data['is_anonymous'] = $data['is_anonymous'] ?? false;
        $data['visibility'] = $data['visibility'] ?? 'resident_only';

        $post = Post::query()->create($data);

        if ($data['is_notice']) {
            $this->fcmMessagingService->sendNotice((int) $post->id, (int) $post->apartment_id, [
                'board_id' => (string) $board->id,
                'board_slug' => (string) $board->slug,
                'title' => $post->title,
            ]);
        } else {
            $this->fcmMessagingService->sendNewPost((int) $post->id, (int) $post->apartment_id, [
                'board_id' => (string) $board->id,
                'board_slug' => (string) $board->slug,
                'title' => $post->title,
            ]);
        }

        return response()->json(['data' => $post], 201);
    }

    public function hide(Request $request, int $id)
    {
        $post = Post::query()->findOrFail($id);

        \App\Models\PostHide::query()->firstOrCreate([
            'user_id' => $request->user()->id,
            'post_id' => $post->id,
        ]);

        return response()->json(['message' => 'Post hidden from your feed.']);
    }

    public function show(Request $request, int $id)
    {
        $post = Post::query()->with(['comments', 'files', 'board'])->findOrFail($id);

        if (! $this->permissionService->hasBoardPermission($request->user(), $post->board, 'read')) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json(['data' => $post]);
    }

    public function update(Request $request, int $id)
    {
        $post = Post::query()->with('board')->findOrFail($id);

        if (! $this->permissionService->hasBoardPermission($request->user(), $post->board, 'write')) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (! $this->canEdit($request->user()->id, $request->user(), $post)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $templateSubmission = $this->resolveTemplateSubmission($request, $post->board);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:160'],
            'body' => ['sometimes', 'string'],
            'is_notice' => ['sometimes', 'boolean'],
            'is_anonymous' => ['sometimes', 'boolean'],
            'visibility' => ['sometimes', 'in:public,resident_only,deleted'],
        ]);

        if ($templateSubmission) {
            [$template, $answers, $data['title'], $data['body']] = $templateSubmission;
            $data['post_template_id'] = $template->id;
            $data['template_answers'] = $answers;
        }

        $post->fill($data)->save();

        return response()->json(['data' => $post]);
    }

    public function destroy(Request $request, int $id)
    {
        $post = Post::query()->with('board')->findOrFail($id);

        if (! $this->permissionService->hasBoardPermission($request->user(), $post->board, 'write')) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (! $this->canEdit($request->user()->id, $request->user(), $post)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $post->delete();

        return response()->noContent();
    }

    private function canEdit(int $userId, User $user, Post $post): bool
    {
        if ($post->user_id === $userId) {
            return true;
        }

        return $this->permissionService->hasAdminRole($user, (int) $post->apartment_id);
    }

    /**
     * 요청에 설문형 템플릿 답변이 포함된 경우 검증 후
     * [템플릿, 정규화 답변, 생성된 제목, 생성된 본문]을 반환한다.
     * 제목/본문은 항상 서버가 답변으로부터 생성한다 (클라이언트 값 무시).
     *
     * @return array{0: PostTemplate, 1: array, 2: string, 3: string}|null
     */
    private function resolveTemplateSubmission(Request $request, Board $board): ?array
    {
        if (! $request->filled('post_template_id')) {
            return null;
        }

        $validated = $request->validate([
            'post_template_id' => ['required', 'integer', 'exists:post_templates,id'],
            'template_answers' => ['required', 'array'],
        ]);

        $template = PostTemplate::query()->active()->find((int) $validated['post_template_id']);
        if (! $template || ! $template->isAvailableForBoard((string) $board->slug)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'post_template_id' => ['이 게시판에서 사용할 수 없는 템플릿입니다.'],
            ]);
        }

        $answers = $this->postTemplateRenderer->validateAnswers($template, $validated['template_answers']);
        $rendered = $this->postTemplateRenderer->render($template, $answers);

        return [$template, $answers, $rendered['title'], $rendered['body_html']];
    }
}
