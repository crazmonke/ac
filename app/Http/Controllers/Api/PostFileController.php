<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostFile;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostFileController extends Controller
{
    public function __construct(private readonly PermissionService $permissionService)
    {
    }

    public function store(Request $request, int $id)
    {
        $post = Post::query()->with('board')->findOrFail($id);

        if (! $this->permissionService->hasBoardPermission($request->user(), $post->board, 'write')) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (! $post->board->allow_file) {
            return response()->json(['message' => 'File upload disabled for this board.'], 422);
        }

        $data = $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,gif,pdf'],
        ]);

        $uploadedFile = $data['file'];
        $storedName = Str::uuid().'.'.$uploadedFile->getClientOriginalExtension();
        $path = $uploadedFile->storeAs('posts/'.$post->id, $storedName, 'local');

        $postFile = PostFile::query()->create([
            'post_id' => $post->id,
            'user_id' => $request->user()->id,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'stored_name' => $storedName,
            'disk' => 'local',
            'path' => $path,
            'mime_type' => (string) $uploadedFile->getMimeType(),
            'size' => $uploadedFile->getSize(),
        ]);

        return response()->json(['data' => $postFile], 201);
    }

    public function show(Request $request, int $id)
    {
        $postFile = PostFile::query()->with(['post.board'])->findOrFail($id);
        $post = $postFile->post;

        if (! $post || ! $post->board) {
            return response()->json(['message' => 'Invalid file relation.'], 422);
        }

        if (! $this->permissionService->hasBoardPermission($request->user(), $post->board, 'read')) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (! Storage::disk($postFile->disk)->exists($postFile->path)) {
            return response()->json(['message' => 'File not found on disk.'], 404);
        }

        return Storage::disk($postFile->disk)->download($postFile->path, $postFile->original_name);
    }

    public function destroy(Request $request, int $id)
    {
        $postFile = PostFile::query()->with('post')->findOrFail($id);
        $post = $postFile->post;

        if (! $post) {
            return response()->json(['message' => 'Invalid file relation.'], 422);
        }

        $user = $request->user();
        $isOwner = $postFile->user_id === $user->id;
        $isAdmin = $this->permissionService->hasAdminRole($user, (int) $post->apartment_id);

        if (! $isOwner && ! $isAdmin) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        Storage::disk($postFile->disk)->delete($postFile->path);
        $postFile->delete();

        return response()->noContent();
    }
}
