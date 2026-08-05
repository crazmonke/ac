<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PointPolicy;
use App\Models\PointTransaction;
use App\Models\User;
use App\Services\PointService;
use Illuminate\Http\Request;

class AdminPointController extends Controller
{
    public function __construct(private readonly PointService $pointService) {}

    public function index(Request $request)
    {
        $q    = trim((string) $request->query('q', ''));
        $sort = $request->query('sort', 'balance');
        $dir  = $request->query('dir', 'desc');

        $allowedSorts = ['name', 'email', 'point_balance', 'last_login_at'];
        $sortCol = in_array($sort, $allowedSorts, true) ? $sort : 'point_balance';
        $sortDir = $dir === 'asc' ? 'asc' : 'desc';

        $usersQuery = User::query()->whereNull('withdrawn_at');

        if ($q !== '') {
            $usersQuery->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%");
            });
        }

        $users = $usersQuery->orderBy($sortCol, $sortDir)->paginate(50)->withQueryString();

        return view('admin.points', compact('users', 'q', 'sort', 'dir'));
    }

    public function policy()
    {
        $policy = PointPolicy::getPolicy();

        return view('admin.points-policy', compact('policy'));
    }

    public function updatePolicy(Request $request)
    {
        $data = $request->validate([
            'post_points'      => ['required', 'integer', 'min:0', 'max:9999'],
            'comment_points'   => ['required', 'integer', 'min:0', 'max:9999'],
            'daily_max_points' => ['required', 'integer', 'min:1', 'max:99999'],
            'min_spend_points' => ['required', 'integer', 'min:0', 'max:999999'],
            'nickname_change_points' => ['required', 'integer', 'min:0', 'max:999999'],
            'daily_free_messages' => ['required', 'integer', 'min:0', 'max:999'],
            'message_send_points' => ['required', 'integer', 'min:0', 'max:999999'],
            'expiry_months'    => ['nullable', 'integer', 'min:1', 'max:120'],
        ]);

        $policy = PointPolicy::getPolicy();
        $policy->update($data);

        return back()->with('status', '포인트 정책이 업데이트되었습니다.');
    }

    public function userDetail(Request $request, int $userId)
    {
        $user   = User::findOrFail($userId);
        $period = $request->query('period', 'all');
        $type   = $request->query('type', 'all');

        $txQuery = PointTransaction::query()
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        if ($period !== 'all') {
            $from = match ($period) {
                '7d'   => now()->subDays(7),
                '30d'  => now()->subDays(30),
                '3m'   => now()->subMonths(3),
                '6m'   => now()->subMonths(6),
                '1y'   => now()->subYear(),
                default => null,
            };
            if ($from) {
                $txQuery->where('created_at', '>=', $from);
            }
        }

        if ($type !== 'all') {
            $txQuery->where('type', $type);
        }

        $transactions = $txQuery->paginate(30)->withQueryString();

        return view('admin.points-user', compact('user', 'transactions', 'period', 'type'));
    }

    public function grant(Request $request, int $userId)
    {
        $user = User::findOrFail($userId);
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:999999'],
            'note'   => ['nullable', 'string', 'max:200'],
        ], [
            'amount.min' => '지급 포인트는 1 이상이어야 합니다.',
        ]);

        $this->pointService->adminGrant($user, $data['amount'], $data['note'] ?? '');

        return back()->with('status', "{$user->name}님에게 {$data['amount']}P 지급 완료.");
    }

    public function deduct(Request $request, int $userId)
    {
        $user = User::findOrFail($userId);
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:999999'],
            'note'   => ['nullable', 'string', 'max:200'],
        ], [
            'amount.min' => '차감 포인트는 1 이상이어야 합니다.',
        ]);

        $this->pointService->adminDeduct($user, $data['amount'], $data['note'] ?? '');

        return back()->with('status', "{$user->name}님에게서 {$data['amount']}P 차감 완료.");
    }
}
