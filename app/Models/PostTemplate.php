<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

class PostTemplate extends Model
{
    use SoftDeletes;

    public const MAX_QUESTIONS = 10;

    public const QUESTION_TYPES = ['single', 'multiple', 'text', 'yes_no'];

    protected $fillable = [
        'name',
        'description',
        'title_template',
        'questions',
        'board_slugs',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'questions' => 'array',
        'board_slugs' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at', 'desc');
    }

    /**
     * 해당 게시판(slug 기준 — 게시판은 단지별로 복제되므로 slug가 논리 단위)에서
     * 사용 가능한 활성 템플릿 목록.
     * board_slugs는 JSON 컬럼이므로 DB 방언을 타지 않도록 PHP에서 필터한다.
     */
    public static function availableForBoard(string $boardSlug): Collection
    {
        if (! Schema::hasTable('post_templates')) {
            return new Collection();
        }

        return static::active()->ordered()->get()
            ->filter(fn (self $template) => $template->isAvailableForBoard($boardSlug))
            ->values();
    }

    public function isAvailableForBoard(string $boardSlug): bool
    {
        $slugs = $this->board_slugs;

        if (empty($slugs)) {
            return true;
        }

        return in_array($boardSlug, array_map('strval', $slugs), true);
    }
}
