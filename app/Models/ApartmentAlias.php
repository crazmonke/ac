<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ApartmentAlias extends Model
{
    use HasFactory;

    protected $fillable = [
        'apartment_id',
        'alias',
        'normalized_alias',
        'source',
        'confidence',
        'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'confidence' => 'decimal:2',
        ];
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }
}
