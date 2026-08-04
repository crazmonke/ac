<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'parent_message_id',
        'content',
        'read_at',
        'sender_hidden_at',
        'receiver_hidden_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'sender_hidden_at' => 'datetime',
            'receiver_hidden_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_message_id');
    }

    /** 두 사용자 간 대화(양방향) 쪽지 */
    public function scopeBetween(Builder $query, int $userId, int $peerId): Builder
    {
        return $query->where(function (Builder $q) use ($userId, $peerId) {
            $q->where(function (Builder $inner) use ($userId, $peerId) {
                $inner->where('sender_id', $userId)->where('receiver_id', $peerId);
            })->orWhere(function (Builder $inner) use ($userId, $peerId) {
                $inner->where('sender_id', $peerId)->where('receiver_id', $userId);
            });
        });
    }

    /** 특정 사용자가 참여한 모든 쪽지 */
    public function scopeInvolving(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $q) use ($userId) {
            $q->where('sender_id', $userId)->orWhere('receiver_id', $userId);
        });
    }

    /** 해당 사용자가 감추지 않은(쪽지함에 보이는) 쪽지 */
    public function scopeVisibleTo(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $q) use ($userId) {
            $q->where(function (Builder $inner) use ($userId) {
                $inner->where('sender_id', $userId)->whereNull('sender_hidden_at');
            })->orWhere(function (Builder $inner) use ($userId) {
                $inner->where('receiver_id', $userId)->whereNull('receiver_hidden_at');
            });
        });
    }

    public static function unreadCountFor(int $userId): int
    {
        return static::query()
            ->where('receiver_id', $userId)
            ->whereNull('read_at')
            ->whereNull('receiver_hidden_at')
            ->count();
    }
}
