<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Board;
use App\Models\Post;
use App\Models\PostFile;
use App\Models\PostTopic;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CommunityPageController extends Controller
{
    public function __construct(private readonly PermissionService $permissionService)
    {
    }

    public function index(Request $request)
    {
        $requestedApartmentId = max(1, (int) $request->query('apartment_id', 1));
        $user = $request->user();

        $apartment = ($user?->preferred_apartment_id ? Apartment::query()->find((int) $user->preferred_apartment_id) : null)
            ?? Apartment::query()->find($requestedApartmentId)
            ?? Apartment::query()->orderBy('id')->first();

        $apartmentName = $apartment?->name ?? '커뮤니티';
        $apartmentId = (int) ($apartment?->id ?? $requestedApartmentId);
        $isVerified = (bool) ($user && $this->permissionService->hasVerifiedRole($user));
        $preferredApartmentId = (int) ($user?->preferred_apartment_id ?? 0);
        $scope = (string) $request->query('scope', 'all');
        $topic = trim((string) $request->query('topic', ''));
        $selectedTopicName = $topic !== ''
            ? PostTopic::query()->where('slug', $topic)->value('name')
            : null;
        $requiresSignupForScope = false;
        $shouldSplitApartmentFeed = $isVerified && $scope === 'apartment' && $preferredApartmentId > 0;

        if (! in_array($scope, ['all', 'region', 'apartment'], true)) {
            $scope = 'all';
        }

        $postsQuery = Post::query()
            ->with(['board', 'apartment', 'topic', 'files'])
            ->where('visibility', '!=', 'deleted')
            ->whereHas('board', fn ($query) => $query->where('is_active', true))
            ->latest();

        if ($scope === 'region') {
            $postsQuery->where('audience_scope', 'region');
        } elseif ($scope === 'apartment') {
            $postsQuery->where('audience_scope', 'apartment');
        }

        if ($topic !== '') {
            $postsQuery->whereHas('topic', function ($query) use ($topic, $selectedTopicName) {
                $query->where('slug', $topic);

                if ($selectedTopicName) {
                    $query->orWhere('name', $selectedTopicName);
                }
            });
        }

        if ($shouldSplitApartmentFeed) {
            $homeSido = trim((string) ($user?->home_sido ?? ''));
            $homeSigungu = trim((string) ($user?->home_sigungu ?? ''));
            $homeDong = trim((string) ($user?->home_eupmyeondong ?? ''));
            $homeDongSafe = $homeDong !== '' ? $homeDong : '__NO_DONG__';

            $postsQuery->orderByRaw(
                'CASE '
                .'WHEN apartment_id = ? THEN 0 '
                .'WHEN region_sido = ? AND region_sigungu = ? AND COALESCE(region_eupmyeondong, \'\') = ? THEN 1 '
                .'WHEN region_sido = ? AND region_sigungu = ? THEN 2 '
                .'WHEN region_sido = ? THEN 3 '
                .'ELSE 4 END',
                [
                    $preferredApartmentId,
                    $homeSido,
                    $homeSigungu,
                    $homeDongSafe,
                    $homeSido,
                    $homeSigungu,
                    $homeSido,
                ]
            )->latest();
        } else {
            $postsQuery->latest();
        }

        $topicsQuery = PostTopic::query()
            ->whereHas('posts', function ($query) use ($scope) {
                $query->where('visibility', '!=', 'deleted');

                if ($scope === 'region') {
                    $query->where('audience_scope', 'region');
                } elseif ($scope === 'apartment') {
                    $query->where('audience_scope', 'apartment');
                }
            })
            ->orderBy('name');

        $topicFacets = $topicsQuery
            ->limit(200)
            ->get(['name', 'slug'])
            ->unique(fn ($item) => mb_strtolower(trim((string) $item->name)))
            ->values()
            ->take(20);

        $canCreatePost = $isVerified;

        $posts = $postsQuery
            ->paginate(20)
            ->withQueryString();

        $posts->through(function (Post $post) use ($user, $apartmentId) {
            $canRead = $this->permissionService->canReadPostDetail($user, $post);

            return [
                'id' => (int) $post->id,
                'title' => $post->title,
                'created_at' => $post->created_at,
                'board_name' => $post->board->name,
                'topic_name' => $post->topic?->name,
                'apartment_name' => $post->apartment->name,
                'sido' => $post->apartment->sido,
                'sigungu' => $post->apartment->sigungu,
                'apartment_id' => (int) $post->apartment_id,
                'view_count' => (int) $post->view_count,
                'comment_count' => (int) $post->comment_count,
                'audience_scope' => (string) ($post->audience_scope ?? 'all'),
                'can_read' => $canRead,
                'access_label' => $this->permissionService->resolvePostAccessLabel($user, $post),
                'is_guest_visible' => (bool) $post->is_guest_visible,
                'thumbnail_url' => $canRead ? $this->resolvePostThumbnailUrl($post) : null,
                'url' => $canRead
                    ? ($user
                        ? '/community/posts/'.$post->id.'?apartment_id='.$apartmentId
                        : '/posts/'.$post->id.'?apartment_id='.(int) $post->apartment_id)
                    : '/posts/'.$post->id.'?apartment_id='.(int) $post->apartment_id,
            ];
        });

        $ownApartmentPosts = collect();
        $otherApartmentPosts = collect();
        if ($shouldSplitApartmentFeed) {
            $pageItems = collect($posts->items());
            $ownApartmentPosts = $pageItems
                ->filter(fn (array $item) => (int) ($item['apartment_id'] ?? 0) === $preferredApartmentId)
                ->values();
            $otherApartmentPosts = $pageItems
                ->filter(fn (array $item) => (int) ($item['apartment_id'] ?? 0) !== $preferredApartmentId)
                ->values();
        }

        $regionLabel = trim((string) ($apartment?->sigungu ?: $apartment?->eupmyeondong ?: $apartment?->sido));

        return view('community.index', [
            'apartmentId' => $apartmentId,
            'apartmentName' => $apartmentName,
            'regionLabel' => $regionLabel !== '' ? $regionLabel : '우리 동네',
            'posts' => $posts,
            'scope' => $scope,
            'topic' => $topic,
            'isVerified' => $isVerified,
            'requiresSignupForScope' => $requiresSignupForScope,
            'topicFacets' => $topicFacets,
            'canCreatePost' => (bool) $canCreatePost,
            'shouldSplitApartmentFeed' => $shouldSplitApartmentFeed,
            'preferredApartmentName' => trim((string) ($user?->home_apartment_name ?? '')),
            'ownApartmentPosts' => $ownApartmentPosts,
            'otherApartmentPosts' => $otherApartmentPosts,
        ]);
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
}
