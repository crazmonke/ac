<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'source',
        'source_id',
        'source_post_id',
        'amount',
        'balance_after',
        'note',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'amount'     => 'integer',
            'balance_after' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourceLabel(): string
    {
        return match ($this->source) {
            'post'    => '게시글 작성',
            'comment' => '댓글 작성',
            'admin'   => '관리자 지급',
            'system'  => '시스템',
            default   => $this->source,
        };
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'earn'   => '적립',
            'deduct' => '차감',
            'expire' => '소멸',
            default  => $this->type,
        };
    }
}
