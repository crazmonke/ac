<?php

namespace App\Observers;

use App\Models\Post;
use App\Services\PointService;

class PostObserver
{
    public function __construct(private readonly PointService $pointService) {}

    public function created(Post $post): void
    {
        $this->pointService->awardForPost($post);
    }

    public function deleted(Post $post): void
    {
        $this->pointService->reclaimForPost($post);
    }
}
