<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'apartment_id',
        'role',
        'granted_at',
        'expires_at',
        'granted_by',
    ];

    protected function casts(): array
    {
        return [
            'granted_at' => 'datetime',
            'expires_at' => 'datetime',
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

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
