<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\ContentModerationService;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Report::query()->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json($query->paginate(30));
    }

    public function update(Request $request, int $id, ContentModerationService $contentModerationService)
    {
        $report = Report::query()->findOrFail($id);

        $data = $request->validate([
            'status' => ['required', 'in:pending,reviewed,dismissed,hidden'],
            'admin_note' => ['nullable', 'string'],
        ]);

        $report->status = $data['status'];
        $report->admin_note = $data['admin_note'] ?? null;
        $report->reviewed_at = now();
        $report->save();
        $contentModerationService->applyReportAction($report->load('reportable'));

        return response()->json(['data' => $report]);
    }
}
