<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class ReportWebController extends Controller
{
    public function create(Request $request)
    {
        return view('reports.create', [
            'apartmentId' => (int) $request->query('apartment_id', 1),
            'defaultType' => (string) $request->query('type', 'post'),
            'defaultId' => (int) $request->query('id', 0),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'reportable_type' => ['required', 'in:post,comment'],
            'reportable_id' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'in:spam,abuse,illegal,other'],
            'detail' => ['nullable', 'string', 'max:2000'],
            'apartment_id' => ['nullable', 'integer'],
        ]);

        [$className, $target] = match ($data['reportable_type']) {
            'post' => [Post::class, Post::query()->find($data['reportable_id'])],
            'comment' => [Comment::class, Comment::query()->with('post')->find($data['reportable_id'])],
        };

        if (! $target) {
            return back()->withErrors(['reportable_id' => '신고 대상이 존재하지 않습니다.'])->withInput();
        }

        if ((int) $target->user_id === (int) $request->user()->id) {
            return back()->withErrors(['reportable_id' => '본인이 작성한 대상은 신고할 수 없습니다.'])->withInput();
        }

        try {
            Report::query()->create([
                'reporter_id' => $request->user()->id,
                'reportable_type' => $className,
                'reportable_id' => $data['reportable_id'],
                'reason' => $data['reason'],
                'detail' => $data['detail'] ?? null,
                'status' => 'pending',
            ]);
        } catch (QueryException) {
            return back()->withErrors(['reportable_id' => '이미 신고한 대상입니다.'])->withInput();
        }

        return redirect('/?apartment_id=' . max(1, (int) ($data['apartment_id'] ?? 1)))
            ->with('status', '신고가 접수되었습니다. 운영팀이 검토 후 조치합니다.');
    }
}
