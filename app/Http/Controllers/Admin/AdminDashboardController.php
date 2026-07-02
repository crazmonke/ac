<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\ApartmentMatchReview;
use App\Models\Board;
use App\Models\BoardCategory;
use App\Models\ResidentVerificationRequest;
use App\Models\Report;
use App\Models\User;
use App\Models\UserRole;
use App\Services\ApartmentSelectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class AdminDashboardController extends Controller
{
    private const VERIFIED_ROLES = ['resident', 'household_rep', 'owner_verified', 'tenant_verified'];

    public function __construct(private readonly ApartmentSelectionService $apartmentSelectionService)
    {
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

        $matchSuggestions = $matchReviews->mapWithKeys(function (ApartmentMatchReview $review) {
            return [
                $review->id => $this->apartmentSelectionService->search($review->raw_apartment_name, 6),
            ];
        });

        return view('admin.review-queue', [
            'matchReviews' => $matchReviews,
            'verificationRequests' => $verificationRequests,
            'matchSuggestions' => $matchSuggestions,
        ]);
    }

    public function users(Request $request)
    {
        $keyword = trim((string) $request->query('q', ''));

        $users = User::query()
            ->with('preferredApartment')
            ->withCount(['posts', 'comments'])
            ->withExists([
                'userRoles as has_verified_role' => function ($query) {
                    $query->whereIn('role', self::VERIFIED_ROLES)
                        ->where(function ($subQuery) {
                            $subQuery->whereNull('expires_at')->orWhere('expires_at', '>', now());
                        });
                },
            ])
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($subQuery) use ($keyword) {
                    $subQuery->where('name', 'like', '%'.$keyword.'%')
                        ->orWhere('email', 'like', '%'.$keyword.'%')
                        ->orWhere('home_apartment_name', 'like', '%'.$keyword.'%')
                        ->orWhere('home_sigungu', 'like', '%'.$keyword.'%');
                });
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.users', [
            'users' => $users,
            'q' => $keyword,
        ]);
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
            return back()->withErrors(['resolved_apartment_id' => '확정할 아파트를 선택해 주세요.']);
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

        return redirect('/admin/review-queue')->with('status', '아파트 매칭 검수 상태가 업데이트되었습니다.');
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
            }
        });

        return redirect('/admin/review-queue')->with('status', '입주민 인증 검수 상태가 업데이트되었습니다.');
    }

    public function updateUserVerification(Request $request, int $id)
    {
        $user = User::query()->with('preferredApartment')->findOrFail($id);

        $data = $request->validate([
            'action' => ['required', 'in:approve,reject'],
        ]);

        if (! $user->preferred_apartment_id) {
            return back()->withErrors(['action' => '선택 아파트가 없는 회원은 인증 상태를 변경할 수 없습니다.']);
        }

        $apartmentId = (int) $user->preferred_apartment_id;
        $adminUserId = (int) $request->user()->id;

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
