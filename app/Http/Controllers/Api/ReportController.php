<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class ReportController extends Controller
{
    public function __construct(private readonly PermissionService $permissionService)
    {
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'reportable_type' => ['required', 'in:post,comment'],
            'reportable_id' => ['required', 'integer'],
            'reason' => ['required', 'in:spam,abuse,illegal,other'],
            'detail' => ['nullable', 'string', 'max:2000'],
        ]);

        [$className, $target] = match ($data['reportable_type']) {
            'post' => [Post::class, Post::query()->find($data['reportable_id'])],
            'comment' => [Comment::class, Comment::query()->with('post')->find($data['reportable_id'])],
        };

        if (! $target) {
            return response()->json(['message' => 'Report target not found.'], 404);
        }

        $apartmentId = $data['reportable_type'] === 'post'
            ? (int) $target->apartment_id
            : (int) $target->post?->apartment_id;

        if (! $apartmentId || ! $this->permissionService->hasApartmentRole($request->user(), $apartmentId, 'resident')) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($data['reportable_type'] === 'post' && $target->user_id === $request->user()->id) {
            return response()->json(['message' => 'Cannot report own post.'], 422);
        }

        if ($data['reportable_type'] === 'comment' && $target->user_id === $request->user()->id) {
            return response()->json(['message' => 'Cannot report own comment.'], 422);
        }

        try {
            $report = Report::query()->create([
                'reporter_id' => $request->user()->id,
                'reportable_type' => $className,
                'reportable_id' => $data['reportable_id'],
                'reason' => $data['reason'],
                'detail' => $data['detail'] ?? null,
                'status' => 'pending',
            ]);
        } catch (QueryException $e) {
            return response()->json(['message' => 'Already reported.'], 409);
        }

        return response()->json(['data' => $report], 201);
    }
}
