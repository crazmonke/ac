<?php

namespace App\Observers;

use App\Models\Comment;
use App\Services\PointService;

class CommentObserver
{
    public function __construct(private readonly PointService $pointService) {}

    public function created(Comment $comment): void
    {
        $this->pointService->awardForComment($comment);
    }

    public function deleted(Comment $comment): void
    {
        $this->pointService->reclaimForComment($comment);
    }
}
