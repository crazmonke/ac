<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Board;
use App\Models\Post;
use App\Models\PostFile;
use App\Models\PostTopic;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Database\Eloquent\Builder;
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

        if ($user) {
            $user->loadMissing('preferredResidenceComplex');
        }

        $apartment = ($user?->preferred_apartment_id ? Apartment::query()->find((int) $user->preferred_apartment_id) : null)
            ?? Apartment::query()->find($requestedApartmentId)
            ?? Apartment::query()->orderBy('id')->first();

        $apartmentName = $apartment?->name ?? '커뮤니티';
        $apartmentId = (int) ($apartment?->id ?? $requestedApartmentId);
        $isVerified = (bool) ($user && $this->permissionService->hasVerifiedRole($user));
        $preferredApartmentId = $this->resolveUserApartmentId($user);
        $preferredResidenceComplexId = (int) ($user?->preferred_residence_complex_id ?? 0);
        $scope = (string) $request->query('scope', 'all');
        $topic = trim((string) $request->query('topic', ''));
        $selectedTopicName = $topic !== ''
            ? PostTopic::query()->where('slug', $topic)->value('name')
            : null;
        $requiresSignupForScope = ! auth()->check();
        $shouldSplitApartmentFeed = false;

        if (! in_array($scope, ['all', 'region', 'apartment'], true)) {
            $scope = 'all';
        }

        if ($scope === 'apartment' && (! $isVerified || ($preferredApartmentId <= 0 && $preferredResidenceComplexId <= 0))) {
            $requiresSignupForScope = true;
        }

        $postsQuery = Post::query()
            ->with(['board', 'apartment', 'residenceComplex', 'topic', 'files'])
            ->where('visibility', '!=', 'deleted')
            ->whereHas('board', fn ($query) => $query->where('is_active', true))
            ->latest();

        if ($scope === 'all') {
            // 전국 탭: 전국 동네 게시글 최신순.
            $postsQuery->where('audience_scope', 'region');
        } elseif ($scope === 'region') {
            // 동네 탭: 로그인 회원의 내 지역 동네 게시글만 노출.
            $postsQuery->where('audience_scope', 'region');
            $this->applyNeighborhoodFilter($postsQuery, $user);
        } elseif ($scope === 'apartment') {
            // 공동주택 탭: 인증 회원의 내 공동주택 게시글만 노출.
            $postsQuery->where('audience_scope', 'apartment');

            if (! $isVerified || ($preferredApartmentId <= 0 && $preferredResidenceComplexId <= 0)) {
                $postsQuery->whereRaw('1 = 0');
            } else {
                $postsQuery->where(function (Builder $query) use ($preferredApartmentId, $preferredResidenceComplexId) {
                    if ($preferredResidenceComplexId > 0) {
                        $query->where('residence_complex_id', $preferredResidenceComplexId)
                            ->orWhere(function (Builder $legacyResidenceQuery) use ($preferredResidenceComplexId) {
                                $legacyResidenceQuery->whereNull('residence_complex_id')
                                    ->whereHas('user', function (Builder $userQuery) use ($preferredResidenceComplexId) {
                                        $userQuery->where('preferred_residence_complex_id', $preferredResidenceComplexId);
                                    });
                            });
                    }

                    if ($preferredApartmentId > 0) {
                        $method = $preferredResidenceComplexId > 0 ? 'orWhere' : 'where';
                        $query->{$method}('apartment_id', $preferredApartmentId);
                    }
                });
            }
        }

        if ($topic !== '') {
            $postsQuery->whereHas('topic', function ($query) use ($topic, $selectedTopicName) {
                $query->where('slug', $topic);

                if ($selectedTopicName) {
                    $query->orWhere('name', $selectedTopicName);
                }
            });
        }

        $postsQuery->latest();

        $topicsQuery = PostTopic::query()
            ->whereHas('posts', function ($query) use ($scope, $user, $isVerified, $preferredApartmentId, $preferredResidenceComplexId) {
                $query->where('visibility', '!=', 'deleted');

                if ($scope === 'all') {
                    $query->where('audience_scope', 'region');
                } elseif ($scope === 'region') {
                    $query->where('audience_scope', 'region');
                    $this->applyNeighborhoodFilter($query, $user);
                } elseif ($scope === 'apartment') {
                    $query->where('audience_scope', 'apartment');

                    if (! $isVerified || ($preferredApartmentId <= 0 && $preferredResidenceComplexId <= 0)) {
                        $query->whereRaw('1 = 0');
                    } else {
                        $query->where(function (Builder $innerQuery) use ($preferredApartmentId, $preferredResidenceComplexId) {
                            if ($preferredResidenceComplexId > 0) {
                                $innerQuery->where('residence_complex_id', $preferredResidenceComplexId)
                                    ->orWhere(function (Builder $legacyResidenceQuery) use ($preferredResidenceComplexId) {
                                        $legacyResidenceQuery->whereNull('residence_complex_id')
                                            ->whereHas('user', function (Builder $userQuery) use ($preferredResidenceComplexId) {
                                                $userQuery->where('preferred_residence_complex_id', $preferredResidenceComplexId);
                                            });
                                    });
                            }

                            if ($preferredApartmentId > 0) {
                                $method = $preferredResidenceComplexId > 0 ? 'orWhere' : 'where';
                                $innerQuery->{$method}('apartment_id', $preferredApartmentId);
                            }
                        });
                    }
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
                'apartment_name' => $post->apartment?->name ?: ($post->residenceComplex?->displayName() ?: '공동주택'),
                'sido' => $post->apartment?->sido ?: ($post->region_sido ?? ''),
                'sigungu' => $post->apartment?->sigungu ?: ($post->region_sigungu ?? ''),
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

    private function resolveUserApartmentId(?User $user): int
    {
        if (! $user) {
            return 0;
        }

        return (int) ($user->preferred_apartment_id ?? 0);
    }

    private function applyNeighborhoodFilter(Builder $query, ?User $user): void
    {
        if (! $user) {
            // 비회원은 동네 기준값이 없어 결과를 제한합니다.
            $query->whereRaw('1 = 0');

            return;
        }

        $targetSido = trim((string) ($user->home_sido ?? ''));
        $targetSigungu = trim((string) ($user->home_sigungu ?? ''));
        $targetDong = trim((string) ($user->home_eupmyeondong ?? ''));

        if ($targetSido === '' && $targetSigungu === '' && $targetDong === '') {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $postQuery) use ($targetSido, $targetSigungu, $targetDong) {
            $postQuery->where(function (Builder $q) use ($targetSido, $targetSigungu, $targetDong) {
                if ($targetSido !== '') {
                    $q->where('region_sido', 'like', '%' . $targetSido . '%');
                }

                if ($targetSigungu !== '') {
                    $q->where(function (Builder $sigunguQuery) use ($targetSigungu) {
                        $sigunguQuery->where('region_sigungu', 'like', '%' . $targetSigungu . '%');

                        $compactSigungu = str_replace([' ', '시'], '', $targetSigungu);
                        if ($compactSigungu !== '') {
                            $sigunguQuery->orWhereRaw("REPLACE(REPLACE(COALESCE(region_sigungu, ''), ' ', ''), '시', '') LIKE ?", ['%' . $compactSigungu . '%']);
                        }
                    });
                }

                if ($targetDong !== '') {
                    $q->where('region_eupmyeondong', 'like', '%' . $targetDong . '%');
                }
            });

            $postQuery->orWhereHas('apartment', function (Builder $apartmentQuery) use ($targetSido, $targetSigungu, $targetDong) {
                if ($targetSido !== '') {
                    $apartmentQuery->where('sido', 'like', '%' . $targetSido . '%');
                }

                if ($targetSigungu !== '') {
                    $apartmentQuery->where(function (Builder $sigunguQuery) use ($targetSigungu) {
                        $sigunguQuery->where('sigungu', 'like', '%' . $targetSigungu . '%');

                        $compactSigungu = str_replace([' ', '시'], '', $targetSigungu);
                        if ($compactSigungu !== '') {
                            $sigunguQuery->orWhereRaw("REPLACE(REPLACE(COALESCE(sigungu, ''), ' ', ''), '시', '') LIKE ?", ['%' . $compactSigungu . '%']);
                        }
                    });
                }

                if ($targetDong !== '') {
                    $apartmentQuery->where('eupmyeondong', 'like', '%' . $targetDong . '%');
                }
            });
        });
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
