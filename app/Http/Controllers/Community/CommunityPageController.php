<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Board;
use App\Models\Post;
use App\Models\PostFile;
use App\Models\PostLike;
use App\Models\PostTopic;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
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
        $regionReset = $request->query('region') === 'all';
        $hasRegionQuery = $regionReset || $request->hasAny(['sido', 'sigungu']);
        $regionSido = trim((string) ($hasRegionQuery ? $request->query('sido', '') : ($user?->home_sido ?? '')));
        $regionSigungu = trim((string) ($hasRegionQuery ? $request->query('sigungu', '') : ($user?->home_sigungu ?? '')));
        $hasSelectedRegion = $regionSido !== '' || $regionSigungu !== '';
        $topic = trim((string) $request->query('topic', ''));
        $searchQuery = trim((string) $request->query('q', ''));
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

        $canQueryCommunityFeed = Schema::hasTable('posts') && Schema::hasTable('boards');
        $hasPostLikesTable = Schema::hasTable('post_likes');
        $hasAudienceScopeColumn = Schema::hasTable('posts') && Schema::hasColumn('posts', 'audience_scope');

        if (! $hasAudienceScopeColumn) {
            $scope = 'all';
        }

        $selectedBoardSlug = (string) $request->query('board', '');
        $postsQuery = null;

        if ($canQueryCommunityFeed) {
            $postsQuery = Post::query()
                ->with(['board', 'apartment', 'residenceComplex', 'topic', 'files', 'user', 'poll.options'])
                ->where('visibility', '!=', 'deleted')
                ->whereHas('board', function ($query) {
                    $query->where('is_active', true)
                        ->where('slug', '!=', 'policy');
                })
                ->latest();

            if ($hasPostLikesTable) {
                $postsQuery->withCount('likes');
            }
        }

        if ($canQueryCommunityFeed && $scope === 'all') {
            if ($hasAudienceScopeColumn) {
                $postsQuery->whereIn('audience_scope', ['region', 'all']);
            }
            if ($hasSelectedRegion) {
                $this->applyRegionFilter($postsQuery, $regionSido, $regionSigungu);
            }
        } elseif ($canQueryCommunityFeed && $scope === 'region') {
            if ($hasAudienceScopeColumn) {
                $postsQuery->where('audience_scope', 'region');
            }
            $this->applyRegionFilter($postsQuery, $regionSido, $regionSigungu);
        } elseif ($canQueryCommunityFeed && $scope === 'apartment') {
            // 공동주택 탭: 인증 회원의 내 공동주택 게시글만 노출.
            if ($hasAudienceScopeColumn) {
                $postsQuery->where('audience_scope', 'apartment');
            }

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

        if ($canQueryCommunityFeed && $topic !== '') {
            $postsQuery->whereHas('topic', function ($query) use ($topic, $selectedTopicName) {
                $query->where('slug', $topic);

                if ($selectedTopicName) {
                    $query->orWhere('name', $selectedTopicName);
                }
            });
        }

        if ($canQueryCommunityFeed && $searchQuery !== '') {
            $postsQuery->where(function (Builder $query) use ($searchQuery) {
                $query->where('title', 'like', '%' . $searchQuery . '%')
                    ->orWhere('body', 'like', '%' . $searchQuery . '%');
            });
        }

        if ($canQueryCommunityFeed && $selectedBoardSlug !== '') {
            $postsQuery->whereHas('board', function ($query) use ($selectedBoardSlug) {
                $query->where('slug', $selectedBoardSlug);
            });
        }

        if ($canQueryCommunityFeed) {
            $postsQuery->latest()->orderByDesc('id');
        }

        $topicFacets = collect();
        if ($canQueryCommunityFeed && Schema::hasTable('post_topics')) {
            $topicsQuery = PostTopic::query()
                ->whereHas('posts', function ($query) use ($scope, $user, $isVerified, $preferredApartmentId, $preferredResidenceComplexId, $hasAudienceScopeColumn, $hasSelectedRegion, $regionSido, $regionSigungu) {
                    $query->where('visibility', '!=', 'deleted');

                    if ($scope === 'all') {
                        if ($hasAudienceScopeColumn) {
                            $query->whereIn('audience_scope', ['region', 'all']);
                        }
                        if ($hasSelectedRegion) {
                            $this->applyRegionFilter($query, $regionSido, $regionSigungu);
                        }
                    } elseif ($scope === 'region') {
                        if ($hasAudienceScopeColumn) {
                            $query->where('audience_scope', 'region');
                        }
                        $this->applyRegionFilter($query, $regionSido, $regionSigungu);
                    } elseif ($scope === 'apartment') {
                        if ($hasAudienceScopeColumn) {
                            $query->where('audience_scope', 'apartment');
                        }

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
        }

        $canCreatePost = $isVerified;

        if ($canQueryCommunityFeed) {
            $posts = $postsQuery
                ->paginate(20)
                ->withQueryString();
        } else {
            $page = max(1, (int) $request->query('page', 1));
            $posts = new LengthAwarePaginator(
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

        $posts->through(function (Post $post) use ($user, $apartmentId, $hasPostLikesTable) {
            $canRead = $this->permissionService->canReadPostDetail($user, $post);
            $authorName = $post->is_anonymous ? '익명' : trim((string) ($post->user?->name ?? '알 수 없음'));
            $authorInitial = mb_substr($authorName !== '' ? $authorName : 'U', 0, 1);
            $pollPreview = $this->buildPollPreview($post);

            return [
                'id' => (int) $post->id,
                'title' => $post->title,
                'created_at' => $post->created_at,
                'created_label' => $post->created_at?->diffForHumans() ?? '-',
                'author_name' => $authorName,
                'author_initial' => mb_strtoupper($authorInitial),
                'author_is_verified' => $post->user !== null && $this->permissionService->hasVerifiedRole($post->user),
                'board_name' => $post->board->name,
                'is_poll' => (bool) ($pollPreview['is_poll'] ?? false),
                'poll_question' => (string) ($pollPreview['question'] ?? ''),
                'poll_options_preview' => (array) ($pollPreview['options'] ?? []),
                'poll_total_votes' => (int) ($pollPreview['total_votes'] ?? 0),
                'topic_name' => $post->topic?->name,
                'apartment_name' => $post->apartment?->name ?: ($post->residenceComplex?->displayName() ?: '공동주택'),
                'body_preview' => $this->buildBodyPreview($post->body),
                'media_items' => $canRead ? $this->extractMediaItems($post) : [],
                'sido' => $post->apartment?->sido ?: ($post->region_sido ?? ''),
                'sigungu' => $post->apartment?->sigungu ?: ($post->region_sigungu ?? ''),
                'apartment_id' => (int) $post->apartment_id,
                'view_count' => (int) $post->view_count,
                'comment_count' => (int) $post->comment_count,
                'like_count' => (int) ($post->likes_count ?? 0),
                'liked_by_me' => $hasPostLikesTable && $user
                    ? PostLike::query()->where('post_id', $post->id)->where('user_id', $user->id)->exists()
                    : false,
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

        // Get boards from "커뮤니티" category for board tab menu
        $boardsFromCommunityCategory = collect();
        $encodedTopic = $topic !== '' ? urlencode($topic) : '';
        
        // Build helper function for URL construction with preserved parameters
        $buildUrl = function (array $overrides = []) use ($apartmentId, $scope, $topic, $selectedBoardSlug, $encodedTopic, $regionSido, $regionSigungu, $regionReset) {
            $params = [
                'scope' => $overrides['scope'] ?? $scope,
                'apartment_id' => $apartmentId,
            ];
            foreach (['sido' => $regionSido, 'sigungu' => $regionSigungu] as $key => $value) {
                if (($overrides[$key] ?? $value) !== '') {
                    $params[$key] = $overrides[$key] ?? $value;
                }
            }
            if ($regionReset) {
                $params['region'] = 'all';
            }
            if (!empty($overrides['topic'] ?? $topic)) {
                $params['topic'] = $overrides['topic'] ?? $encodedTopic;
            }
            if (!empty($overrides['board'] ?? $selectedBoardSlug)) {
                $params['board'] = $overrides['board'] ?? $selectedBoardSlug;
            }
            return '/community?' . http_build_query($params);
        };
        
        // Build URLs for scope tabs
        $scopeTabUrls = [
            'all' => $buildUrl(['scope' => 'all']),
            'region' => $buildUrl(['scope' => 'region']),
            'apartment' => $buildUrl(['scope' => 'apartment']),
        ];
        
        // Build URLs for topic tabs
        $topicTabUrls = [];
        $topicTabUrls['all'] = $buildUrl(['topic' => '']);
        foreach ($topicFacets as $facet) {
            $topicTabUrls[$facet->slug] = $buildUrl(['topic' => $facet->slug]);
        }
        
        if (Schema::hasTable('board_categories') && Schema::hasTable('boards')) {
            $communityCategory = \App\Models\BoardCategory::query()
                ->where('name', '커뮤니티')
                ->where(function ($query) use ($apartmentId) {
                    $query->where('apartment_id', $apartmentId)
                        ->orWhereNull('apartment_id');
                })
                ->first();
            
            if ($communityCategory) {
                $boardsFromCommunityCategory = $communityCategory->boards()
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get(['id', 'name', 'slug'])
                    ->values();
            }
        }
        
        // Build URLs for board tabs
        $boardTabUrls = [];
        $boardTabUrls['all'] = $buildUrl(['board' => '']);
        foreach ($boardsFromCommunityCategory as $board) {
            $boardTabUrls[$board->slug] = $buildUrl(['board' => $board->slug]);
        }

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

        // 검색 쿼리가 있으면 검색 결과 페이지로 렌더링
        if ($searchQuery !== '') {
            return view('community.search', [
                'apartmentId' => $apartmentId,
                'apartmentName' => $apartmentName,
                'searchQuery' => $searchQuery,
                'posts' => $posts,
                'isVerified' => $isVerified,
            ]);
        }

        return view('community.index', [
            'apartmentId' => $apartmentId,
            'apartmentName' => $apartmentName,
            'regionLabel' => $regionLabel !== '' ? $regionLabel : '우리 동네',
            'posts' => $posts,
            'scope' => $scope,
            'regionSido' => $regionSido,
            'regionSigungu' => $regionSigungu,
            'topic' => $topic,
            'searchQuery' => $searchQuery,
            'isVerified' => $isVerified,
            'requiresSignupForScope' => $requiresSignupForScope,
            'topicFacets' => $topicFacets,
            'canCreatePost' => (bool) $canCreatePost,
            'shouldSplitApartmentFeed' => $shouldSplitApartmentFeed,
            'preferredApartmentName' => trim((string) ($user?->home_apartment_name ?? '')),
            'ownApartmentPosts' => $ownApartmentPosts,
            'otherApartmentPosts' => $otherApartmentPosts,
            'boardsFromCommunityCategory' => $boardsFromCommunityCategory,
            'selectedBoardSlug' => $selectedBoardSlug,
            'scopeTabUrls' => $scopeTabUrls,
            'topicTabUrls' => $topicTabUrls,
            'boardTabUrls' => $boardTabUrls,
            'emptyFeedMessage' => $emptyFeedMessages[array_rand($emptyFeedMessages)],
        ]);
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

    private function resolveUserApartmentId(?User $user): int
    {
        if (! $user) {
            return 0;
        }

        return (int) ($user->preferred_apartment_id ?? 0);
    }

    private function applyRegionFilter(Builder $query, string $sido, string $sigungu): void
    {
        $query->where(function (Builder $postQuery) use ($sido, $sigungu) {
            $postQuery->where(function (Builder $regionQuery) use ($sido, $sigungu) {
                $this->applyRegionColumns($regionQuery, 'region_', $sido, $sigungu);
            })->orWhereHas('apartment', function (Builder $apartmentQuery) use ($sido, $sigungu) {
                $this->applyRegionColumns($apartmentQuery, '', $sido, $sigungu);
            });
        });
    }

    private function applyRegionColumns(Builder $query, string $prefix, string $sido, string $sigungu): void
    {
        if ($sido !== '') {
            $query->where($prefix.'sido', 'like', '%'.$sido.'%');
        }

        if ($sigungu !== '') {
            $column = $prefix.'sigungu';
            $query->where(function (Builder $sigunguQuery) use ($column, $sigungu) {
                $sigunguQuery->where($column, 'like', '%'.$sigungu.'%');
                $compactSigungu = str_replace([' ', '시'], '', $sigungu);
                if ($compactSigungu !== '') {
                    $sigunguQuery->orWhereRaw("REPLACE(REPLACE(COALESCE({$column}, ''), ' ', ''), '시', '') LIKE ?", ['%'.$compactSigungu.'%']);
                }
            });
        }

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
            ->first(fn (PostFile $file) => $this->resolveMediaType($file) === 'image');

        if ($imageFile) {
            return '/community/files/'.$imageFile->id;
        }

        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', (string) $post->body, $matches)) {
            return $matches[1] ?? null;
        }

        return null;
    }
}
