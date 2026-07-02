<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'preferred_apartment_id',
        'home_sido',
        'home_sigungu',
        'home_eupmyeondong',
        'home_apartment_name',
        'last_login_at',
        'access_allowed',
        'withdrawn_at',
        'profile_locked',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'access_allowed' => 'boolean',
            'withdrawn_at' => 'datetime',
            'profile_locked' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    public function preferredApartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class, 'preferred_apartment_id');
    }

    public function residentVerificationRequests(): HasMany
    {
        return $this->hasMany(ResidentVerificationRequest::class);
    }

    public function apartmentMatchReviews(): HasMany
    {
        return $this->hasMany(ApartmentMatchReview::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function hasRoleForApartment(string $role, ?int $apartmentId = null): bool
    {
        $query = $this->userRoles()->where('role', $role);

        if ($apartmentId !== null) {
            $query->where('apartment_id', $apartmentId);
        }

        return $query->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        })->exists();
    }
}
