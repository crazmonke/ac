<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Board;
use App\Models\Comment;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use App\Models\PostLike;
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
        $selectedTopicName = $topic !== ''
            ? PostTopic::query()->where('slug', $topic)->value('name')
            : null;

        if (! $this->permissionService->hasBoardPermission($user, $board, 'read')) {
            abort(403);
        }

        $postsQuery = Post::query()
            ->with(['user', 'topic', 'files'])
            ->where('board_id', $board->id)
            ->where('visibility', '!=', 'deleted');

        if ($topic !== '') {
            $postsQuery->whereHas('topic', function ($query) use ($topic, $selectedTopicName) {
                $query->where('slug', $topic);

                if ($selectedTopicName) {
                    $query->orWhere('name', $selectedTopicName);
                }
            });
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

        $postAccessMap = [];
        foreach ($posts as $post) {
            $canRead = $this->permissionService->canReadPostDetail($user, $post);
            $postAccessMap[(int) $post->id] = [
                'can_read' => $canRead,
                'access_label' => $this->permissionService->resolvePostAccessLabel($user, $post),
                'url' => $canRead
                    ? '/community/posts/'.$post->id.'?apartment_id='.$apartmentId
                    : '/posts/'.$post->id.'?apartment_id='.(int) $post->apartment_id,
                'thumbnail_url' => $canRead ? $this->resolvePostThumbnailUrl($post) : null,
            ];
        }

        $topicOptions = $this->loadDistinctTopicOptions($apartmentId);

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
            'postAccessMap' => $postAccessMap,
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
                'poll.options.votes',
                'comments' => function ($query) {
                    $query->whereNull('parent_id')
                        ->with(['user', 'children.user'])
                        ->orderBy('created_at');
                },
            ])
            ->findOrFail($id);

        $user = $request->user();

        if (! $this->permissionService->canReadPostDetail($user, $post)) {
            return redirect('/posts/'.$post->id.'?apartment_id='.(int) $post->apartment_id);
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

        $userVoteOptionIds = collect();
        $pollTotalVotes = 0;
        if ($post->poll) {
            $userVoteOptionIds = PollVote::query()
                ->where('poll_id', $post->poll->id)
                ->where('user_id', $user->id)
                ->pluck('poll_option_id');
            $pollTotalVotes = (int) $post->poll->options->sum('vote_count');
        }

        $likeCount = (int) PostLike::query()->where('post_id', $post->id)->count();
        $likedByMe = (bool) PostLike::query()
            ->where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->exists();

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
            'userVoteOptionIds' => $userVoteOptionIds,
            'pollTotalVotes' => $pollTotalVotes,
            'likeCount' => $likeCount,
            'likedByMe' => $likedByMe,
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

        $topicOptions = $this->loadDistinctTopicOptions((int) $post->apartment_id);

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
        $user = $request->user()->loadMissing(['preferredApartment', 'preferredResidenceComplex']);
        $requestedScope = (string) $request->query('scope', '');
        $requestedTopicSlug = trim((string) $request->query('topic', ''));

        if (! $this->canWriteInBoard($user, $board)) {
            abort(403);
        }

        $writerApartmentId = (int) ($user->preferred_apartment_id ?? 0);
        $writerResidenceComplexId = (int) ($user->preferred_residence_complex_id ?? 0);

        if ($writerApartmentId <= 0 && $writerResidenceComplexId <= 0) {
            return redirect('/settings?apartment_id='.$apartmentId)
                ->withErrors(['apartment_query' => '글을 작성하려면 먼저 공동주택를 선택해 주세요.']);
        }

        $topicOptions = $this->loadDistinctTopicOptions($writerApartmentId > 0 ? $writerApartmentId : $apartmentId);
        $canUseRestrictedAudience = $writerResidenceComplexId > 0
            ? $this->permissionService->hasVerifiedResidenceComplex($user, $writerResidenceComplexId)
            : $this->permissionService->hasVerifiedRole($user, $writerApartmentId);

        $defaultAudienceScope = $requestedScope === 'apartment' ? 'apartment' : 'region';
        if (! $canUseRestrictedAudience && $defaultAudienceScope === 'apartment') {
            $defaultAudienceScope = 'region';
        }

        $defaultTopicId = null;
        if ($requestedTopicSlug !== '') {
            $defaultTopic = $topicOptions->first(fn ($topic) => (string) $topic->slug === $requestedTopicSlug);
            if ($defaultTopic) {
                $defaultTopicId = (int) $defaultTopic->id;
            }
        }

        return view('community.post-create', [
            'board' => $board,
            'apartmentId' => $apartmentId,
            'topicOptions' => $topicOptions,
            'canUseRestrictedAudience' => $canUseRestrictedAudience,
            'defaultAudienceScope' => $defaultAudienceScope,
            'defaultTopicId' => $defaultTopicId,
            'requestedScope' => $requestedScope,
            'requestedTopicSlug' => $requestedTopicSlug,
        ]);
    }

    public function compose(Request $request)
    {
        $apartmentId = $this->resolveContextApartmentId($request);
        $user = $request->user();
        $scope = (string) $request->query('scope', '');
        $topic = trim((string) $request->query('topic', ''));

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

            $query = ['apartment_id' => $targetApartmentId];
            if (in_array($scope, ['all', 'region', 'apartment'], true)) {
                $query['scope'] = $scope;
            }
            if ($topic !== '') {
                $query['topic'] = $topic;
            }

            return redirect('/community/boards/'.$targetBoard->slug.'/create?'.http_build_query($query));
        }

        return view('community.compose', [
            'apartmentId' => $apartmentId,
            'writableBoards' => $writableBoards,
            'scope' => $scope,
            'topic' => $topic,
        ]);
    }

    public function storePost(Request $request, string $slug)
    {
        $apartmentId = $this->resolveContextApartmentId($request);
        $board = $this->resolveBoard($slug, $apartmentId);
        $user = $request->user()->loadMissing(['preferredApartment', 'preferredResidenceComplex']);

        if (! $this->canWriteInBoard($user, $board)) {
            abort(403);
        }

        $writerApartment = $user->preferredApartment;

        $writerResidenceComplex = $user->preferredResidenceComplex;
        $writerResidenceComplexId = (int) ($writerResidenceComplex?->id ?? 0);

        if (! $writerApartment && $writerResidenceComplexId <= 0) {
            return redirect('/settings?apartment_id='.$apartmentId)
                ->withErrors(['apartment_query' => '글을 작성하려면 먼저 공동주택를 선택해 주세요.']);
        }

        $writerApartmentId = (int) ($writerApartment?->id ?? 0);
        $storageApartmentId = $writerApartmentId > 0 ? $writerApartmentId : $apartmentId;

        $rules = [
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string'],
            'post_topic_id' => ['nullable', 'integer', 'exists:post_topics,id'],
            'new_topic' => ['nullable', 'string', 'max:60'],
            'audience_scope' => ['required', 'in:region,apartment'],
            'poll_allow_multiple' => ['nullable', 'boolean'],
            'poll_results_public' => ['nullable', 'boolean'],
            'is_anonymous' => ['nullable', 'boolean'],
            'is_guest_visible' => ['nullable', 'boolean'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:51200', 'mimes:jpg,jpeg,png,gif,pdf,mp4,mov,webm'],
        ];

        if ($board->board_type === 'poll') {
            $rules['poll_question'] = ['required', 'string', 'max:255'];
            $rules['poll_options'] = ['required', 'string', 'max:4000'];
        }

        $data = $request->validate($rules);

        $audienceScope = (string) $data['audience_scope'];
        $canUseApartmentScope = $writerResidenceComplexId > 0
            ? $this->permissionService->hasVerifiedResidenceComplex($user, $writerResidenceComplexId)
            : $this->permissionService->hasVerifiedRole($user, $writerApartmentId);

        if ($audienceScope === 'apartment' && ! $canUseApartmentScope) {
            return back()->withErrors(['audience_scope' => '공동주택 공개 글은 인증 회원만 작성할 수 있습니다.'])->withInput();
        }

        $postTopicId = $this->resolvePostTopicId(
            $request,
            $storageApartmentId,
            $data['post_topic_id'] ?? null,
            $data['new_topic'] ?? null
        );

        $region = $writerApartment
            ? [
                'sido' => $writerApartment->sido,
                'sigungu' => $writerApartment->sigungu,
                'dong' => $writerApartment->eupmyeondong,
            ]
            : $this->extractRegionFromAddress(
                (string) ($writerResidenceComplex?->road_address ?: ''),
                (string) ($writerResidenceComplex?->jibun_address ?: '')
            );

        $post = Post::query()->create([
            'board_id' => $board->id,
            'post_topic_id' => $postTopicId,
            'apartment_id' => $storageApartmentId,
            'residence_complex_id' => $writerResidenceComplexId > 0 ? $writerResidenceComplexId : null,
            'region_sido' => $region['sido'],
            'region_sigungu' => $region['sigungu'],
            'region_eupmyeondong' => $region['dong'],
            'user_id' => $user->id,
            'title' => $data['title'],
            'body' => $this->sanitizeEditorHtml((string) $data['body']),
            'is_notice' => false,
            'is_anonymous' => (bool) ($data['is_anonymous'] ?? false),
            'visibility' => 'resident_only',
            'audience_scope' => $audienceScope,
            'is_guest_visible' => (bool) ($data['is_guest_visible'] ?? false),
            'view_count' => 0,
            'comment_count' => 0,
        ]);

        $this->storeAttachments($request, $post);

        if ($board->board_type === 'poll') {
            $this->syncPollFromRequest($request, $post, $data);
        }

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

        $rules = [
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string'],
            'post_topic_id' => ['nullable', 'integer', 'exists:post_topics,id'],
            'new_topic' => ['nullable', 'string', 'max:60'],
            'audience_scope' => ['required', 'in:region,apartment'],
            'is_anonymous' => ['nullable', 'boolean'],
            'is_guest_visible' => ['nullable', 'boolean'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:51200', 'mimes:jpg,jpeg,png,gif,pdf,mp4,mov,webm'],
        ];

        if ($post->board->board_type === 'poll') {
            $rules['poll_question'] = ['required', 'string', 'max:255'];
            $rules['poll_options'] = ['required', 'string', 'max:4000'];
        }

        $data = $request->validate($rules);

        $audienceScope = (string) $data['audience_scope'];
        $canUseApartmentScope = (int) ($post->residence_complex_id ?? 0) > 0
            ? $this->permissionService->hasVerifiedResidenceComplex($request->user(), (int) $post->residence_complex_id)
            : $this->permissionService->hasVerifiedRole($request->user(), (int) $post->apartment_id);

        if ($audienceScope === 'apartment' && ! $canUseApartmentScope) {
            return back()->withErrors(['audience_scope' => '공동주택 공개 글은 인증 회원만 작성할 수 있습니다.'])->withInput();
        }

        $postTopicId = $this->resolvePostTopicId(
            $request,
            (int) $post->apartment_id,
            $data['post_topic_id'] ?? null,
            $data['new_topic'] ?? null
        );

        $post->fill([
            'title' => $data['title'],
            'body' => $this->sanitizeEditorHtml((string) $data['body']),
            'post_topic_id' => $postTopicId,
            'audience_scope' => $audienceScope,
            'is_anonymous' => (bool) ($data['is_anonymous'] ?? false),
            'is_guest_visible' => (bool) ($data['is_guest_visible'] ?? false),
        ])->save();

        $this->storeAttachments($request, $post);

        if ($post->board->board_type === 'poll') {
            $this->syncPollFromRequest($request, $post, $data);
        }

        return redirect('/community/posts/'.$post->id.'?apartment_id='.(int) $post->apartment_id)
            ->with('status', '게시글이 수정되었습니다.');
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

        $apartmentId = (int) $post->apartment_id;
        $scope = in_array($post->audience_scope, ['region', 'apartment'], true)
            ? $post->audience_scope
            : 'region';

        foreach ($post->files as $file) {
            Storage::disk($file->disk)->delete($file->path);
            $file->delete();
        }

        $post->delete();

        return redirect('/community?scope='.$scope.'&apartment_id='.$apartmentId)
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

    public function likePost(Request $request, int $id)
    {
        $post = Post::query()->with('board')->findOrFail($id);

        if (! $this->permissionService->canReadPostDetail($request->user(), $post)) {
            abort(403);
        }

        PostLike::query()->firstOrCreate([
            'post_id' => $post->id,
            'user_id' => $request->user()->id,
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'liked' => true,
                'like_count' => (int) PostLike::query()->where('post_id', $post->id)->count(),
            ]);
        }

        return back();
    }

    public function unlikePost(Request $request, int $id)
    {
        $post = Post::query()->with('board')->findOrFail($id);

        if (! $this->permissionService->canReadPostDetail($request->user(), $post)) {
            abort(403);
        }

        PostLike::query()
            ->where('post_id', $post->id)
            ->where('user_id', $request->user()->id)
            ->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'liked' => false,
                'like_count' => (int) PostLike::query()->where('post_id', $post->id)->count(),
            ]);
        }

        return back();
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

        if (! $post || ! $this->permissionService->canReadPostDetail($request->user(), $post)) {
            abort(403);
        }

        if (! Storage::disk($file->disk)->exists($file->path)) {
            abort(404);
        }

        $disk = Storage::disk($file->disk);
        $absolutePath = $disk->path($file->path);
        $mime = trim((string) ($file->mime_type ?? ''));

        if ($mime === '') {
            $mime = (string) mime_content_type($absolutePath);
        }

        if ($mime === '') {
            $mime = 'application/octet-stream';
        }

        return response()->file($absolutePath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.addslashes((string) $file->original_name).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
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

    public function storePollVote(Request $request, int $id)
    {
        $post = Post::query()->with(['board', 'poll.options'])->findOrFail($id);
        $user = $request->user();

        if ($post->board->board_type !== 'poll' || ! $post->poll) {
            abort(404);
        }

        if (! $this->permissionService->canReadPostDetail($user, $post)) {
            abort(403);
        }

        $data = $request->validate([
            'poll_option_ids' => ['required', 'array', 'min:1'],
            'poll_option_ids.*' => ['integer', 'exists:poll_options,id'],
        ]);

        if (! $post->poll->allow_multiple && count($data['poll_option_ids']) > 1) {
            return back()->withErrors(['poll_option_ids' => '단일 선택 투표입니다.'])->withInput();
        }

        $options = $post->poll->options->keyBy('id');
        foreach ($data['poll_option_ids'] as $optionId) {
            if (! $options->has((int) $optionId)) {
                abort(422);
            }
        }

        $existingVotes = PollVote::query()
            ->where('poll_id', $post->poll->id)
            ->where('user_id', $user->id)
            ->get();

        foreach ($existingVotes as $existingVote) {
            $existingVote->option()->decrement('vote_count');
            $existingVote->delete();
        }

        foreach ($data['poll_option_ids'] as $optionId) {
            PollVote::query()->create([
                'poll_id' => $post->poll->id,
                'poll_option_id' => (int) $optionId,
                'user_id' => $user->id,
            ]);
            PollOption::query()->where('id', (int) $optionId)->increment('vote_count');
        }

        return back()->with('status', '투표가 완료되었습니다.');
    }

    public function uploadEditorPhoto(Request $request)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:51200', 'mimes:jpg,jpeg,png,gif,webp,heic,heif,avif'],
        ]);

        $uploadedFile = $data['file'];
        $dateSegment = now()->format('Y/m');
        $targetDirectory = public_path('uploads/editor-images/'.$dateSegment);

        if (! is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0755, true);
        }

        $extension = Str::lower((string) $uploadedFile->getClientOriginalExtension());
        $safeExtension = $extension !== '' ? $extension : 'jpg';
        $storedName = Str::uuid().'.'.$safeExtension;

        $uploadedFile->move($targetDirectory, $storedName);
        $this->mirrorUploadToDocroot('editor-images/'.$dateSegment.'/'.$storedName);

        return response()->json([
            'url' => '/uploads/editor-images/'.$dateSegment.'/'.$storedName,
            'name' => (string) $uploadedFile->getClientOriginalName(),
        ]);
    }

    public function uploadEditorVideo(Request $request)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:512000', 'mimes:mp4,mov,webm,m4v'],
        ]);

        $uploadedFile = $data['file'];
        $dateSegment = now()->format('Y/m');
        $targetDirectory = public_path('uploads/editor-videos/'.$dateSegment);

        if (! is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0755, true);
        }

        $maxOutputBytes = 100 * 1024 * 1024;
        $originalExtension = Str::lower((string) $uploadedFile->getClientOriginalExtension());
        $tempName = Str::uuid().'.'.($originalExtension !== '' ? $originalExtension : 'mp4');
        $tempPath = $targetDirectory.'/'.$tempName;
        $uploadedFile->move($targetDirectory, $tempName);

        $storedName = Str::uuid().'.mp4';
        $finalPath = $targetDirectory.'/'.$storedName;
        $outputPath = $tempPath;
        $compressed = false;

        if (@filesize($tempPath) > $maxOutputBytes) {
            $compressed = $this->compressVideoToTargetSize($tempPath, $finalPath, $maxOutputBytes);
            if (! $compressed) {
                @unlink($tempPath);
                return response()->json([
                    'message' => '영상 자동 압축에 실패했습니다. 10MB 이하 영상으로 다시 시도해 주세요.',
                ], 422);
            }

            $outputPath = $finalPath;
            @unlink($tempPath);
        } else {
            $safeExtension = $originalExtension !== '' ? $originalExtension : 'mp4';
            $storedName = Str::uuid().'.'.$safeExtension;
            $finalPath = $targetDirectory.'/'.$storedName;
            rename($tempPath, $finalPath);
            $outputPath = $finalPath;
        }

        if (@filesize($outputPath) > $maxOutputBytes) {
            @unlink($outputPath);
            return response()->json([
                'message' => '압축 후에도 10MB를 초과합니다. 더 짧거나 낮은 해상도의 영상을 선택해 주세요.',
            ], 422);
        }

        $this->mirrorUploadToDocroot('editor-videos/'.$dateSegment.'/'.$storedName);

        return response()->json([
            'url' => '/uploads/editor-videos/'.$dateSegment.'/'.$storedName,
            'name' => (string) $uploadedFile->getClientOriginalName(),
            'type' => 'video',
        ]);
    }

    private function mirrorUploadToDocroot(string $relativeUploadPath): void
    {
        $relativeUploadPath = ltrim($relativeUploadPath, '/');
        $publicSource = public_path('uploads/'.$relativeUploadPath);
        $docrootTarget = base_path('uploads/'.$relativeUploadPath);

        if ($publicSource === $docrootTarget || ! is_file($publicSource)) {
            return;
        }

        $targetDirectory = dirname($docrootTarget);
        if (! is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0755, true);
        }

        @copy($publicSource, $docrootTarget);
    }

    private function compressVideoToTargetSize(string $sourcePath, string $targetPath, int $targetBytes): bool
    {
        $ffmpeg = trim((string) @shell_exec('command -v ffmpeg'));
        if ($ffmpeg === '') {
            return false;
        }

        $profiles = [
            ['crf' => 30, 'maxrate' => '1600k', 'bufsize' => '3200k', 'audio' => '96k', 'scale' => 'min(1280,iw):-2'],
            ['crf' => 34, 'maxrate' => '1100k', 'bufsize' => '2200k', 'audio' => '72k', 'scale' => 'min(960,iw):-2'],
            ['crf' => 37, 'maxrate' => '850k', 'bufsize' => '1700k', 'audio' => '64k', 'scale' => 'min(854,iw):-2'],
        ];

        foreach ($profiles as $profile) {
            @unlink($targetPath);

            $command = sprintf(
                '%s -y -i %s -vf %s -c:v libx264 -preset veryfast -crf %d -maxrate %s -bufsize %s -pix_fmt yuv420p -c:a aac -b:a %s -movflags +faststart %s 2>&1',
                escapeshellarg($ffmpeg),
                escapeshellarg($sourcePath),
                escapeshellarg('scale='.$profile['scale']),
                (int) $profile['crf'],
                escapeshellarg($profile['maxrate']),
                escapeshellarg($profile['bufsize']),
                escapeshellarg($profile['audio']),
                escapeshellarg($targetPath)
            );

            @shell_exec($command);

            if (is_file($targetPath) && @filesize($targetPath) > 0 && @filesize($targetPath) <= $targetBytes) {
                return true;
            }
        }

        return is_file($targetPath) && @filesize($targetPath) > 0 && @filesize($targetPath) <= $targetBytes;
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

        return $this->permissionService->hasBoardPermission($user, $board, 'write');
    }

    private function loadDistinctTopicOptions(int $apartmentId)
    {
        return PostTopic::query()
            ->where(function ($query) use ($apartmentId) {
                $query->whereNull('apartment_id')
                    ->orWhere('apartment_id', $apartmentId);
            })
            ->orderByRaw('CASE WHEN apartment_id = ? THEN 0 ELSE 1 END', [$apartmentId])
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
                ->unique(fn ($topic) => mb_strtolower(trim((string) $topic->name)))
            ->values();
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

    private function resolvePostThumbnailUrl(Post $post): ?string
    {
        $imageFile = $post->files
            ->first(fn (PostFile $file) => Str::startsWith(Str::lower((string) $file->mime_type), 'image/'));

        if ($imageFile) {
            return '/community/files/'.$imageFile->id;
        }

        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', (string) $post->body, $matches)) {
            return $matches[1] ?? null;
        }

        return null;
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

    private function extractRegionFromAddress(string $roadAddress, string $jibunAddress = ''): array
    {
        $address = trim($roadAddress !== '' ? $roadAddress : $jibunAddress);

        if ($address === '') {
            return ['sido' => null, 'sigungu' => null, 'dong' => null];
        }

        $tokens = preg_split('/\s+/u', str_replace(',', ' ', $address)) ?: [];
        $tokens = array_values(array_filter(array_map(function ($token) {
            $token = trim((string) $token);

            return $token !== '' ? $token : null;
        }, $tokens)));
        $tokens = array_values(array_filter($tokens, fn ($token) => $token !== '대한민국'));

        $sido = null;
        $cityToken = null;
        $districtToken = null;
        $dong = null;

        foreach ($tokens as $token) {
            if ($sido === null && preg_match('/(도|특별시|광역시|자치시)$/u', $token)) {
                $sido = $token;
                continue;
            }

            if ($cityToken === null && preg_match('/시$/u', $token)) {
                $cityToken = $token;
                continue;
            }

            if ($districtToken === null && preg_match('/(구|군)$/u', $token)) {
                $districtToken = $token;
                continue;
            }

            if ($dong === null && preg_match('/(동|읍|면|가)$/u', $token)) {
                $dong = $token;
            }
        }

        if ($sido === null && $cityToken !== null) {
            $sido = $cityToken;
        }

        $sigungu = null;
        if ($cityToken !== null && $districtToken !== null) {
            $sigungu = trim($cityToken . ' ' . $districtToken);
        } elseif ($districtToken !== null) {
            $sigungu = $districtToken;
        } elseif ($cityToken !== null) {
            $sigungu = $cityToken;
        }

        return [
            'sido' => $sido,
            'sigungu' => $sigungu,
            'dong' => $dong,
        ];
    }

    private function syncPollFromRequest(Request $request, Post $post, array $data): void
    {
        $question = trim((string) ($data['poll_question'] ?? ''));
        $rawOptions = trim((string) ($data['poll_options'] ?? ''));

        if ($question === '' || $rawOptions === '') {
            abort(422, '투표 질문과 선택지를 입력해 주세요.');
        }

        $optionLabels = collect(preg_split('/\r\n|\r|\n/', $rawOptions) ?: [])
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->take(10)
            ->values();

        if ($optionLabels->count() < 2) {
            abort(422, '투표 선택지는 최소 2개 이상이어야 합니다.');
        }

        $poll = $post->poll()->updateOrCreate(
            ['post_id' => $post->id],
            [
                'question' => $question,
                'allow_multiple' => (bool) ($data['poll_allow_multiple'] ?? false),
                'results_public' => (bool) ($data['poll_results_public'] ?? true),
            ]
        );

        $poll->options()->delete();
        foreach ($optionLabels as $index => $label) {
            $poll->options()->create([
                'label' => $label,
                'sort_order' => $index,
                'vote_count' => 0,
            ]);
        }
    }

    private function sanitizeEditorHtml(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        if (! class_exists(\DOMDocument::class)) {
            return strip_tags($html);
        }

        $allowedTags = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'span', 'ul', 'ol', 'li', 'blockquote', 'h1', 'h2', 'h3', 'h4', 'pre', 'code', 'a', 'img', 'video', 'source'];
        $allowedAttrMap = [
            'a' => ['href', 'target', 'rel', 'style'],
            'img' => ['src', 'alt'],
            'video' => ['src', 'controls', 'playsinline', 'preload', 'muted', 'loop', 'poster'],
            'source' => ['src', 'type'],
            'span' => ['style'],
            'p' => ['style'],
            'li' => ['style'],
            'blockquote' => ['style'],
            'h1' => ['style'],
            'h2' => ['style'],
            'h3' => ['style'],
            'h4' => ['style'],
        ];

        $previousUseInternalErrors = libxml_use_internal_errors(true);

        $document = new \DOMDocument('1.0', 'UTF-8');
        $wrappedHtml = '<div id="__se2_root__">'.$html.'</div>';
        $encodedHtml = mb_convert_encoding($wrappedHtml, 'HTML-ENTITIES', 'UTF-8');

        $document->loadHTML($encodedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        $elements = [];
        foreach ($document->getElementsByTagName('*') as $element) {
            $elements[] = $element;
        }

        foreach (array_reverse($elements) as $element) {
            $tag = Str::lower($element->tagName);

            if ($tag === 'div' && $element->getAttribute('id') === '__se2_root__') {
                continue;
            }

            if (! in_array($tag, $allowedTags, true)) {
                $parent = $element->parentNode;
                if (! $parent) {
                    continue;
                }

                while ($element->firstChild) {
                    $parent->insertBefore($element->firstChild, $element);
                }

                $parent->removeChild($element);
                continue;
            }

            $allowedAttrs = $allowedAttrMap[$tag] ?? [];
            if ($element->hasAttributes()) {
                for ($index = $element->attributes->length - 1; $index >= 0; $index--) {
                    $attributeNode = $element->attributes->item($index);
                    if (! $attributeNode) {
                        continue;
                    }

                    $attributeName = Str::lower($attributeNode->name);
                    if (! in_array($attributeName, $allowedAttrs, true)) {
                        $element->removeAttribute($attributeNode->name);
                    }
                }
            }

            if ($tag === 'a') {
                $href = trim((string) $element->getAttribute('href'));
                if ($href === '' || ! preg_match('/^(https?:\/\/|mailto:|tel:|\/|#)/i', $href)) {
                    $element->removeAttribute('href');
                }

                $target = Str::lower(trim((string) $element->getAttribute('target')));
                if ($target === '_blank') {
                    $element->setAttribute('rel', 'noopener noreferrer nofollow');
                } else {
                    $element->removeAttribute('target');
                    $element->removeAttribute('rel');
                }
            } elseif ($tag === 'img' || $tag === 'video' || $tag === 'source') {
                $src = trim((string) $element->getAttribute('src'));
                if ($src === '' || ! preg_match('/^(https?:\/\/|\/)/i', $src)) {
                    $parent = $element->parentNode;
                    if ($parent) {
                        $parent->removeChild($element);
                    }
                }
            }

            if ($element->hasAttribute('style')) {
                $sanitizedStyle = $this->sanitizeInlineStyle((string) $element->getAttribute('style'));
                if ($sanitizedStyle === '') {
                    $element->removeAttribute('style');
                } else {
                    $element->setAttribute('style', $sanitizedStyle);
                }
            }
        }

        $root = $document->getElementById('__se2_root__');
        if (! $root) {
            return '';
        }

        $sanitized = '';
        foreach ($root->childNodes as $childNode) {
            $sanitized .= $document->saveHTML($childNode);
        }

        return trim($sanitized);
    }

    private function sanitizeInlineStyle(string $style): string
    {
        $allowedStyleMap = [
            'color' => '/^(#([0-9a-f]{3}|[0-9a-f]{6})|rgb\((\s*\d+\s*,){2}\s*\d+\s*\)|rgba\((\s*\d+\s*,){3}\s*(0|0?\.\d+|1)\s*\))$/i',
            'background-color' => '/^(transparent|#([0-9a-f]{3}|[0-9a-f]{6})|rgb\((\s*\d+\s*,){2}\s*\d+\s*\)|rgba\((\s*\d+\s*,){3}\s*(0|0?\.\d+|1)\s*\))$/i',
            'font-size' => '/^((1[0-9]|2[0-9]|3[0-9]|40)px|xx-small|x-small|small|medium|large|x-large|xx-large|smaller|larger)$/i',
            'text-align' => '/^(left|center|right)$/i',
            'border-left' => '/^4px\s+solid\s+#([0-9a-f]{3}|[0-9a-f]{6})$/i',
            'padding' => '/^\d{1,2}px(\s+\d{1,2}px){0,3}$/',
            'margin' => '/^\d{1,2}px(\s+\d{1,2}px){0,3}$/',
            'border-radius' => '/^\d{1,2}px$/',
        ];

        $safeDeclarations = [];
        foreach (explode(';', $style) as $declaration) {
            [$property, $value] = array_pad(explode(':', $declaration, 2), 2, null);
            $property = Str::lower(trim((string) $property));
            $value = trim((string) $value);

            if ($property === '' || $value === '' || ! isset($allowedStyleMap[$property])) {
                continue;
            }

            if (preg_match($allowedStyleMap[$property], $value)) {
                $safeDeclarations[] = $property.':'.$value;
            }
        }

        return implode('; ', $safeDeclarations);
    }
}
