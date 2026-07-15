<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\PostLike;
use App\Models\PointTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $items = collect();

        // 내 게시글에 달린 댓글 (최근 50개)
        $myPostIds = Post::where('user_id', $user->id)->pluck('id');

        Comment::with('post')
            ->whereIn('post_id', $myPostIds)
            ->where('user_id', '!=', $user->id)
            ->whereNull('deleted_at')
            ->latest()
            ->limit(50)
            ->get()
            ->each(function ($comment) use (&$items) {
                $items->push((object) [
                    'type'       => 'comment',
                    'title'      => '내 게시글에 댓글이 달렸습니다',
                    'body'       => mb_strimwidth(strip_tags($comment->body), 0, 60, '…'),
                    'link'       => '/community/posts/' . $comment->post_id,
                    'created_at' => $comment->created_at,
                ]);
            });

        // 내 게시글에 달린 좋아요 (최근 50개)
        PostLike::with('post')
            ->whereIn('post_id', $myPostIds)
            ->where('user_id', '!=', $user->id)
            ->latest()
            ->limit(50)
            ->get()
            ->each(function ($like) use (&$items) {
                $items->push((object) [
                    'type'       => 'like',
                    'title'      => '게시글에 좋아요가 달렸습니다',
                    'body'       => $like->post?->title ?? '',
                    'link'       => '/community/posts/' . $like->post_id,
                    'created_at' => $like->created_at,
                ]);
            });

        // 관리자 포인트 지급/차감 (최근 30개)
        PointTransaction::where('user_id', $user->id)
            ->where('source', 'admin')
            ->latest()
            ->limit(30)
            ->get()
            ->each(function ($tx) use (&$items) {
                $sign = $tx->amount >= 0 ? '+' : '';
                $items->push((object) [
                    'type'       => 'point',
                    'title'      => $tx->amount >= 0 ? '포인트가 지급되었습니다' : '포인트가 차감되었습니다',
                    'body'       => ($tx->note ?: '') . " ({$sign}{$tx->amount}P)",
                    'link'       => '/points',
                    'created_at' => $tx->created_at,
                ]);
            });

        // 공지사항 (최근 20개)
        Post::where('is_notice', true)
            ->latest()
            ->limit(20)
            ->get()
            ->each(function ($post) use (&$items) {
                $items->push((object) [
                    'type'       => 'notice',
                    'title'      => '[공지] ' . $post->title,
                    'body'       => '',
                    'link'       => '/community/posts/' . $post->id,
                    'created_at' => $post->created_at,
                ]);
            });

        $notifications = $items->sortByDesc('created_at')->values();

        return view('notifications', compact('notifications'));
    }
}
