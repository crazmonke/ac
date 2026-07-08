<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResidenceUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'building_id',
        'dong',
        'ho',
        'unit_label_generated',
        'normalized_unit_key',
    ];

    public function building(): BelongsTo
    {
        return $this->belongsTo(ResidenceBuilding::class, 'building_id');
    }
}
