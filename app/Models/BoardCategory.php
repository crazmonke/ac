<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class BoardCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'apartment_id',
        'name',
        'slug',
        'sort_order',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function boards(): HasMany
    {
        return $this->hasMany(Board::class, 'category_id');
    }
}
