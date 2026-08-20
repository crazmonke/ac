<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\ModerationSetting;
use App\Models\Post;
use App\Models\Report;
use Illuminate\Support\Str;

class ContentModerationService
{
    public function findBlockedTerm(?string $content): ?string
    {
        $normalizedContent = $this->normalize($content);

        if ($normalizedContent === '') {
            return null;
        }

        foreach ($this->blockedTerms() as $term) {
            if (Str::contains($normalizedContent, $this->normalize($term))) {
                return $term;
            }
        }

        return null;
    }

    public function validateContent(?string $content): void
    {
        $blockedTerm = $this->findBlockedTerm($content);

        if ($blockedTerm !== null) {
            abort(422, '부적절한 표현이 포함되어 있어 등록할 수 없습니다.');
        }
    }

    public function applyReportAction(Report $report): void
    {
        if ($report->status !== 'hidden') {
            return;
        }

        $target = $report->reportable;
        if (! $target) {
            return;
        }

        $target->loadMissing('user');

        if ($target instanceof Post) {
            $target->forceFill(['visibility' => 'deleted'])->save();
            $target->user?->forceFill(['access_allowed' => false])->save();
        } elseif ($target instanceof Comment) {
            $target->delete();
            $target->user?->forceFill(['access_allowed' => false])->save();
        }
    }

    private function blockedTerms(): array
    {
        $configuredTerms = (string) (ModerationSetting::query()
            ->where('key', 'blocked_terms')
            ->value('value') ?? config('community.blocked_terms', ''));

        return collect(preg_split('/[,\r\n]+/u', $configuredTerms) ?: [])
            ->map(fn (string $term) => trim($term))
            ->filter(fn (string $term) => $term !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function normalize(?string $content): string
    {
        return Str::lower(preg_replace('/\s+/u', '', trim((string) $content)) ?? '');
    }
}