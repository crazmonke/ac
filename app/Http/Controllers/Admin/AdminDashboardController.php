<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\ApartmentMatchReview;
use App\Models\Board;
use App\Models\BoardCategory;
use App\Models\Post;
use App\Models\ResidenceComplex;
use App\Models\ResidenceMergeCandidate;
use App\Models\ResidentVerificationRequest;
use App\Models\Report;
use App\Models\User;
use App\Models\UserResidence;
use App\Models\UserRole;
use App\Services\ApartmentSelectionService;
use App\Services\FcmMessagingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class AdminDashboardController extends Controller
{
    private const VERIFIED_ROLES = ['resident', 'household_rep', 'owner_verified', 'tenant_verified'];

    public function __construct(
        private readonly ApartmentSelectionService $apartmentSelectionService,
        private readonly FcmMessagingService $fcmMessagingService,
    ) {
    }

    public function index()
    {
        return view('admin.dashboard', [
            'boardsCount' => Board::query()->count(),
            'pendingReportsCount' => Report::query()->where('status', 'pending')->count(),
            'pendingMatchReviewsCount' => ApartmentMatchReview::query()->where('status', 'pending')->count(),
            'pendingVerificationCount' => ResidentVerificationRequest::query()->where('status', 'pending')->count(),
            'latestReports' => Report::query()->latest()->limit(10)->get(),
        ]);
    }

    public function reviewQueue()
    {
        $matchReviews = ApartmentMatchReview::query()
            ->with(['user', 'suggestedApartment', 'resolvedApartment'])
            ->where('status', 'pending')
            ->latest()
            ->limit(50)
            ->get();

        $verificationRequests = ResidentVerificationRequest::query()
            ->with(['user', 'apartment'])
            ->where('status', 'pending')
            ->latest()
            ->limit(50)
            ->get();

        $residenceVerificationRequests = UserResidence::query()
            ->with(['user', 'complex', 'building', 'unit'])
            ->where('verification_status', 'pending')
            ->latest()
            ->limit(50)
            ->get();

        $mergeCandidates = ResidenceMergeCandidate::query()
            ->with(['sourceComplex', 'targetComplex'])
            ->where('status', 'pending')
            ->orderByDesc('score')
            ->limit(50)
            ->get();

        $matchSuggestions = $matchReviews->mapWithKeys(function (ApartmentMatchReview $review) {
            return [
                $review->id => $this->apartmentSelectionService->search($review->raw_apartment_name, 6),
            ];
        });

        return view('admin.review-queue', [
            'matchReviews' => $matchReviews,
            'verificationRequests' => $verificationRequests,
            'residenceVerificationRequests' => $residenceVerificationRequests,
            'mergeCandidates' => $mergeCandidates,
            'matchSuggestions' => $matchSuggestions,
        ]);
    }

    public function updateMergeCandidate(Request $request, int $id)
    {
        $candidate = ResidenceMergeCandidate::query()
            ->with(['sourceComplex', 'targetComplex'])
            ->findOrFail($id);

        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
        ]);

        DB::transaction(function () use ($request, $candidate, $data) {
            $candidate->fill([
                'status' => $data['status'],
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ])->save();

            if ($data['status'] !== 'approved') {
                return;
            }

            $source = $candidate->sourceComplex;
            $target = $candidate->targetComplex;

            if (! $source || ! $target || $source->id === $target->id) {
                return;
            }

            $source->fill([
                'status' => 'merged',
                'merged_into_id' => $target->id,
            ])->save();

            UserResidence::query()
                ->where('complex_id', $source->id)
                ->update(['complex_id' => $target->id]);

            User::query()
                ->where('preferred_residence_complex_id', $source->id)
                ->update(['preferred_residence_complex_id' => $target->id]);

            ResidentVerificationRequest::query()
                ->where('residence_complex_id', $source->id)
                ->update(['residence_complex_id' => $target->id]);

            ResidenceComplex::query()
                ->where('merged_into_id', $source->id)
                ->update(['merged_into_id' => $target->id]);
        });

        return redirect('/admin/review-queue')->with('status', '공동주택 중복 병합 검수 상태가 업데이트되었습니다.');
    }

    public function updateResidenceVerification(Request $request, int $id)
    {
        $userResidence = UserResidence::query()->with(['user', 'complex'])->findOrFail($id);

        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
        ]);

        $approved = $data['status'] === 'approved';

        $userResidence->fill([
            'verification_status' => $approved ? 'verified' : 'rejected',
            'verification_method' => 'admin',
        ])->save();

        if ($approved) {
            $region = $this->extractRegionFromResidenceAddress(
                (string) ($userResidence->building?->road_address ?: $userResidence->complex?->road_address ?: ''),
                (string) ($userResidence->building?->jibun_address ?: $userResidence->complex?->jibun_address ?: '')
            );

            $userResidence->user?->forceFill([
                'preferred_residence_complex_id' => $userResidence->complex_id,
                'preferred_residence_building_id' => $userResidence->building_id,
                'preferred_residence_unit_id' => $userResidence->unit_id,
                'home_sido' => $region['sido'],
                'home_sigungu' => $region['sigungu'],
                'home_eupmyeondong' => $region['eupmyeondong'],
                'home_apartment_name' => $userResidence->complex?->displayName(),
            ])->save();
        }

        return redirect('/admin/review-queue')->with('status', $approved ? '공동주택 인증을 승인했습니다.' : '공동주택 인증을 반려했습니다.');
    }

    public function retryResidenceVerification(Request $request, int $id)
    {
        $userResidence = UserResidence::query()
            ->with(['user.preferredApartment', 'complex', 'building'])
            ->findOrFail($id);

        $latitude = isset($userResidence->evidence_meta['latitude']) ? (float) $userResidence->evidence_meta['latitude'] : null;
        $longitude = isset($userResidence->evidence_meta['longitude']) ? (float) $userResidence->evidence_meta['longitude'] : null;

        if ($latitude === null || $longitude === null) {
            return redirect('/admin/review-queue')->with('status', '재검증 실패: 저장된 GPS 좌표가 없어 자동 재검증을 실행할 수 없습니다.');
        }

        if (! $userResidence->user || ! $userResidence->complex || ! $userResidence->building) {
            return redirect('/admin/review-queue')->with('status', '재검증 실패: 사용자/공동주택 데이터가 누락되었습니다.');
        }

        $result = $this->apartmentSelectionService->tryAutoApproveResidentByResidence(
            $userResidence->user,
            $userResidence->complex,
            $userResidence->building,
            $latitude,
            $longitude,
            'admin_reverify',
            $userResidence->user->preferredApartment
        );

        $evidence = is_array($userResidence->evidence_meta) ? $userResidence->evidence_meta : [];
        $evidence['retry'] = [
            'at' => now()->toDateTimeString(),
            'approved' => (bool) ($result['approved'] ?? false),
            'reason' => (string) ($result['reason'] ?? 'unknown'),
            'details' => $result['details'] ?? null,
        ];

        if (($result['approved'] ?? false) === true) {
            $userResidence->fill([
                'verification_status' => 'verified',
                'verification_method' => 'gps',
                'gps_verified_at' => now(),
                'distance_m' => isset($result['details']['distance_meters']) ? (int) $result['details']['distance_meters'] : null,
                'evidence_meta' => $evidence,
            ])->save();

            return redirect('/admin/review-queue')->with('status', '재검증 성공: 공동주택 인증이 자동 승인되었습니다.');
        }

        $userResidence->fill([
            'verification_status' => 'pending',
            'verification_method' => 'manual',
            'evidence_meta' => $evidence,
        ])->save();

        return redirect('/admin/review-queue')->with('status', '재검증 실패: 자동 승인 조건을 충족하지 않았습니다.');
    }

    public function bulkAutoApproveResidenceVerifications(Request $request)
    {
        $data = $request->validate([
            'mode' => ['required', 'in:preview,execute'],
            'hours' => ['required', 'integer', 'min:0', 'max:720'],
            'limit' => ['required', 'integer', 'min:1', 'max:2000'],
            'include_no_coordinates' => ['nullable', 'boolean'],
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $options = [
            '--hours' => (int) $data['hours'],
            '--limit' => (int) $data['limit'],
        ];

        if ($request->boolean('include_no_coordinates')) {
            $options['--approve-without-coordinates'] = true;
        }

        $adminNote = trim((string) ($data['admin_note'] ?? ''));
        if ($adminNote !== '') {
            $options['--admin-note'] = $adminNote;
        }

        if ($data['mode'] === 'execute') {
            $options['--execute'] = true;
            $options['--yes'] = true;
        }

        try {
            Artisan::call('residences:auto-approve-pending', $options);
            $output = trim((string) Artisan::output());
        } catch (\Throwable $e) {
            return redirect('/admin/review-queue')
                ->withErrors(['bulk_auto_approve' => '일괄 승인 실행 중 오류가 발생했습니다: ' . $e->getMessage()]);
        }

        $message = $data['mode'] === 'execute'
            ? '공동주택 인증 일괄 승인 실행이 완료되었습니다.'
            : '공동주택 인증 일괄 승인 미리보기가 완료되었습니다.';

        return redirect('/admin/review-queue')
            ->with('status', $message)
            ->with('bulkAutoApproveOutput', $output);
    }

    public function users(Request $request)
    {
        $keyword = trim((string) $request->query('q', ''));
        $sort = $request->query('sort', 'id');
        $dir = $request->query('dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['id', 'posts_count', 'comments_count', 'last_login_at', 'created_at', 'verified'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        $query = User::query()
            ->with(['preferredApartment', 'preferredResidenceComplex'])
            ->withCount(['posts', 'comments'])
            ->withExists([
                'userRoles as has_verified_role' => function ($query) {
                    $query->whereIn('role', self::VERIFIED_ROLES)
                        ->where(function ($subQuery) {
                            $subQuery->whereNull('expires_at')->orWhere('expires_at', '>', now());
                        });
                },
                'userResidences as has_verified_residence' => function ($query) {
                    $query->where('verification_status', 'verified');
                },
            ])
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($subQuery) use ($keyword) {
                    $subQuery->where('name', 'like', '%'.$keyword.'%')
                        ->orWhere('email', 'like', '%'.$keyword.'%')
                        ->orWhere('home_apartment_name', 'like', '%'.$keyword.'%')
                        ->orWhere('home_sigungu', 'like', '%'.$keyword.'%');
                });
            });

        if ($sort === 'verified') {
            $query->orderByRaw('(has_verified_role OR has_verified_residence) ' . $dir);
        } elseif ($sort === 'posts_count') {
            $query->orderBy('posts_count', $dir);
        } elseif ($sort === 'comments_count') {
            $query->orderBy('comments_count', $dir);
        } else {
            $query->orderBy($sort, $dir);
        }

        $users = $query->paginate(30)->withQueryString();

        $users->getCollection()->transform(function (User $member) {
            $regionLabel = trim(implode(' ', array_filter([
                $member->home_sido,
                $member->home_sigungu,
                $member->home_eupmyeondong,
            ])));

            if ($regionLabel === '' && $member->preferredResidenceComplex) {
                $region = $this->extractRegionFromResidenceAddress(
                    (string) ($member->preferredResidenceComplex->road_address ?? ''),
                    (string) ($member->preferredResidenceComplex->jibun_address ?? '')
                );
                $regionLabel = trim(implode(' ', array_filter([
                    $region['sido'],
                    $region['sigungu'],
                    $region['eupmyeondong'],
                ])));
            }

            $member->computed_region_label = $regionLabel;
            $member->computed_residence_name = $member->preferredApartment?->name
                ?: ($member->home_apartment_name ?: $member->preferredResidenceComplex?->displayName());
            $member->computed_is_verified = (bool) (($member->has_verified_role ?? false) || ($member->has_verified_residence ?? false));

            return $member;
        });

        return view('admin.users', [
            'users' => $users,
            'q' => $keyword,
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }

    public function notifications()
    {
        return view('admin.notifications');
    }

    public function posts(Request $request)
    {
        $keyword = trim((string) $request->query('q', ''));
        $boardId = $request->query('board_id');
        $visibility = $request->query('visibility', '');

        $posts = Post::query()
            ->with(['user', 'board'])
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($sub) use ($keyword) {
                    $sub->where('title', 'like', '%'.$keyword.'%')
                        ->orWhere('body', 'like', '%'.$keyword.'%');
                });
            })
            ->when($boardId, fn ($q) => $q->where('board_id', (int) $boardId))
            ->when($visibility !== '', fn ($q) => $q->where('visibility', $visibility))
            ->latest()
            ->paginate(40)
            ->withQueryString();

        $boards = Board::query()->orderBy('name')->get();

        return view('admin.posts', [
            'posts' => $posts,
            'boards' => $boards,
            'q' => $keyword,
            'boardId' => $boardId,
            'visibilityFilter' => $visibility,
        ]);
    }

    public function bulkPostAction(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', 'in:delete,hide,show'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $count = count($data['ids']);

        if ($data['action'] === 'delete') {
            Post::query()->whereIn('id', $data['ids'])->each(fn ($p) => $p->delete());
        } elseif ($data['action'] === 'hide') {
            Post::query()->whereIn('id', $data['ids'])->update(['visibility' => 'deleted']);
        } elseif ($data['action'] === 'show') {
            Post::query()->whereIn('id', $data['ids'])->update(['visibility' => 'resident_only']);
        }

        $label = match ($data['action']) {
            'delete' => '삭제',
            'hide' => '숨김',
            'show' => '표시 복원',
        };

        return redirect('/admin/posts')->with('status', "{$count}개 게시글을 {$label} 처리했습니다.");
    }

    public function destroyPost(int $id)
    {
        $post = Post::query()->findOrFail($id);
        $post->delete();

        return back()->with('status', '게시글이 삭제되었습니다.');
    }

    public function sendNotification(Request $request)
    {
        $data = $request->validate([
            'topic' => ['required', 'in:notice,new_post,comment'],
            'title' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:300'],
        ]);

        $this->fcmMessagingService->sendTopicNotification($data['topic'], $data['title'], $data['body']);

        return redirect('/admin/notifications')->with('status', '알림이 발송되었습니다.');
    }

    private function extractRegionFromResidenceAddress(string $roadAddress, string $jibunAddress = ''): array
    {
        $address = trim($roadAddress !== '' ? $roadAddress : $jibunAddress);

        if ($address === '') {
            return ['sido' => null, 'sigungu' => null, 'eupmyeondong' => null];
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
            'eupmyeondong' => $dong,
        ];
    }

    public function boards()
    {
        return view('admin.boards', [
            'boards' => Board::query()->with('category')->orderBy('id', 'desc')->limit(100)->get(),
            'categories' => BoardCategory::query()->orderBy('name')->get(),
            'apartments' => Apartment::query()->orderBy('name')->get(),
            'roleLabels' => config('community.board_permission_roles', []),
            'boardTypes' => config('community.board_types', []),
        ]);
    }

    public function storeBoard(Request $request)
    {
        $roles = array_keys(config('community.board_permission_roles', []));

        $data = $request->validate([
            'category_id' => ['required', 'exists:board_categories,id'],
            'apartment_id' => ['nullable', 'exists:apartments,id'],
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'required',
                'string',
                'max:80',
                Rule::unique('boards', 'slug')->where(function ($query) use ($request) {
                    $apartmentId = $request->input('apartment_id');

                    return $apartmentId
                        ? $query->where('apartment_id', $apartmentId)
                        : $query->whereNull('apartment_id');
                }),
            ],
            'description' => ['nullable', 'string'],
            'board_type' => ['required', Rule::in(config('community.board_types', []))],
            'read_role' => ['required', Rule::in($roles)],
            'write_role' => ['required', Rule::in($roles)],
            'comment_role' => ['required', Rule::in(array_merge($roles, ['none']))],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['allow_file'] = $request->boolean('allow_file');
        $data['allow_anonymous'] = $request->boolean('allow_anonymous');
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        Board::query()->create($data);

        return redirect('/admin/boards')->with('status', '게시판이 생성되었습니다.');
    }

    public function updateBoard(Request $request, int $id)
    {
        $board = Board::query()->findOrFail($id);
        $roles = array_keys(config('community.board_permission_roles', []));

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'required',
                'string',
                'max:80',
                Rule::unique('boards', 'slug')
                    ->where(function ($query) use ($board) {
                        return $board->apartment_id
                            ? $query->where('apartment_id', $board->apartment_id)
                            : $query->whereNull('apartment_id');
                    })
                    ->ignore($board->id),
            ],
            'board_type' => ['required', Rule::in(config('community.board_types', []))],
            'read_role' => ['required', Rule::in($roles)],
            'write_role' => ['required', Rule::in($roles)],
            'comment_role' => ['required', Rule::in(array_merge($roles, ['none']))],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['allow_file'] = $request->boolean('allow_file');
        $data['allow_anonymous'] = $request->boolean('allow_anonymous');
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $board->fill($data)->save();

        return redirect('/admin/boards')->with('status', '게시판이 수정되었습니다.');
    }

    public function destroyBoard(int $id)
    {
        Board::query()->findOrFail($id)->delete();

        return redirect('/admin/boards')->with('status', '게시판이 삭제되었습니다.');
    }

    public function reports()
    {
        return view('admin.reports', [
            'reports' => Report::query()->latest()->limit(100)->get(),
        ]);
    }

    public function updateReport(Request $request, int $id)
    {
        $report = Report::query()->findOrFail($id);

        $data = $request->validate([
            'status' => ['required', 'in:pending,reviewed,dismissed,hidden'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $report->status = $data['status'];
        $report->admin_note = $data['admin_note'] ?? null;
        $report->reviewed_at = now();
        $report->save();

        return redirect('/admin/reports')->with('status', '신고 상태가 업데이트되었습니다.');
    }

    public function updateMatchReview(Request $request, int $id)
    {
        $review = ApartmentMatchReview::query()->with('user')->findOrFail($id);

        $data = $request->validate([
            'status' => ['required', 'in:resolved,rejected'],
            'resolved_apartment_id' => ['nullable', 'integer', 'exists:apartments,id'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['status'] === 'resolved' && empty($data['resolved_apartment_id'])) {
            return back()->withErrors(['resolved_apartment_id' => '확정할 공동주택를 선택해 주세요.']);
        }

        $review->fill([
            'status' => $data['status'],
            'resolved_apartment_id' => $data['status'] === 'resolved' ? (int) $data['resolved_apartment_id'] : null,
            'admin_note' => $data['admin_note'] ?? null,
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ])->save();

        if ($data['status'] === 'resolved' && $review->user) {
            $review->user->preferred_apartment_id = (int) $data['resolved_apartment_id'];
            $review->user->save();
        }

        return redirect('/admin/review-queue')->with('status', '공동주택 매칭 검수 상태가 업데이트되었습니다.');
    }

    public function updateVerificationRequest(Request $request, int $id)
    {
        $verificationRequest = ResidentVerificationRequest::query()->with(['user', 'apartment'])->findOrFail($id);

        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $verificationRequest, $data) {
            $target = ResidentVerificationRequest::query()->firstOrCreate([
                'user_id' => $verificationRequest->user_id,
                'apartment_id' => $verificationRequest->apartment_id,
                'status' => $data['status'],
            ], [
                'request_note' => $verificationRequest->request_note,
            ]);

            $target->fill([
                'admin_note' => $data['admin_note'] ?? null,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ])->save();

            if ((int) $verificationRequest->id !== (int) $target->id) {
                $verificationRequest->delete();
            }

            if ($data['status'] === 'approved') {
                UserRole::query()->firstOrCreate([
                    'user_id' => $verificationRequest->user_id,
                    'apartment_id' => $verificationRequest->apartment_id,
                    'role' => 'resident',
                ], [
                    'granted_at' => now(),
                    'granted_by' => $request->user()->id,
                ]);

                ResidentVerificationRequest::query()
                    ->where('user_id', $verificationRequest->user_id)
                    ->where('apartment_id', $verificationRequest->apartment_id)
                    ->where('status', 'pending')
                    ->where('id', '!=', $target->id)
                    ->delete();

                if ($verificationRequest->residence_complex_id) {
                    UserResidence::query()
                        ->where('user_id', $verificationRequest->user_id)
                        ->where('complex_id', $verificationRequest->residence_complex_id)
                        ->update([
                            'verification_status' => 'verified',
                            'verification_method' => 'admin',
                        ]);
                }
            } else {
                if ($verificationRequest->residence_complex_id) {
                    UserResidence::query()
                        ->where('user_id', $verificationRequest->user_id)
                        ->where('complex_id', $verificationRequest->residence_complex_id)
                        ->update([
                            'verification_status' => 'rejected',
                            'verification_method' => 'admin',
                        ]);
                }
            }
        });

        return redirect('/admin/review-queue')->with('status', '입주민 인증 검수 상태가 업데이트되었습니다.');
    }

    public function updateUserVerification(Request $request, int $id)
    {
        $user = User::query()->with(['preferredApartment', 'preferredResidenceComplex', 'preferredResidenceBuilding', 'preferredResidenceUnit'])->findOrFail($id);

        $data = $request->validate([
            'action' => ['required', 'in:approve,reject'],
        ]);

        $adminUserId = (int) $request->user()->id;

        if (! $user->preferred_apartment_id && ! $user->preferred_residence_complex_id) {
            return back()->withErrors(['action' => '선택 공동주택 정보가 없는 회원은 인증 상태를 변경할 수 없습니다.']);
        }

        if (! $user->preferred_apartment_id && $user->preferred_residence_complex_id) {
            DB::transaction(function () use ($user, $adminUserId, $data) {
                $residence = UserResidence::query()->firstOrCreate([
                    'user_id' => $user->id,
                    'complex_id' => $user->preferred_residence_complex_id,
                ], [
                    'building_id' => $user->preferred_residence_building_id,
                    'unit_id' => $user->preferred_residence_unit_id,
                    'verification_method' => 'manual',
                    'verification_status' => 'pending',
                    'is_primary' => true,
                ]);

                $approved = $data['action'] === 'approve';

                $residence->fill([
                    'verification_status' => $approved ? 'verified' : 'rejected',
                    'verification_method' => 'admin',
                    'gps_verified_at' => $approved ? now() : null,
                ])->save();

                $region = $this->extractRegionFromResidenceAddress(
                    (string) ($user->preferredResidenceBuilding?->road_address ?: $user->preferredResidenceComplex?->road_address ?: ''),
                    (string) ($user->preferredResidenceBuilding?->jibun_address ?: $user->preferredResidenceComplex?->jibun_address ?: '')
                );

                $user->forceFill([
                    'home_sido' => $region['sido'],
                    'home_sigungu' => $region['sigungu'],
                    'home_eupmyeondong' => $region['eupmyeondong'],
                    'home_apartment_name' => $user->preferredResidenceComplex?->displayName(),
                ])->save();

                if (! $approved) {
                    UserRole::query()
                        ->where('user_id', $user->id)
                        ->whereIn('role', self::VERIFIED_ROLES)
                        ->delete();
                }
            });

            return back()->with('status', $data['action'] === 'approve' ? '회원 인증을 승인했습니다.' : '회원 인증을 반려했습니다.');
        }

        $apartmentId = (int) $user->preferred_apartment_id;

        DB::transaction(function () use ($user, $apartmentId, $adminUserId, $data) {
            if ($data['action'] === 'approve') {
                UserRole::query()->updateOrCreate([
                    'user_id' => $user->id,
                    'apartment_id' => $apartmentId,
                    'role' => 'resident',
                ], [
                    'granted_at' => now(),
                    'expires_at' => null,
                    'granted_by' => $adminUserId,
                ]);

                $approved = ResidentVerificationRequest::query()->firstOrCreate([
                    'user_id' => $user->id,
                    'apartment_id' => $apartmentId,
                    'status' => 'approved',
                ], [
                    'request_note' => '회원관리에서 승인 처리',
                ]);

                $approved->fill([
                    'admin_note' => '회원관리에서 승인 처리',
                    'reviewed_by' => $adminUserId,
                    'reviewed_at' => now(),
                ])->save();

                ResidentVerificationRequest::query()
                    ->where('user_id', $user->id)
                    ->where('apartment_id', $apartmentId)
                    ->where('status', 'pending')
                    ->delete();
            } else {
                UserRole::query()
                    ->where('user_id', $user->id)
                    ->where('apartment_id', $apartmentId)
                    ->whereIn('role', self::VERIFIED_ROLES)
                    ->delete();

                $rejected = ResidentVerificationRequest::query()->firstOrCreate([
                    'user_id' => $user->id,
                    'apartment_id' => $apartmentId,
                    'status' => 'rejected',
                ], [
                    'request_note' => '회원관리에서 반려 처리',
                ]);

                $rejected->fill([
                    'admin_note' => '회원관리에서 반려 처리',
                    'reviewed_by' => $adminUserId,
                    'reviewed_at' => now(),
                ])->save();

                ResidentVerificationRequest::query()
                    ->where('user_id', $user->id)
                    ->where('apartment_id', $apartmentId)
                    ->where('status', 'pending')
                    ->delete();
            }
        });

        return back()->with('status', $data['action'] === 'approve' ? '회원 인증을 승인했습니다.' : '회원 인증을 반려했습니다.');
    }

    public function updateUserAccess(Request $request, int $id)
    {
        $user = User::query()->findOrFail($id);

        $data = $request->validate([
            'action' => ['required', 'in:allow,deny'],
        ]);

        $user->forceFill([
            'access_allowed' => $data['action'] === 'allow',
        ])->save();

        return back()->with('status', $data['action'] === 'allow' ? '계정 접근을 허용했습니다.' : '계정 접근을 거부했습니다.');
    }

    public function withdrawUser(int $id)
    {
        $user = User::query()->findOrFail($id);

        if (! $user->withdrawn_at) {
            $user->forceFill([
                'withdrawn_at' => now(),
                'access_allowed' => false,
            ])->save();
        }

        return back()->with('status', '회원을 탈퇴 처리했습니다.');
    }

    public function updateUserProfileLock(Request $request, int $id)
    {
        $user = User::query()->findOrFail($id);

        $data = $request->validate([
            'action' => ['required', 'in:lock,unlock'],
        ]);

        $user->forceFill([
            'profile_locked' => $data['action'] === 'lock',
        ])->save();

        return back()->with('status', $data['action'] === 'lock' ? '프로필 수정이 잠금 처리되었습니다.' : '프로필 수정 잠금이 해제되었습니다.');
    }
}
