<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\Report;

class AdminDashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'boardsCount' => Board::query()->count(),
            'pendingReportsCount' => Report::query()->where('status', 'pending')->count(),
            'latestReports' => Report::query()->latest()->limit(10)->get(),
        ]);
    }

    public function boards()
    {
        return view('admin.boards', [
            'boards' => Board::query()->with('category')->orderBy('id', 'desc')->limit(100)->get(),
        ]);
    }

    public function reports()
    {
        return view('admin.reports', [
            'reports' => Report::query()->latest()->limit(100)->get(),
        ]);
    }
}
