<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Board;
use App\Models\BoardCategory;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            'categories' => BoardCategory::query()->orderBy('name')->get(),
            'apartments' => Apartment::query()->orderBy('name')->get(),
            'roles' => array_keys(config('community.roles', [])),
            'boardTypes' => config('community.board_types', []),
        ]);
    }

    public function storeBoard(Request $request)
    {
        $roles = array_keys(config('community.roles', []));

        $data = $request->validate([
            'category_id' => ['required', 'exists:board_categories,id'],
            'apartment_id' => ['nullable', 'exists:apartments,id'],
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:80', Rule::unique('boards', 'slug')->where('apartment_id', $request->input('apartment_id'))],
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
        $roles = array_keys(config('community.roles', []));

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'required',
                'string',
                'max:80',
                Rule::unique('boards', 'slug')
                    ->where('apartment_id', $board->apartment_id)
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
}
