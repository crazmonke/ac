<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Services\FcmMessagingService;
use App\Services\PermissionService;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function __construct(
        private readonly PermissionService $permissionService,
        private readonly FcmMessagingService $fcmMessagingService,
    ) {
    }

    public function index(int $postId)
    {
        $post = Post::query()->with('board')->findOrFail($postId);

        if (! $this->permissionService->hasBoardPermission(request()->user(), $post->board, 'read')) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $comments = Comment::query()
            ->where('post_id', $postId)
            ->orderBy('created_at')
            ->paginate(50);

        return response()->json($comments);
    }

    public function store(Request $request, int $postId)
    {
        $post = Post::query()->with('board')->findOrFail($postId);

        if (! $this->permissionService->hasBoardPermission($request->user(), $post->board, 'comment')) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $data = $request->validate([
            'parent_id' => ['nullable', 'exists:comments,id'],
            'body' => ['required', 'string'],
            'is_anonymous' => ['sometimes', 'boolean'],
        ]);

        $data['post_id'] = $post->id;
        $data['user_id'] = $request->user()->id;
        $data['is_anonymous'] = $data['is_anonymous'] ?? false;

        $comment = Comment::query()->create($data);

        $post->increment('comment_count');

        $this->fcmMessagingService->sendComment((int) $post->id, (int) $post->apartment_id, [
            'comment_id' => (string) $comment->id,
            'board_id' => (string) $post->board_id,
            'board_slug' => (string) ($post->board?->slug ?? ''),
            'title' => $post->title,
        ]);

        return response()->json(['data' => $comment], 201);
    }

    public function update(Request $request, int $id)
    {
        $comment = Comment::query()->with('post.board')->findOrFail($id);

        if (! $this->permissionService->hasBoardPermission($request->user(), $comment->post->board, 'comment')) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (! $this->canEdit($request->user()->id, $request->user(), $comment)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $data = $request->validate([
            'body' => ['required', 'string'],
            'is_anonymous' => ['sometimes', 'boolean'],
        ]);

        $comment->fill($data)->save();

        return response()->json(['data' => $comment]);
    }

    public function destroy(Request $request, int $id)
    {
        $comment = Comment::query()->with('post.board')->findOrFail($id);

        if (! $this->permissionService->hasBoardPermission($request->user(), $comment->post->board, 'comment')) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (! $this->canEdit($request->user()->id, $request->user(), $comment)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $comment->delete();

        $post = Post::query()->find($comment->post_id);
        if ($post && $post->comment_count > 0) {
            $post->decrement('comment_count');
        }

        return response()->noContent();
    }

    private function canEdit(int $userId, User $user, Comment $comment): bool
    {
        if ($comment->user_id === $userId) {
            return true;
        }

        $post = Post::query()->find($comment->post_id);

        if (! $post) {
            return false;
        }

        return $this->permissionService->hasAdminRole($user, (int) $post->apartment_id);
    }
}
