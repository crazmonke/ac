<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationalMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_name',
        'user_id',
        'complex_id',
        'building_id',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
