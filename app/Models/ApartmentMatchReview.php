<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ApartmentMatchReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'source',
        'raw_apartment_name',
        'raw_region',
        'road_address',
        'latitude',
        'longitude',
        'suggested_apartment_id',
        'resolved_apartment_id',
        'status',
        'admin_note',
        'resolved_by',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function suggestedApartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class, 'suggested_apartment_id');
    }

    public function resolvedApartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class, 'resolved_apartment_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
