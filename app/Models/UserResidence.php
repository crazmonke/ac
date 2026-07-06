<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserResidence extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'complex_id',
        'building_id',
        'unit_id',
        'verification_method',
        'verification_status',
        'gps_verified_at',
        'distance_m',
        'evidence_meta',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'gps_verified_at' => 'datetime',
            'evidence_meta' => 'array',
            'is_primary' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function complex(): BelongsTo
    {
        return $this->belongsTo(ResidenceComplex::class, 'complex_id');
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(ResidenceBuilding::class, 'building_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ResidenceUnit::class, 'unit_id');
    }
}
