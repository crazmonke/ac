<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Post;
use App\Services\PermissionService;
use Illuminate\Http\Request;

class CommunityPageController extends Controller
{
    public function __construct(private readonly PermissionService $permissionService)
    {
    }

    public function index(Request $request)
    {
        $requestedApartmentId = max(1, (int) $request->query('apartment_id', 1));
        $user = $request->user();

        $apartment = Apartment::query()->find($requestedApartmentId)
            ?? ($user?->preferred_apartment_id ? Apartment::query()->find((int) $user->preferred_apartment_id) : null)
            ?? Apartment::query()->orderBy('id')->first();

        $apartmentName = $apartment?->name ?? '커뮤니티';
        $apartmentId = (int) ($apartment?->id ?? $requestedApartmentId);
        $isResident = (bool) ($user && $apartment && $user->hasRoleForApartment('resident', (int) $apartment->id));
        $isGuest = ! $user;
        $scope = (string) $request->query('scope', $isResident ? 'region' : 'all');
        $requiresSignupForScope = $isGuest && in_array($scope, ['region', 'apartment'], true);

        if (! in_array($scope, ['all', 'region', 'apartment'], true)) {
            $scope = $isResident ? 'region' : 'all';
        }

        $postsQuery = Post::query()
            ->with(['board', 'apartment'])
            ->where('visibility', '!=', 'deleted')
            ->whereHas('board', fn ($query) => $query->where('is_active', true))
            ->latest();

        if ($requiresSignupForScope) {
            $postsQuery->whereRaw('1=0');
        } elseif ($isResident && $scope === 'apartment' && $apartment) {
            $postsQuery->where('apartment_id', (int) $apartment->id);
        } elseif ($isResident && $scope === 'region' && $apartment) {
            $sido = trim((string) $apartment->sido);
            $sigungu = trim((string) $apartment->sigungu);
            $postsQuery->whereHas('apartment', function ($query) use ($sido, $sigungu) {
                if ($sido !== '') {
                    $query->where('sido', $sido);
                }

                if ($sigungu !== '') {
                    $query->where('sigungu', $sigungu);
                }
            });
        }

        $posts = $postsQuery
            ->paginate(20)
            ->withQueryString();

        $posts->through(function (Post $post) use ($user) {
            $canReadBoard = $this->permissionService->hasBoardPermission($user, $post->board, 'read');
            $canRead = $canReadBoard || (bool) $post->is_guest_visible;

            return [
                'id' => (int) $post->id,
                'title' => $post->title,
                'created_at' => $post->created_at,
                'board_name' => $post->board->name,
                'apartment_name' => $post->apartment->name,
                'sido' => $post->apartment->sido,
                'sigungu' => $post->apartment->sigungu,
                'apartment_id' => (int) $post->apartment_id,
                'view_count' => (int) $post->view_count,
                'comment_count' => (int) $post->comment_count,
                'can_read' => $canRead,
                'is_guest_visible' => (bool) $post->is_guest_visible,
                'url' => $canRead
                    ? ($user && $canReadBoard
                        ? '/community/posts/'.$post->id.'?apartment_id='.(int) $post->apartment_id
                        : '/posts/'.$post->id.'?apartment_id='.(int) $post->apartment_id)
                    : '/register?redirect='.urlencode('/posts/'.$post->id.'?apartment_id='.(int) $post->apartment_id),
            ];
        });

        $regionLabel = trim((string) ($apartment?->sigungu ?: $apartment?->eupmyeondong ?: $apartment?->sido));

        return view('community.index', [
            'apartmentId' => $apartmentId,
            'apartmentName' => $apartmentName,
            'regionLabel' => $regionLabel !== '' ? $regionLabel : '우리 동네',
            'posts' => $posts,
            'scope' => $scope,
            'isResident' => $isResident,
            'requiresSignupForScope' => $requiresSignupForScope,
        ]);
    }
}
