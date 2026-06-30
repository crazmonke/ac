<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\Board;
use App\Models\Post;
use App\Services\PermissionService;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;

class PublicSiteController extends Controller
{
    public function __construct(private readonly PermissionService $permissionService)
    {
    }

    public function home(Request $request)
    {
        $apartmentId = max(1, (int) $request->query('apartment_id', 1));

        $apartment = Apartment::query()->find($apartmentId)
            ?? Apartment::query()->orderBy('id')->first();

        if (! $apartment) {
            abort(404, '아파트 데이터가 없습니다.');
        }

        $apartmentId = (int) $apartment->id;
        $user = $request->user();

        $boards = Board::query()
            ->with('category')
            ->where('apartment_id', $apartmentId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $publicBoards = $boards->filter(fn (Board $board) => $this->permissionService->hasBoardPermission($user, $board, 'read'));
        $lockedBoards = $boards->reject(fn (Board $board) => $this->permissionService->hasBoardPermission($user, $board, 'read'));

        $notices = Post::query()
            ->with(['board', 'apartment'])
            ->where('apartment_id', $apartmentId)
            ->where(function ($query) {
                $query->where('is_notice', true)
                    ->orWhereHas('board', function ($boardQuery) {
                        $boardQuery->where('board_type', 'notice');
                    });
            })
            ->latest()
            ->limit(6)
            ->get();

        $bestTopics = Post::query()
            ->with(['board', 'apartment'])
            ->where('apartment_id', $apartmentId)
            ->where('visibility', 'resident_only')
            ->whereHas('board', function ($query) {
                $query->where('is_active', true)
                    ->where('read_role', '!=', 'guest');
            })
            ->orderByRaw('(view_count * 2 + comment_count * 3) desc')
            ->latest()
            ->limit(8)
            ->get();

        $latestPosts = Post::query()
            ->with(['board', 'apartment'])
            ->where('apartment_id', $apartmentId)
            ->whereHas('board', function ($query) {
                $query->where('is_active', true);
            })
            ->latest()
            ->limit(12)
            ->get();

        return view('public.home', [
            'apartment' => $apartment,
            'publicBoards' => $publicBoards,
            'lockedBoards' => $lockedBoards,
            'notices' => $this->mapPostCards($notices, $user, $apartmentId),
            'bestTopics' => $this->mapPostCards($bestTopics, $user, $apartmentId),
            'latestPosts' => $this->mapPostCards($latestPosts, $user, $apartmentId),
            'isLoggedIn' => (bool) $user,
        ]);
    }

    public function board(Request $request, string $slug)
    {
        $apartmentId = max(1, (int) $request->query('apartment_id', 1));

        $board = Board::query()
            ->where('slug', $slug)
            ->where('apartment_id', $apartmentId)
            ->where('is_active', true)
            ->firstOrFail();

        $canRead = $this->permissionService->hasBoardPermission($request->user(), $board, 'read');

        $posts = Post::query()
            ->where('board_id', $board->id)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('public.board', [
            'board' => $board,
            'posts' => $posts,
            'canRead' => $canRead,
            'apartmentId' => $apartmentId,
        ]);
    }

    public function post(Request $request, int $id)
    {
        $post = Post::query()
            ->with(['board', 'apartment', 'user'])
            ->findOrFail($id);

        $canRead = $this->permissionService->hasBoardPermission($request->user(), $post->board, 'read');

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

    private function mapPostCards(Collection $posts, $user, int $apartmentId): Collection
    {
        return $posts->map(function (Post $post) use ($user, $apartmentId) {
            $canRead = $this->permissionService->hasBoardPermission($user, $post->board, 'read');

            if ($canRead && $user) {
                $url = '/community/posts/'.$post->id.'?apartment_id='.$apartmentId;
            } elseif ($canRead) {
                $url = '/posts/'.$post->id.'?apartment_id='.$apartmentId;
            } else {
                $url = '/register?redirect='.urlencode('/posts/'.$post->id.'?apartment_id='.$apartmentId);
            }

            return [
                'id' => (int) $post->id,
                'title' => $post->title,
                'created_at' => $post->created_at,
                'board_name' => $post->board->name,
                'apartment_name' => $post->apartment->name,
                'view_count' => (int) $post->view_count,
                'comment_count' => (int) $post->comment_count,
                'is_notice' => (bool) $post->is_notice,
                'can_read' => $canRead,
                'url' => $url,
            ];
        });
    }
}
