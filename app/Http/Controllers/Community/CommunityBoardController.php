<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\Comment;
use App\Models\PostFile;
use App\Models\Post;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CommunityBoardController extends Controller
{
    public function __construct(private readonly PermissionService $permissionService)
    {
    }

    public function board(Request $request, string $slug)
    {
        $apartmentId = max(1, (int) $request->query('apartment_id', 1));
        $keyword = trim((string) $request->query('q', ''));
        $sort = (string) $request->query('sort', 'latest');

        $board = $this->resolveBoard($slug, $apartmentId);
        $user = $request->user();

        if (! $this->permissionService->hasBoardPermission($user, $board, 'read')) {
            abort(403);
        }

        $postsQuery = Post::query()
            ->with('user')
            ->where('board_id', $board->id)
            ->where('visibility', '!=', 'deleted');

        if ($keyword !== '') {
            $postsQuery->where(function ($query) use ($keyword) {
                $query->where('title', 'like', '%'.$keyword.'%')
                    ->orWhere('body', 'like', '%'.$keyword.'%');
            });
        }

        $postsQuery->orderByDesc('is_notice');

        match ($sort) {
            'oldest' => $postsQuery->oldest(),
            'views' => $postsQuery->orderByDesc('view_count')->latest(),
            'comments' => $postsQuery->orderByDesc('comment_count')->latest(),
            default => $postsQuery->latest(),
        };

        $posts = $postsQuery
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
            'q' => $keyword,
            'sort' => $sort,
        ]);
    }

    public function showPost(Request $request, int $id)
    {
        $post = Post::query()
            ->with([
                'board',
                'user',
                'files',
                'comments' => function ($query) {
                    $query->whereNull('parent_id')
                        ->with(['user', 'children.user'])
                        ->orderBy('created_at');
                },
            ])
            ->findOrFail($id);

        $user = $request->user();

        if (! $this->permissionService->hasBoardPermission($user, $post->board, 'read')) {
            abort(403);
        }

        $post->increment('view_count');

        $rootCommentCount = $post->comments->count();
        $replyCount = $post->comments->sum(fn ($comment) => $comment->children->count());
        $bestCommentIds = $post->comments
            ->sortByDesc(fn ($comment) => $comment->children->count())
            ->take(2)
            ->pluck('id')
            ->values()
            ->all();

        return view('community.post', [
            'post' => $post,
            'apartmentId' => (int) $post->apartment_id,
            'canWrite' => $this->permissionService->hasBoardPermission($user, $post->board, 'write'),
            'canComment' => $this->permissionService->hasBoardPermission($user, $post->board, 'comment'),
            'isApartmentAdmin' => $this->permissionService->hasAdminRole($user, (int) $post->apartment_id),
            'currentUserId' => $user->id,
            'rootCommentCount' => $rootCommentCount,
            'replyCount' => $replyCount,
            'totalCommentCount' => $rootCommentCount + $replyCount,
            'bestCommentIds' => $bestCommentIds,
        ]);
    }

    public function editPost(Request $request, int $id)
    {
        $post = Post::query()->with('board', 'files')->findOrFail($id);
        $user = $request->user();

        if (! $this->permissionService->hasBoardPermission($user, $post->board, 'write')) {
            abort(403);
        }

        if (! $this->canManage($user, (int) $post->user_id, (int) $post->apartment_id)) {
            abort(403);
        }

        return view('community.post-edit', [
            'post' => $post,
            'apartmentId' => (int) $post->apartment_id,
        ]);
    }

    public function createPost(Request $request, string $slug)
    {
        $apartmentId = max(1, (int) $request->query('apartment_id', 1));
        $board = $this->resolveBoard($slug, $apartmentId);
        $user = $request->user();

        if (! $this->permissionService->hasBoardPermission($user, $board, 'write')) {
            abort(403);
        }

        return view('community.post-create', [
            'board' => $board,
            'apartmentId' => $apartmentId,
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
            'is_guest_visible' => ['nullable', 'boolean'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,gif,pdf'],
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
            'is_guest_visible' => (bool) ($data['is_guest_visible'] ?? false),
            'view_count' => 0,
            'comment_count' => 0,
        ]);

        $this->storeAttachments($request, $post);

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
            'is_guest_visible' => ['nullable', 'boolean'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,gif,pdf'],
        ]);

        $post->fill([
            'title' => $data['title'],
            'body' => $data['body'],
            'is_anonymous' => (bool) ($data['is_anonymous'] ?? false),
            'is_guest_visible' => (bool) ($data['is_guest_visible'] ?? false),
        ])->save();

        $this->storeAttachments($request, $post);

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

        foreach ($post->files as $file) {
            Storage::disk($file->disk)->delete($file->path);
            $file->delete();
        }

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
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ]);

        $parentId = null;

        if (! empty($data['parent_id'])) {
            $parent = Comment::query()->findOrFail((int) $data['parent_id']);
            if ((int) $parent->post_id !== (int) $post->id) {
                abort(422);
            }
            $parentId = (int) $parent->id;
        }

        Comment::query()->create([
            'post_id' => $post->id,
            'parent_id' => $parentId,
            'user_id' => $user->id,
            'body' => $data['body'],
            'is_anonymous' => (bool) ($data['is_anonymous'] ?? false),
        ]);

        $post->increment('comment_count');

        return back()->with('status', '댓글이 등록되었습니다.');
    }

    public function updateComment(Request $request, int $id)
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

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'is_anonymous' => ['nullable', 'boolean'],
        ]);

        $comment->fill([
            'body' => $data['body'],
            'is_anonymous' => (bool) ($data['is_anonymous'] ?? false),
        ])->save();

        return back()->with('status', '댓글이 수정되었습니다.');
    }

    public function editComment(Request $request, int $id)
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

        return view('community.comment-edit', [
            'comment' => $comment,
            'post' => $post,
            'apartmentId' => (int) $post->apartment_id,
        ]);
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

    public function downloadFile(Request $request, int $id)
    {
        $file = PostFile::query()->with('post.board')->findOrFail($id);
        $post = $file->post;

        if (! $post || ! $this->permissionService->hasBoardPermission($request->user(), $post->board, 'read')) {
            abort(403);
        }

        if (! Storage::disk($file->disk)->exists($file->path)) {
            abort(404);
        }

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    public function destroyFile(Request $request, int $id)
    {
        $file = PostFile::query()->with('post.board')->findOrFail($id);
        $post = $file->post;
        $user = $request->user();

        if (! $post || ! $this->permissionService->hasBoardPermission($user, $post->board, 'write')) {
            abort(403);
        }

        $canDelete = $user->id === (int) $file->user_id
            || $this->canManage($user, (int) $post->user_id, (int) $post->apartment_id);

        if (! $canDelete) {
            abort(403);
        }

        Storage::disk($file->disk)->delete($file->path);
        $file->delete();

        return back()->with('status', '첨부파일이 삭제되었습니다.');
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

    private function storeAttachments(Request $request, Post $post): void
    {
        if (! $request->hasFile('attachments')) {
            return;
        }

        foreach ((array) $request->file('attachments') as $uploadedFile) {
            if (! $uploadedFile) {
                continue;
            }

            $storedName = Str::uuid().'.'.$uploadedFile->getClientOriginalExtension();
            $path = $uploadedFile->storeAs('posts/'.$post->id, $storedName, 'local');

            PostFile::query()->create([
                'post_id' => $post->id,
                'user_id' => $request->user()->id,
                'original_name' => $uploadedFile->getClientOriginalName(),
                'stored_name' => $storedName,
                'disk' => 'local',
                'path' => $path,
                'mime_type' => (string) $uploadedFile->getMimeType(),
                'size' => $uploadedFile->getSize(),
            ]);
        }
    }
}
