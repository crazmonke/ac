<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResidenceBuilding extends Model
{
    use HasFactory;

    protected $fillable = [
        'complex_id',
        'building_no',
        'building_name',
        'road_address',
        'jibun_address',
        'bld_main_no',
        'bld_sub_no',
        'legal_dong_code',
        'latitude',
        'longitude',
        'normalized_key',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function complex(): BelongsTo
    {
        return $this->belongsTo(ResidenceComplex::class, 'complex_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(ResidenceUnit::class, 'building_id');
    }
}
