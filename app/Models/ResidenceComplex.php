<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResidenceComplex extends Model
{
    use HasFactory;

    protected $fillable = [
        'housing_type',
        'official_name',
        'alias_name',
        'auto_display_name',
        'display_name_source',
        'road_address',
        'jibun_address',
        'legal_dong_code',
        'postal_code',
        'latitude',
        'longitude',
        'normalized_key',
        'status',
        'merged_into_id',
        'legacy_apartment_id',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function legacyApartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class, 'legacy_apartment_id');
    }

    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(self::class, 'merged_into_id');
    }

    public function buildings(): HasMany
    {
        return $this->hasMany(ResidenceBuilding::class, 'complex_id');
    }

    public function userResidences(): HasMany
    {
        return $this->hasMany(UserResidence::class, 'complex_id');
    }

    public function displayName(): string
    {
        return trim((string) ($this->official_name ?: $this->alias_name ?: $this->auto_display_name));
    }
}
