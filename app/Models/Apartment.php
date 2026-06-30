<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Apartment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'road_address',
        'jibun_address',
        'sido',
        'sigungu',
        'eupmyeondong',
    ];

    public function boardCategories(): HasMany
    {
        return $this->hasMany(BoardCategory::class);
    }

    public function boards(): HasMany
    {
        return $this->hasMany(Board::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }
}
