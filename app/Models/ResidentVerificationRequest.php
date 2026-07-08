<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ResidentVerificationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'apartment_id',
        'residence_complex_id',
        'residence_building_id',
        'residence_unit_id',
        'status',
        'verification_method',
        'distance_m',
        'request_note',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'distance_m' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function residenceComplex(): BelongsTo
    {
        return $this->belongsTo(ResidenceComplex::class, 'residence_complex_id');
    }

    public function residenceBuilding(): BelongsTo
    {
        return $this->belongsTo(ResidenceBuilding::class, 'residence_building_id');
    }

    public function residenceUnit(): BelongsTo
    {
        return $this->belongsTo(ResidenceUnit::class, 'residence_unit_id');
    }
}
