<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\Post;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use App\Models\User;

class PostController extends Controller
{
    public function __construct(private readonly PermissionService $permissionService)
    {
    }

    public function index(int $boardId)
    {
        $posts = Post::query()
            ->where('board_id', $boardId)
            ->where('visibility', '!=', 'deleted')
            ->latest()
            ->paginate(20);

        return response()->json($posts);
    }

    public function store(Request $request, int $boardId)
    {
        $board = Board::query()->findOrFail($boardId);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string'],
            'is_notice' => ['sometimes', 'boolean'],
            'is_anonymous' => ['sometimes', 'boolean'],
            'visibility' => ['sometimes', 'in:public,resident_only,deleted'],
        ]);

        $data['user_id'] = $request->user()->id;
        $data['board_id'] = $board->id;
        $data['apartment_id'] = (int) $board->apartment_id;
        $data['is_notice'] = $data['is_notice'] ?? false;
        $data['is_anonymous'] = $data['is_anonymous'] ?? false;
        $data['visibility'] = $data['visibility'] ?? 'resident_only';

        $post = Post::query()->create($data);

        return response()->json(['data' => $post], 201);
    }

    public function show(Request $request, int $id)
    {
        $post = Post::query()->with(['comments', 'files', 'board'])->findOrFail($id);

        if (! $this->permissionService->hasBoardPermission($request->user(), $post->board, 'read')) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json(['data' => $post]);
    }

    public function update(Request $request, int $id)
    {
        $post = Post::query()->with('board')->findOrFail($id);

        if (! $this->permissionService->hasBoardPermission($request->user(), $post->board, 'write')) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (! $this->canEdit($request->user()->id, $request->user(), $post)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:160'],
            'body' => ['sometimes', 'string'],
            'is_notice' => ['sometimes', 'boolean'],
            'is_anonymous' => ['sometimes', 'boolean'],
            'visibility' => ['sometimes', 'in:public,resident_only,deleted'],
        ]);

        $post->fill($data)->save();

        return response()->json(['data' => $post]);
    }

    public function destroy(Request $request, int $id)
    {
        $post = Post::query()->with('board')->findOrFail($id);

        if (! $this->permissionService->hasBoardPermission($request->user(), $post->board, 'write')) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (! $this->canEdit($request->user()->id, $request->user(), $post)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $post->delete();

        return response()->noContent();
    }

    private function canEdit(int $userId, User $user, Post $post): bool
    {
        if ($post->user_id === $userId) {
            return true;
        }

        return $this->permissionService->hasAdminRole($user, (int) $post->apartment_id);
    }
}
