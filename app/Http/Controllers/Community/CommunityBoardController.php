<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\Comment;
use App\Models\PostTopic;
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
        $apartmentId = $this->resolveContextApartmentId($request);
        $keyword = trim((string) $request->query('q', ''));
        $sort = (string) $request->query('sort', 'latest');
        $topic = trim((string) $request->query('topic', ''));

        $board = $this->resolveBoard($slug, $apartmentId);
        $user = $request->user();

        if (! $this->permissionService->hasBoardPermission($user, $board, 'read')) {
            abort(403);
        }

        $postsQuery = Post::query()
            ->with(['user', 'topic'])
            ->where('board_id', $board->id)
            ->where('visibility', '!=', 'deleted');

        if ($topic !== '') {
            $postsQuery->whereHas('topic', fn ($query) => $query->where('slug', $topic));
        }

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

        $topicOptions = PostTopic::query()
            ->where(function ($query) use ($apartmentId) {
                $query->whereNull('apartment_id')
                    ->orWhere('apartment_id', $apartmentId);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return view('community.board', [
            'board' => $board,
            'posts' => $posts,
            'apartmentId' => $apartmentId,
            'canWrite' => $this->canWriteInBoard($user, $board),
            'canComment' => $this->permissionService->hasBoardPermission($user, $board, 'comment'),
            'isApartmentAdmin' => $this->permissionService->hasAdminRole($user, $apartmentId),
            'currentUserId' => $user->id,
            'q' => $keyword,
            'sort' => $sort,
            'topic' => $topic,
            'topicOptions' => $topicOptions,
        ]);
    }

    public function showPost(Request $request, int $id)
    {
        $post = Post::query()
            ->with([
                'board',
                'apartment',
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

        if (! $this->permissionService->canReadPostDetail($user, $post)) {
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
            'apartmentId' => $this->resolveContextApartmentId($request, (int) $post->apartment_id),
            'canWrite' => $this->canWriteInBoard($user, $post->board),
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
        $post = Post::query()->with(['board', 'files', 'topic'])->findOrFail($id);
        $user = $request->user();

        if (! $this->canWriteInBoard($user, $post->board)) {
            abort(403);
        }

        if (! $this->canManage($user, (int) $post->user_id, (int) $post->apartment_id)) {
            abort(403);
        }

        $topicOptions = PostTopic::query()
            ->where(function ($query) use ($post) {
                $query->whereNull('apartment_id')
                    ->orWhere('apartment_id', (int) $post->apartment_id);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('community.post-edit', [
            'post' => $post,
            'apartmentId' => $this->resolveContextApartmentId($request, (int) $post->apartment_id),
            'topicOptions' => $topicOptions,
            'canUseRestrictedAudience' => $this->permissionService->hasVerifiedRole($user, (int) $post->apartment_id),
        ]);
    }

    public function createPost(Request $request, string $slug)
    {
        $apartmentId = $this->resolveContextApartmentId($request);
        $board = $this->resolveBoard($slug, $apartmentId);
        $user = $request->user();

        if (! $this->canWriteInBoard($user, $board)) {
            abort(403);
        }

        if (! $user->preferred_apartment_id) {
            return redirect('/settings?apartment_id='.$apartmentId)
                ->withErrors(['apartment_query' => '글을 작성하려면 먼저 아파트를 선택해 주세요.']);
        }

        $writerApartmentId = (int) $user->preferred_apartment_id;

        $topicOptions = PostTopic::query()
            ->where(function ($query) use ($writerApartmentId) {
                $query->whereNull('apartment_id')
                    ->orWhere('apartment_id', $writerApartmentId);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('community.post-create', [
            'board' => $board,
            'apartmentId' => $apartmentId,
            'topicOptions' => $topicOptions,
            'canUseRestrictedAudience' => $this->permissionService->hasVerifiedRole($user, $writerApartmentId),
        ]);
    }

    public function compose(Request $request)
    {
        $apartmentId = $this->resolveContextApartmentId($request);
        $user = $request->user();

        if (! $this->permissionService->hasVerifiedRole($user)) {
            abort(403);
        }

        $candidateBoards = Board::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $writableBoards = $candidateBoards
            ->filter(fn (Board $board) => $this->canWriteInBoard($user, $board))
            ->values();

        if ($writableBoards->count() === 1) {
            $targetBoard = $writableBoards->first();
            $targetApartmentId = $targetBoard->apartment_id
                ? (int) $targetBoard->apartment_id
                : ((int) ($user->preferred_apartment_id ?: $apartmentId));

            return redirect('/community/boards/'.$targetBoard->slug.'/create?apartment_id='.$targetApartmentId);
        }

        return view('community.compose', [
            'apartmentId' => $apartmentId,
            'writableBoards' => $writableBoards,
        ]);
    }

    public function storePost(Request $request, string $slug)
    {
        $apartmentId = $this->resolveContextApartmentId($request);
        $board = $this->resolveBoard($slug, $apartmentId);
        $user = $request->user()->loadMissing('preferredApartment');

        if (! $this->canWriteInBoard($user, $board)) {
            abort(403);
        }

        if (! $user->preferred_apartment_id || ! $user->preferredApartment) {
            return redirect('/settings?apartment_id='.$apartmentId)
                ->withErrors(['apartment_query' => '글을 작성하려면 먼저 아파트를 선택해 주세요.']);
        }

        $writerApartment = $user->preferredApartment;
        $writerApartmentId = (int) $writerApartment->id;

        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string'],
            'post_topic_id' => ['nullable', 'integer', 'exists:post_topics,id'],
            'new_topic' => ['nullable', 'string', 'max:60'],
            'audience_scope' => ['required', 'in:region,apartment'],
            'is_anonymous' => ['nullable', 'boolean'],
            'is_guest_visible' => ['nullable', 'boolean'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,gif,pdf'],
        ]);

        $audienceScope = (string) $data['audience_scope'];
        if (! $this->permissionService->hasVerifiedRole($user, $writerApartmentId)) {
            return back()->withErrors(['audience_scope' => '글쓰기는 인증 회원만 가능합니다.'])->withInput();
        }

        $postTopicId = $this->resolvePostTopicId(
            $request,
            $writerApartmentId,
            $data['post_topic_id'] ?? null,
            $data['new_topic'] ?? null
        );

        $post = Post::query()->create([
            'board_id' => $board->id,
            'post_topic_id' => $postTopicId,
            'apartment_id' => $writerApartmentId,
            'region_sido' => $writerApartment->sido,
            'region_sigungu' => $writerApartment->sigungu,
            'region_eupmyeondong' => $writerApartment->eupmyeondong,
            'user_id' => $user->id,
            'title' => $data['title'],
            'body' => $data['body'],
            'is_notice' => false,
            'is_anonymous' => (bool) ($data['is_anonymous'] ?? false),
            'visibility' => 'resident_only',
            'audience_scope' => $audienceScope,
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

        if (! $this->canWriteInBoard($user, $post->board)) {
            abort(403);
        }

        if (! $this->canManage($user, (int) $post->user_id, (int) $post->apartment_id)) {
            abort(403);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string'],
            'post_topic_id' => ['nullable', 'integer', 'exists:post_topics,id'],
            'new_topic' => ['nullable', 'string', 'max:60'],
            'audience_scope' => ['required', 'in:region,apartment'],
            'is_anonymous' => ['nullable', 'boolean'],
            'is_guest_visible' => ['nullable', 'boolean'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,gif,pdf'],
        ]);

        $audienceScope = (string) $data['audience_scope'];
        if (! $this->permissionService->hasVerifiedRole($request->user(), (int) $post->apartment_id)) {
            return back()->withErrors(['audience_scope' => '글쓰기는 인증 회원만 가능합니다.'])->withInput();
        }

        $postTopicId = $this->resolvePostTopicId(
            $request,
            (int) $post->apartment_id,
            $data['post_topic_id'] ?? null,
            $data['new_topic'] ?? null
        );

        $post->fill([
            'title' => $data['title'],
            'body' => $data['body'],
            'post_topic_id' => $postTopicId,
            'audience_scope' => $audienceScope,
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

        if (! $this->canWriteInBoard($user, $post->board)) {
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

        if (! $post || ! $this->canWriteInBoard($user, $post->board)) {
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
            ->where('is_active', true)
            ->where(function ($query) use ($apartmentId) {
                $query->whereNull('apartment_id')
                    ->orWhere('apartment_id', $apartmentId);
            })
            ->orderByRaw('CASE WHEN apartment_id IS NULL THEN 0 ELSE 1 END')
            ->firstOrFail();
    }

    private function resolveContextApartmentId(Request $request, ?int $fallbackApartmentId = null): int
    {
        $preferredApartmentId = (int) ($request->user()?->preferred_apartment_id ?? 0);
        if ($preferredApartmentId > 0) {
            return $preferredApartmentId;
        }

        $requestedApartmentId = max(1, (int) $request->query('apartment_id', 1));
        if ($requestedApartmentId > 0) {
            return $requestedApartmentId;
        }

        return max(1, (int) ($fallbackApartmentId ?? 1));
    }

    private function canManage(User $user, int $ownerUserId, int $apartmentId): bool
    {
        if ($user->id === $ownerUserId) {
            return true;
        }

        return $this->permissionService->hasAdminRole($user, $apartmentId);
    }

    private function canWriteInBoard(?User $user, Board $board): bool
    {
        if (! $user) {
            return false;
        }

        if (! $this->permissionService->hasBoardPermission($user, $board, 'write')) {
            return false;
        }

        return $this->permissionService->hasVerifiedRole($user, $board->apartment_id ? (int) $board->apartment_id : null);
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

    private function resolvePostTopicId(
        Request $request,
        int $apartmentId,
        mixed $topicId,
        ?string $newTopicName
    ): ?int {
        $newTopicName = trim((string) $newTopicName);

        if ($newTopicName !== '') {
            $slug = Str::slug($newTopicName, '-');

            if ($slug === '') {
                $slug = 'topic-'.Str::lower(Str::random(8));
            }

            $baseSlug = $slug;
            $suffix = 1;
            while (
                PostTopic::query()
                    ->where('apartment_id', $apartmentId)
                    ->where('slug', $slug)
                    ->exists()
            ) {
                $suffix++;
                $slug = $baseSlug.'-'.$suffix;
            }

            $topic = PostTopic::query()->create([
                'apartment_id' => $apartmentId,
                'created_by' => $request->user()->id,
                'name' => $newTopicName,
                'slug' => $slug,
            ]);

            return (int) $topic->id;
        }

        if (! $topicId) {
            return null;
        }

        $topic = PostTopic::query()
            ->where('id', (int) $topicId)
            ->where(function ($query) use ($apartmentId) {
                $query->whereNull('apartment_id')
                    ->orWhere('apartment_id', $apartmentId);
            })
            ->first();

        return $topic ? (int) $topic->id : null;
    }
}
