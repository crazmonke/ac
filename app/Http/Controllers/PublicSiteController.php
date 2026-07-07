<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\Board;
use App\Models\Post;
use App\Models\PostFile;
use App\Models\PostLike;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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
            abort(404, '공동주택 데이터가 없습니다.');
        }

        $candidates = Post::query()
            ->with(['board', 'apartment', 'files', 'user'])
            ->withCount('likes')
            ->where('visibility', '!=', 'deleted')
            ->whereHas('board', function ($query) {
                $query->where('is_active', true);
            })
            ->latest()
            ->limit(500)
            ->get();

        $feedPosts = $candidates
            ->filter(fn (Post $post) => $this->shouldShowOnHomeFeed($post, $user, $apartment))
            ->values();

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 20;
        $feedPaginator = new LengthAwarePaginator(
            $feedPosts->forPage($page, $perPage)->values(),
            $feedPosts->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
        $feedPaginator = $this->mapPostPaginator($feedPaginator, $user);

        $isLoggedIn = (bool) $user;
        $isVerifiedUser = (bool) ($user && $this->permissionService->hasVerifiedRole($user));

        $feedTitle = ! $isLoggedIn
            ? '전국 동네 공개 피드'
            : ($isVerifiedUser ? '인증 회원 맞춤 피드' : '비인증 회원 피드');

        $feedDescription = ! $isLoggedIn
            ? '비회원도 읽을 수 있는 동네 공개 게시글을 최신순으로 제공합니다.'
            : ($isVerifiedUser
                ? '인증된 동네 게시글과 내 공동주택 게시글을 최신순으로 보여드립니다.'
                : '동네 영역 게시글과 비인증 회원도 읽을 수 있는 게시글을 최신순으로 보여드립니다.');

        return view('public.home', [
            'apartment' => $apartment,
            'feedPosts' => $feedPaginator,
            'feedTitle' => $feedTitle,
            'feedDescription' => $feedDescription,
            'isLoggedIn' => $isLoggedIn,
            'isVerifiedUser' => $isVerifiedUser,
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

    private function mapPostCards(Collection $posts, $user): Collection
    {
        return $posts->map(fn (Post $post) => $this->mapPostCard($post, $user));
    }

    private function mapPostPaginator(LengthAwarePaginator $paginator, $user): LengthAwarePaginator
    {
        $paginator->setCollection(
            $paginator->getCollection()->map(fn (Post $post) => $this->mapPostCard($post, $user))
        );

        return $paginator;
    }

    private function mapPostCard(Post $post, $user): array
    {
        $canRead = $this->canReadPost($user, $post);
        $authorName = $post->is_anonymous ? '익명' : trim((string) ($post->user?->name ?? '알 수 없음'));
        $authorInitial = mb_substr($authorName !== '' ? $authorName : 'U', 0, 1);
        $likeCount = (int) ($post->likes_count ?? 0);
        $likedByMe = $user
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
            'board_name' => $post->board->name,
            'apartment_name' => $post->apartment->name,
            'body_preview' => $this->buildBodyPreview($post->body),
            'media_items' => ($canRead && $user) ? $this->extractMediaItems($post) : [],
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

        foreach ($post->files as $file) {
            $mime = Str::lower((string) ($file->mime_type ?? ''));
            if (! Str::startsWith($mime, 'image/') && ! Str::startsWith($mime, 'video/')) {
                continue;
            }

            $items[] = [
                'type' => Str::startsWith($mime, 'video/') ? 'video' : 'image',
                'url' => '/community/files/'.$file->id,
                'name' => (string) ($file->original_name ?? 'media'),
            ];
        }

        return array_slice($items, 0, 8);
    }

    private function shouldShowOnHomeFeed(Post $post, ?User $user, Apartment $fallbackApartment): bool
    {
        if (! $this->permissionService->canReadPostDetail($user, $post)) {
            return false;
        }

        $scope = (string) ($post->audience_scope ?? 'all');

        if (! $user) {
            return $scope === 'region';
        }

        if (! $this->permissionService->hasVerifiedRole($user)) {
            return $scope === 'region' || $this->permissionService->canReadPostDetail($user, $post);
        }

        if ($scope === 'apartment') {
            return (int) $post->apartment_id === $this->resolveUserApartmentId($user, $fallbackApartment);
        }

        if ($scope === 'region') {
            return $this->isSameNeighborhood($post, $user, $fallbackApartment);
        }

        return false;
    }

    private function resolveUserApartmentId(User $user, Apartment $fallbackApartment): int
    {
        $preferredApartmentId = (int) ($user->preferred_apartment_id ?? 0);
        if ($preferredApartmentId > 0) {
            return $preferredApartmentId;
        }

        $legacyApartmentId = (int) ($user->preferredResidenceComplex?->legacy_apartment_id ?? 0);
        if ($legacyApartmentId > 0) {
            return $legacyApartmentId;
        }

        return 0;
    }

    private function isSameNeighborhood(Post $post, User $user, Apartment $fallbackApartment): bool
    {
        $targetSido = trim((string) ($user->home_sido ?: ''));
        $targetSigungu = trim((string) ($user->home_sigungu ?: ''));
        $targetDong = trim((string) ($user->home_eupmyeondong ?: ''));

        $postSido = trim((string) ($post->region_sido ?: $post->apartment?->sido));
        $postSigungu = trim((string) ($post->region_sigungu ?: $post->apartment?->sigungu));
        $postDong = trim((string) ($post->region_eupmyeondong ?: $post->apartment?->eupmyeondong));

        if ($targetSido !== '' && $postSido !== '' && ! $this->regionTokenMatches($targetSido, $postSido, false)) {
            return false;
        }

        if ($targetSigungu !== '' && $postSigungu !== '' && ! $this->regionTokenMatches($targetSigungu, $postSigungu, true)) {
            return false;
        }

        if ($targetDong !== '' && $postDong !== '' && ! $this->regionTokenMatches($targetDong, $postDong, false)) {
            return false;
        }

        return true;
    }

    private function regionTokenMatches(string $left, string $right, bool $normalizeCityToken): bool
    {
        $lhs = $this->normalizeRegionToken($left, $normalizeCityToken);
        $rhs = $this->normalizeRegionToken($right, $normalizeCityToken);

        if ($lhs === '' || $rhs === '') {
            return true;
        }

        if ($lhs === $rhs) {
            return true;
        }

        return str_contains($lhs, $rhs) || str_contains($rhs, $lhs);
    }

    private function normalizeRegionToken(string $value, bool $normalizeCityToken): string
    {
        $normalized = preg_replace('/\s+/u', '', trim($value)) ?: '';

        if (! $normalizeCityToken) {
            return $normalized;
        }

        return str_replace('시', '', $normalized);
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
