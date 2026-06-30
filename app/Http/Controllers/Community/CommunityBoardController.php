<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Http\Request;

class CommunityBoardController extends Controller
{
    public function __construct(private readonly PermissionService $permissionService)
    {
    }

    public function board(Request $request, string $slug)
    {
        $apartmentId = max(1, (int) $request->query('apartment_id', 1));

        $board = $this->resolveBoard($slug, $apartmentId);
        $user = $request->user();

        if (! $this->permissionService->hasBoardPermission($user, $board, 'read')) {
            abort(403);
        }

        $posts = Post::query()
            ->with('user')
            ->where('board_id', $board->id)
            ->where('visibility', '!=', 'deleted')
            ->orderByDesc('is_notice')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('community.board', [
            'board' => $board,
            'posts' => $posts,
            'apartmentId' => $apartmentId,
            'canWrite' => $this->permissionService->hasBoardPermission($user, $board, 'write'),
            'canComment' => $this->permissionService->hasBoardPermission($user, $board, 'comment'),
            'isApartmentAdmin' => $this->permissionService->hasAdminRole($user, $apartmentId),
            'currentUserId' => $user->id,
        ]);
    }

    public function showPost(Request $request, int $id)
    {
        $post = Post::query()
            ->with(['board', 'user', 'comments.user'])
            ->findOrFail($id);

        $user = $request->user();

        if (! $this->permissionService->hasBoardPermission($user, $post->board, 'read')) {
            abort(403);
        }

        $post->increment('view_count');

        return view('community.post', [
            'post' => $post,
            'apartmentId' => (int) $post->apartment_id,
            'canWrite' => $this->permissionService->hasBoardPermission($user, $post->board, 'write'),
            'canComment' => $this->permissionService->hasBoardPermission($user, $post->board, 'comment'),
            'isApartmentAdmin' => $this->permissionService->hasAdminRole($user, (int) $post->apartment_id),
            'currentUserId' => $user->id,
        ]);
    }

    public function storePost(Request $request, string $slug)
    {
        $apartmentId = max(1, (int) $request->query('apartment_id', 1));
        $board = $this->resolveBoard($slug, $apartmentId);

        if (! $this->permissionService->hasBoardPermission($request->user(), $board, 'write')) {
            abort(403);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string'],
            'is_anonymous' => ['nullable', 'boolean'],
        ]);

        $post = Post::query()->create([
            'board_id' => $board->id,
            'apartment_id' => (int) $board->apartment_id,
            'user_id' => $request->user()->id,
            'title' => $data['title'],
            'body' => $data['body'],
            'is_notice' => false,
            'is_anonymous' => (bool) ($data['is_anonymous'] ?? false),
            'visibility' => 'resident_only',
            'view_count' => 0,
            'comment_count' => 0,
        ]);

        return redirect('/community/posts/'.$post->id.'?apartment_id='.$apartmentId);
    }

    public function updatePost(Request $request, int $id)
    {
        $post = Post::query()->with('board')->findOrFail($id);
        $user = $request->user();

        if (! $this->permissionService->hasBoardPermission($user, $post->board, 'write')) {
            abort(403);
        }

        if (! $this->canManage($user, (int) $post->user_id, (int) $post->apartment_id)) {
            abort(403);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string'],
            'is_anonymous' => ['nullable', 'boolean'],
        ]);

        $post->fill([
            'title' => $data['title'],
            'body' => $data['body'],
            'is_anonymous' => (bool) ($data['is_anonymous'] ?? false),
        ])->save();

        return back()->with('status', '게시글이 수정되었습니다.');
    }

    public function destroyPost(Request $request, int $id)
    {
        $post = Post::query()->with('board')->findOrFail($id);
        $user = $request->user();

        if (! $this->permissionService->hasBoardPermission($user, $post->board, 'write')) {
            abort(403);
        }

        if (! $this->canManage($user, (int) $post->user_id, (int) $post->apartment_id)) {
            abort(403);
        }

        $slug = $post->board->slug;
        $apartmentId = (int) $post->apartment_id;
        $post->delete();

        return redirect('/community/'.$slug.'?apartment_id='.$apartmentId)
            ->with('status', '게시글이 삭제되었습니다.');
    }

    public function storeComment(Request $request, int $id)
    {
        $post = Post::query()->with('board')->findOrFail($id);
        $user = $request->user();

        if (! $this->permissionService->hasBoardPermission($user, $post->board, 'comment')) {
            abort(403);
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'is_anonymous' => ['nullable', 'boolean'],
        ]);

        Comment::query()->create([
            'post_id' => $post->id,
            'parent_id' => null,
            'user_id' => $user->id,
            'body' => $data['body'],
            'is_anonymous' => (bool) ($data['is_anonymous'] ?? false),
        ]);

        $post->increment('comment_count');

        return back()->with('status', '댓글이 등록되었습니다.');
    }

    public function destroyComment(Request $request, int $id)
    {
        $comment = Comment::query()->with('post.board')->findOrFail($id);
        $post = $comment->post;
        $user = $request->user();

        if (! $post || ! $this->permissionService->hasBoardPermission($user, $post->board, 'comment')) {
            abort(403);
        }

        if (! $this->canManage($user, (int) $comment->user_id, (int) $post->apartment_id)) {
            abort(403);
        }

        $comment->delete();

        if ($post->comment_count > 0) {
            $post->decrement('comment_count');
        }

        return back()->with('status', '댓글이 삭제되었습니다.');
    }

    private function resolveBoard(string $slug, int $apartmentId): Board
    {
        return Board::query()
            ->where('slug', $slug)
            ->where('apartment_id', $apartmentId)
            ->where('is_active', true)
            ->firstOrFail();
    }

    private function canManage(User $user, int $ownerUserId, int $apartmentId): bool
    {
        if ($user->id === $ownerUserId) {
            return true;
        }

        return $this->permissionService->hasAdminRole($user, $apartmentId);
    }
}
