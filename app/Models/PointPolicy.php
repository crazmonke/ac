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
        'expiry_months',
    ];

    protected function casts(): array
    {
        return [
            'post_points'      => 'integer',
            'comment_points'   => 'integer',
            'daily_max_points' => 'integer',
            'min_spend_points' => 'integer',
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
            'expiry_months'    => null,
        ]);
    }
}
