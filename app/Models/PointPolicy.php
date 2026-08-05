<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointPolicy extends Model
{
    protected $fillable = [
        'post_points',
        'comment_points',
        'daily_max_points',
        'min_spend_points',
        'nickname_change_points',
        'daily_free_messages',
        'message_send_points',
        'expiry_months',
    ];

    protected function casts(): array
    {
        return [
            'post_points'      => 'integer',
            'comment_points'   => 'integer',
            'daily_max_points' => 'integer',
            'min_spend_points' => 'integer',
            'nickname_change_points' => 'integer',
            'daily_free_messages' => 'integer',
            'message_send_points' => 'integer',
            'expiry_months'    => 'integer',
        ];
    }

    public static function getPolicy(): self
    {
        return static::query()->first() ?? static::create([
            'post_points'      => 1,
            'comment_points'   => 1,
            'daily_max_points' => 10,
            'min_spend_points' => 1000,
            'nickname_change_points' => 100,
            'daily_free_messages' => 5,
            'message_send_points' => 10,
            'expiry_months'    => null,
        ]);
    }
}
