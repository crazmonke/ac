<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\PostLike;
use App\Models\PointTransaction;
use App\Models\UserNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $notifications = $this->loadStoredNotifications($user->id)
            ->concat($this->loadLegacyNotifications($user->id))
            ->sortByDesc('created_at')
            ->unique('unique_key')
            ->values();

        return view('notifications', compact('notifications'));
    }

    private function loadStoredNotifications(int $userId): Collection
    {
        return UserNotification::query()
            ->where('user_id', $userId)
            ->latest()
            ->limit(100)
            ->get()
            ->map(function (UserNotification $notification) {
                return (object) [
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'body' => $notification->body ?? '',
                    'link' => $notification->link ?: '/notifications',
                    'created_at' => $notification->created_at,
                    'unique_key' => $notification->source_type && $notification->source_id
                        ? $notification->source_type . ':' . $notification->source_id
                        : 'user_notification:' . $notification->id,
                ];
            });
    }

    private function loadLegacyNotifications(int $userId): Collection
    {
        $items = collect();

        // 내 게시글에 달린 댓글 (최근 50개)
        $myPostIds = Post::where('user_id', $userId)->pluck('id');

        Comment::with('post')
            ->whereIn('post_id', $myPostIds)
            ->where('user_id', '!=', $userId)
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
                    'unique_key' => 'comment:' . $comment->id,
                ]);
            });

        // 내 게시글에 달린 좋아요 (최근 50개)
        PostLike::with('post')
            ->whereIn('post_id', $myPostIds)
            ->where('user_id', '!=', $userId)
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
                    'unique_key' => 'post_like:' . $like->id,
                ]);
            });

        // 관리자 포인트 지급/차감 (최근 30개)
        PointTransaction::where('user_id', $userId)
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
                    'unique_key' => 'point_transaction:' . $tx->id,
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
                    'unique_key' => 'notice_post:' . $post->id,
                ]);
            });

        return $items;
    }
}
