<?php

namespace App\Http\Controllers;

use App\Models\PointTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserPointController extends Controller
{
    public function index(Request $request)
    {
        $user   = Auth::user();
        $period = $request->query('period', 'all');
        $type   = $request->query('type', 'all');

        $txQuery = PointTransaction::query()
            ->where('user_id', $user->id)
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

        return view('user.points', compact('user', 'transactions', 'period', 'type'));
    }
}
