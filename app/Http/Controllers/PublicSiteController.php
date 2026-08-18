<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\Banner;
use App\Models\Board;
use App\Models\Post;
use App\Models\PostFile;
use App\Models\PostLike;
use App\Services\PermissionService;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PublicSiteController extends Controller
{
    public function __construct(private readonly PermissionService $permissionService)
    {
    }

    public function home(Request $request)
    {
        $requestedApartmentId = max(1, (int) $request->query('apartment_id', 1));
        $user = $request->user();

        if ($user) {
            $user->loadMissing('preferredResidenceComplex');
        }

        $apartment = ($user?->preferred_apartment_id ? Apartment::query()->find((int) $user->preferred_apartment_id) : null)
            ?? Apartment::query()->find($requestedApartmentId)
            ?? Apartment::query()->orderBy('id')->first();

        if (! $apartment) {
            $apartment = new Apartment([
                'id' => 0,
                'name' => '공동주택 정보 준비중',
                'sido' => '',
                'sigungu' => '',
                'eupmyeondong' => '',
            ]);
        }

        $hasPostLikesTable = Schema::hasTable('post_likes');
        $canQueryFeed = Schema::hasTable('posts') && Schema::hasTable('boards');
        $hasAudienceScopeColumn = $canQueryFeed && Schema::hasColumn('posts', 'audience_scope');

        // 커뮤니티 메인(/community) 전체 탭과 동일한 조건으로 노출한다.
        if ($canQueryFeed) {
            $query = Post::query()
                ->with(['board', 'apartment', 'files', 'user', 'poll.options'])
                ->where('visibility', '!=', 'deleted')
                ->whereHas('board', function ($query) {
                    $query->where('is_active', true)
                        ->where('slug', '!=', 'policy');
                })
                ->latest()
                ->orderByDesc('id');

            if ($hasPostLikesTable) {
                $query->withCount('likes');
            }

            if ($hasAudienceScopeColumn) {
                $query->whereIn('audience_scope', ['region', 'all']);
            }

            $feedPaginator = $query->paginate(20)->withQueryString();
        } else {
            $page = max(1, (int) $request->query('page', 1));
            $feedPaginator = new LengthAwarePaginator(
                collect(),
                0,
                20,
                $page,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );
        }

        $feedPaginator = $this->mapPostPaginator($feedPaginator, $user, $hasPostLikesTable);

        $isLoggedIn = (bool) $user;
        $isVerifiedUser = (bool) ($user && $this->permissionService->hasVerifiedRole($user));

        $feedTitle = '전국 동네 피드';
        $feedDescription = '전국 동네 게시글을 최신순으로 보여드립니다.';

        $banners = Banner::active()->ordered()->get()->map(function ($banner) {
            // 업로드된 파일이 있으면 그것을 우선 사용 (Cafe24 호환성: public/uploads 경로 사용)
            if ($banner->type === 'image' && $banner->image_path) {
                $banner->image_url = asset($banner->image_path);
            }
            if ($banner->type === 'video' && $banner->video_path) {
                $banner->video_url = asset($banner->video_path);
            }
            return $banner;
        });

        // 게시글 없을 때 표시할 랜덤 메시지
        $emptyFeedMessages = [
            '첫 게시글의 주인공이 되어보세요.',
            '우리 동네 소식을 가장 먼저 알려주세요.',
            '질문 하나, 정보 하나가 이웃에게 큰 도움이 됩니다.',
            '오늘의 첫 이야기를 남겨보세요.',
            '이웃들이 기다리고 있는 정보를 공유해 주세요.',
            '우리 동네의 첫 소식을 전해볼까요?',
            "오늘은 비어 있지만,\n내일은 이웃들의 이야기로 가득 찰 거예요.",
            '첫 번째 이야기가 새로운 인연을 만듭니다.',
            '작은 글 하나가 따뜻한 이웃을 만나는 시작입니다.',
            "모두가 기다리는 건 특별한 글이 아니라,\n당신의 첫 이야기입니다.",
            '우리 동네 이야기는 주민이 만들어갑니다.',
            '층간소음보다 따뜻한 대화가 먼저 시작되길 바랍니다.',
            "공지부터 맛집, 생활 꿀팁까지.\n우리 동네 이야기를 공유해 보세요.",
            "같은 건물, 같은 동네.\n이제는 이야기도 함께 나눠보세요.",
            '우리 단지의 정보와 일상을 함께 만들어가요.',
        ];

        return view('public.home', [
            'apartment' => $apartment,
            'feedPosts' => $feedPaginator,
            'feedTitle' => $feedTitle,
            'feedDescription' => $feedDescription,
            'isLoggedIn' => $isLoggedIn,
            'isVerifiedUser' => $isVerifiedUser,
            'banners' => $banners,
            'emptyFeedMessage' => $emptyFeedMessages[array_rand($emptyFeedMessages)],
        ]);
    }

    public function board(Request $request, string $slug)
    {
        $apartmentId = max(1, (int) $request->query('apartment_id', 1));
        $user = $request->user();

        $board = Board::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->where(function ($query) use ($apartmentId) {
                $query->whereNull('apartment_id')
                    ->orWhere('apartment_id', $apartmentId);
            })
            ->orderByRaw('CASE WHEN apartment_id IS NULL THEN 0 ELSE 1 END')
            ->firstOrFail();

        $canReadBoard = $this->permissionService->hasBoardPermission($user, $board, 'read');

        $posts = Post::query()
            ->where('board_id', $board->id)
            ->where('visibility', '!=', 'deleted')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $postReadMap = [];
        foreach ($posts as $post) {
            $postReadMap[$post->id] = $this->canReadPost($user, $post, $canReadBoard);
        }

        return view('public.board', [
            'board' => $board,
            'posts' => $posts,
            'canReadBoard' => $canReadBoard,
            'postReadMap' => $postReadMap,
            'apartmentId' => $apartmentId,
        ]);
    }

    public function post(Request $request, int $id)
    {
        $post = Post::query()
            ->with(['board', 'apartment', 'user'])
            ->findOrFail($id);

        $canRead = $this->canReadPost($request->user(), $post);

        return view('public.post', [
            'post' => $post,
            'canRead' => $canRead,
            'apartmentId' => (int) $post->apartment_id,
            'isLoggedIn' => (bool) $request->user(),
        ]);
    }

    public function terms()
    {
        return view('public.policy', [
            'title' => '이용약관',
            'content' => '서비스 이용약관입니다. 가입/이용 시 본 약관 및 운영정책에 동의한 것으로 간주합니다.',
        ]);
    }

    public function privacy()
    {
        return view('public.policy', [
            'title' => '개인정보처리방침',
            'content' => '개인정보 수집 및 이용, 보관, 파기와 관련한 정책을 안내합니다.',
        ]);
    }

    private function mapPostCards(Collection $posts, $user, bool $hasPostLikesTable): Collection
    {
        return $posts->map(fn (Post $post) => $this->mapPostCard($post, $user, $hasPostLikesTable));
    }

    private function mapPostPaginator(LengthAwarePaginator $paginator, $user, bool $hasPostLikesTable): LengthAwarePaginator
    {
        $paginator->setCollection(
            $paginator->getCollection()->map(fn (Post $post) => $this->mapPostCard($post, $user, $hasPostLikesTable))
        );

        return $paginator;
    }

    private function mapPostCard(Post $post, $user, bool $hasPostLikesTable): array
    {
        $canRead = $this->canReadPost($user, $post);
        $authorName = $post->is_anonymous ? '익명' : trim((string) ($post->user?->name ?? '알 수 없음'));
        $authorInitial = mb_substr($authorName !== '' ? $authorName : 'U', 0, 1);
        $likeCount = (int) ($post->likes_count ?? 0);
        $pollPreview = $this->buildPollPreview($post);
        $likedByMe = $hasPostLikesTable && $user
            ? PostLike::query()->where('post_id', $post->id)->where('user_id', $user->id)->exists()
            : false;

        if ($canRead && $user) {
            $url = '/community/posts/'.$post->id;
        } elseif ($canRead) {
            $url = '/posts/'.$post->id;
        } else {
            $url = '/posts/'.$post->id;
        }

        return [
            'id' => (int) $post->id,
            'title' => $post->title,
            'created_at' => $post->created_at,
            'created_label' => $post->created_at?->diffForHumans() ?? '-',
            'display_date' => $this->formatDisplayDate($post->created_at),
            'author_name' => $authorName,
            'author_initial' => mb_strtoupper($authorInitial),
            'author_is_verified' => $post->user !== null && $this->permissionService->hasVerifiedRole($post->user),
            'board_name' => $post->board->name,
            'is_poll' => (bool) ($pollPreview['is_poll'] ?? false),
            'poll_question' => (string) ($pollPreview['question'] ?? ''),
            'poll_options_preview' => (array) ($pollPreview['options'] ?? []),
            'poll_total_votes' => (int) ($pollPreview['total_votes'] ?? 0),
            'audience_scope' => (string) ($post->audience_scope ?? 'all'),
            'apartment_name' => $post->apartment->name,
            'body_preview' => $this->buildBodyPreview($post->body),
            'media_items' => $canRead ? $this->extractMediaItems($post) : [],
            'region_label' => $this->regionLabelFromSido($post->apartment->sido),
            'brand_token' => $this->brandTokenFromApartmentName($post->apartment->name),
            'view_count' => (int) $post->view_count,
            'comment_count' => (int) $post->comment_count,
            'like_count' => $likeCount,
            'liked_by_me' => $likedByMe,
            'is_notice' => (bool) $post->is_notice,
            'is_guest_visible' => (bool) $post->is_guest_visible,
            'can_read' => $canRead,
            'access_label' => $this->permissionService->resolvePostAccessLabel($user, $post),
            'url' => $url,
        ];
    }

    private function buildBodyPreview(?string $html): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $html)) ?: '');

        return mb_strimwidth($text, 0, 220, '...');
    }

    private function extractMediaItems(Post $post): array
    {
        $items = [];
        $seenUrls = [];

        foreach ($post->files as $file) {
            $mediaType = $this->resolveMediaType($file);
            if (! $mediaType) {
                continue;
            }

            $url = '/community/files/'.$file->id;
            if (isset($seenUrls[$url])) {
                continue;
            }

            $seenUrls[$url] = true;

            $items[] = [
                'type' => $mediaType,
                'url' => $url,
                'name' => (string) ($file->original_name ?? 'media'),
            ];
        }

        foreach ($this->extractEmbeddedBodyMedia((string) $post->body) as $embeddedMedia) {
            $url = (string) ($embeddedMedia['url'] ?? '');
            if ($url === '' || isset($seenUrls[$url])) {
                continue;
            }

            $seenUrls[$url] = true;
            $items[] = $embeddedMedia;
        }

        return array_slice($items, 0, 8);
    }

    private function buildPollPreview(Post $post): array
    {
        if ((string) ($post->board?->board_type ?? '') !== 'poll' || ! $post->poll) {
            return [
                'is_poll' => false,
                'question' => '',
                'options' => [],
                'total_votes' => 0,
            ];
        }

        $options = $post->poll->options
            ->sortBy('sort_order')
            ->take(3)
            ->pluck('label')
            ->map(fn ($label) => trim((string) $label))
            ->filter()
            ->values()
            ->all();

        return [
            'is_poll' => true,
            'question' => trim((string) ($post->poll->question ?? '')),
            'options' => $options,
            'total_votes' => (int) $post->poll->options->sum('vote_count'),
        ];
    }

    private function extractEmbeddedBodyMedia(string $html): array
    {
        $items = [];
        $patterns = [
            ['regex' => '/<img[^>]+src=["\']([^"\']+)["\']/i', 'type' => 'image'],
            ['regex' => '/<video[^>]+src=["\']([^"\']+)["\']/i', 'type' => 'video'],
            ['regex' => '/<source[^>]+src=["\']([^"\']+)["\']/i', 'type' => 'video'],
        ];

        foreach ($patterns as $pattern) {
            if (! preg_match_all($pattern['regex'], $html, $matches)) {
                continue;
            }

            foreach (($matches[1] ?? []) as $src) {
                $url = trim((string) $src);
                if ($url === '') {
                    continue;
                }

                $items[] = [
                    'type' => $pattern['type'],
                    'url' => $url,
                    'name' => 'embedded-media',
                ];
            }
        }

        return $items;
    }

    private function resolveMediaType(PostFile $file): ?string
    {
        $mime = Str::lower(trim((string) ($file->mime_type ?? '')));

        if (Str::startsWith($mime, 'image/')) {
            return 'image';
        }

        if (Str::startsWith($mime, 'video/')) {
            return 'video';
        }

        $sourceName = Str::lower((string) ($file->original_name ?: $file->path ?: ''));
        $extension = pathinfo($sourceName, PATHINFO_EXTENSION);

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true)) {
            return 'image';
        }

        if (in_array($extension, ['mp4', 'mov', 'webm', 'm4v'], true)) {
            return 'video';
        }

        return null;
    }
    private function applyNoticeFilter($query): void
    {
        $query->where('is_notice', true)
            ->orWhereHas('board', function ($boardQuery) {
                $boardQuery->where('board_type', 'notice');
            });
    }

    private function applyNonNoticeFilter($query): void
    {
        $query->where('is_notice', false)
            ->whereDoesntHave('board', function ($boardQuery) {
                $boardQuery->where('board_type', 'notice');
            });
    }

    private function canReadPost($user, Post $post): bool
    {
        return $this->permissionService->canReadPostDetail($user, $post);
    }

    private function regionLabelFromSido(?string $sido): string
    {
        $value = trim((string) $sido);

        if ($value === '') {
            return '-';
        }

        if (str_starts_with($value, '서울')) {
            return '서울';
        }

        return preg_replace('/\s+/', '', $value) ?: $value;
    }

    private function brandTokenFromApartmentName(?string $name): string
    {
        $value = trim((string) $name);

        if ($value === '') {
            return 'APT';
        }

        $knownBrands = [
            '자이' => 'XI',
            '래미안' => 'RAE',
            '푸르지오' => 'PRU',
            '더샵' => 'TS',
            '힐스테이트' => 'HS',
            '아이파크' => 'IP',
            '롯데캐슬' => 'LC',
            'e편한세상' => 'ECS',
            'SK뷰' => 'SKV',
            '위브' => 'WB',
            '호반베르디움' => 'HB',
        ];

        foreach ($knownBrands as $keyword => $token) {
            if (str_contains($value, $keyword)) {
                return $token;
            }
        }

        $normalized = preg_replace('/\s+/', '', $value) ?: $value;

        return mb_substr($normalized, 0, 1);
    }

    private function formatDisplayDate($createdAt): string
    {
        if (! $createdAt) {
            return '-';
        }

        $date = $createdAt->copy();

        if ($date->isSameDay(now())) {
            return $date->format('H:i');
        }

        return $date->format('m/d');
    }
}
