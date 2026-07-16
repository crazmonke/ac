<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\PointPolicy;
use App\Models\PointTransaction;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PointService
{
    public function __construct(
        private readonly UserNotificationService $userNotificationService,
    ) {
    }

    public function awardForPost(Post $post): void
    {
        if (! $post->user_id) {
            return;
        }

        $policy = PointPolicy::getPolicy();
        if ($policy->post_points <= 0) {
            return;
        }

        $user = User::find($post->user_id);
        if (! $user) {
            return;
        }

        $remaining = $policy->daily_max_points - $this->getDailyEarned($user);
        $toAward = min($policy->post_points, max(0, $remaining));
        if ($toAward <= 0) {
            return;
        }

        $this->record($user, $toAward, 'earn', 'post', $post->id, null, null, $policy);
    }

    public function awardForComment(Comment $comment): void
    {
        if (! $comment->user_id || ! $comment->post_id) {
            return;
        }

        $post = $comment->relationLoaded('post') ? $comment->post : Post::find($comment->post_id);
        if (! $post) {
            return;
        }

        // 본인 게시글 댓글은 포인트 없음
        if ((int) $comment->user_id === (int) $post->user_id) {
            return;
        }

        // 동일 게시글에 이미 댓글 포인트를 획득한 경우 중복 지급 안 함
        $alreadyEarned = PointTransaction::query()
            ->where('user_id', $comment->user_id)
            ->where('type', 'earn')
            ->where('source', 'comment')
            ->where('source_post_id', $post->id)
            ->exists();
        if ($alreadyEarned) {
            return;
        }

        $policy = PointPolicy::getPolicy();
        if ($policy->comment_points <= 0) {
            return;
        }

        $user = User::find($comment->user_id);
        if (! $user) {
            return;
        }

        $remaining = $policy->daily_max_points - $this->getDailyEarned($user);
        $toAward = min($policy->comment_points, max(0, $remaining));
        if ($toAward <= 0) {
            return;
        }

        $this->record($user, $toAward, 'earn', 'comment', $comment->id, null, $post->id, $policy);
    }

    public function reclaimForPost(Post $post): void
    {
        $earnTx = PointTransaction::query()
            ->where('type', 'earn')
            ->where('source', 'post')
            ->where('source_id', $post->id)
            ->first();

        if (! $earnTx) {
            return;
        }

        $user = User::find($post->user_id);
        if (! $user) {
            return;
        }

        $this->record($user, -$earnTx->amount, 'deduct', 'post', $post->id, '게시글 삭제 포인트 회수', null, null);
    }

    public function reclaimForComment(Comment $comment): void
    {
        $earnTx = PointTransaction::query()
            ->where('type', 'earn')
            ->where('source', 'comment')
            ->where('source_id', $comment->id)
            ->first();

        if (! $earnTx) {
            return;
        }

        $user = User::find($comment->user_id);
        if (! $user) {
            return;
        }

        $this->record($user, -$earnTx->amount, 'deduct', 'comment', $comment->id, '댓글 삭제 포인트 회수', null, null);
    }

    public function adminGrant(User $user, int $amount, string $note): void
    {
        $tx = $this->record($user, abs($amount), 'earn', 'admin', null, $note ?: '관리자 포인트 지급', null, null);
        $this->notifyAdminPointChange($user, $tx);
    }

    public function adminDeduct(User $user, int $amount, string $note): void
    {
        $tx = $this->record($user, -abs($amount), 'deduct', 'admin', null, $note ?: '관리자 포인트 차감', null, null);
        $this->notifyAdminPointChange($user, $tx);
    }

    private function getDailyEarned(User $user): int
    {
        return (int) PointTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', 'earn')
            ->whereIn('source', ['post', 'comment'])
            ->whereDate('created_at', today())
            ->sum('amount');
    }

    private function record(
        User $user,
        int $amount,
        string $type,
        string $source,
        ?int $sourceId,
        ?string $note,
        ?int $sourcePostId,
        ?PointPolicy $policy
    ): PointTransaction {
        return DB::transaction(function () use ($user, $amount, $type, $source, $sourceId, $note, $sourcePostId, $policy) {
            $lockedUser  = User::query()->where('id', $user->id)->lockForUpdate()->first();
            $newBalance  = max(0, (int) $lockedUser->point_balance + $amount);

            $expiresAt = null;
            if ($type === 'earn' && $policy && $policy->expiry_months) {
                $expiresAt = now()->addMonths($policy->expiry_months);
            }

            $transaction = PointTransaction::query()->create([
                'user_id'       => $user->id,
                'type'          => $type,
                'source'        => $source,
                'source_id'     => $sourceId,
                'source_post_id' => $sourcePostId,
                'amount'        => $amount,
                'balance_after' => $newBalance,
                'note'          => $note,
                'expires_at'    => $expiresAt,
            ]);

            $lockedUser->update(['point_balance' => $newBalance]);

            return $transaction;
        });
    }

    private function notifyAdminPointChange(User $user, PointTransaction $transaction): void
    {
        if ($transaction->source !== 'admin') {
            return;
        }

        $sign = $transaction->amount >= 0 ? '+' : '';
        $body = trim(($transaction->note ?: '') . (($transaction->note ? ' ' : '') . "({$sign}{$transaction->amount}P)"));

        $this->userNotificationService->notifyUser(
            (int) $user->id,
            'point',
            $transaction->amount >= 0 ? '포인트가 지급되었습니다' : '포인트가 차감되었습니다',
            $body,
            '/points',
            'point_transaction',
            (int) $transaction->id,
            [
                'amount' => (string) $transaction->amount,
                'source' => (string) $transaction->source,
            ]
        );
    }
}
