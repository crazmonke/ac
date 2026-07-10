<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostLike;
use App\Services\FcmMessagingService;
use Illuminate\Http\Request;

class PostLikeController extends Controller
{
    public function __construct(
        private readonly FcmMessagingService $fcmMessagingService,
    ) {
    }

    public function toggle(Request $request, int $postId)
    {
        $post = Post::query()->findOrFail($postId);
        $userId = $request->user()->id;

        $existing = PostLike::query()
            ->where('post_id', $postId)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            $existing->delete();
            $post->decrement('like_count');

            return response()->json(['liked' => false, 'like_count' => max(0, $post->like_count)]);
        }

        PostLike::query()->create(['post_id' => $postId, 'user_id' => $userId]);
        $post->increment('like_count');

        if ($post->user_id && $post->user_id !== $userId) {
            $this->fcmMessagingService->sendLikeToUser((int) $post->user_id, $postId, (int) $post->apartment_id, [
                'title' => $post->title,
            ]);
        }

        return response()->json(['liked' => true, 'like_count' => $post->like_count], 201);
    }
}
