<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\Board;
use App\Models\Post;
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

        $apartment = ($user?->preferred_apartment_id ? Apartment::query()->find((int) $user->preferred_apartment_id) : null)
            ?? Apartment::query()->find($requestedApartmentId)
            ?? Apartment::query()->orderBy('id')->first();

        if (! $apartment) {
            abort(404, '아파트 데이터가 없습니다.');
        }

        $apartmentId = (int) $apartment->id;

        $boards = Board::query()
            ->with('category')
            ->where(function ($query) use ($apartmentId) {
                $query->whereNull('apartment_id')
                    ->orWhere('apartment_id', $apartmentId);
            })
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $publicBoards = $boards->filter(fn (Board $board) => $this->permissionService->hasBoardPermission($user, $board, 'read'));
        $lockedBoards = $boards->reject(fn (Board $board) => $this->permissionService->hasBoardPermission($user, $board, 'read'));

        $notices = Post::query()
            ->with(['board', 'apartment'])
            ->where(function ($query) {
                $this->applyNoticeFilter($query);
            })
            ->latest()
            ->paginate(20, ['*'], 'notice_page')
            ->withQueryString();
        $notices = $this->mapPostPaginator($notices, $user);

        $bestCandidatePosts = Post::query()
            ->with(['board', 'apartment'])
            ->where('visibility', '!=', 'deleted')
            ->where(function ($query) {
                $this->applyNonNoticeFilter($query);
            })
            ->whereHas('board', function ($query) {
                $query->where('is_active', true);
            })
            ->whereIn('audience_scope', ['region', 'apartment'])
            ->orderByDesc('view_count')
            ->orderByDesc('comment_count')
            ->latest()
            ->limit(300)
            ->get();

        $bestTopics = $bestCandidatePosts
            ->filter(fn (Post $post) => $this->permissionService->canReadPostDetail($user, $post))
            ->take(10)
            ->values();

        $latestPosts = Post::query()
            ->with(['board', 'apartment'])
            ->where(function ($query) {
                $this->applyNonNoticeFilter($query);
            })
            ->whereHas('board', function ($query) {
                $query->where('is_active', true);
            })
            ->latest()
            ->limit(20)
            ->get();

        return view('public.home', [
            'apartment' => $apartment,
            'publicBoards' => $publicBoards,
            'lockedBoards' => $lockedBoards,
            'notices' => $notices,
            'bestTopics' => $this->mapPostCards($bestTopics, $user),
            'latestPosts' => $this->mapPostCards($latestPosts, $user),
            'isLoggedIn' => (bool) $user,
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
            'display_date' => $this->formatDisplayDate($post->created_at),
            'board_name' => $post->board->name,
            'apartment_name' => $post->apartment->name,
            'region_label' => $this->regionLabelFromSido($post->apartment->sido),
            'brand_token' => $this->brandTokenFromApartmentName($post->apartment->name),
            'view_count' => (int) $post->view_count,
            'comment_count' => (int) $post->comment_count,
            'is_notice' => (bool) $post->is_notice,
            'is_guest_visible' => (bool) $post->is_guest_visible,
            'can_read' => $canRead,
            'access_label' => $this->permissionService->resolvePostAccessLabel($user, $post),
            'url' => $url,
        ];
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
