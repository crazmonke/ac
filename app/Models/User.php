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
        'is_adult',
        'terms_agreed_at',
        'privacy_agreed_at',
        'preferred_apartment_id',
        'preferred_residence_complex_id',
        'preferred_residence_building_id',
        'preferred_residence_unit_id',
        'home_sido',
        'home_sigungu',
        'home_eupmyeondong',
        'home_apartment_name',
        'last_login_at',
        'access_allowed',
        'withdrawn_at',
        'profile_locked',
        'password',
        'point_balance',
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
            'is_adult' => 'boolean',
            'terms_agreed_at' => 'datetime',
            'privacy_agreed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'access_allowed' => 'boolean',
            'withdrawn_at' => 'datetime',
            'profile_locked' => 'boolean',
            'password' => 'hashed',
            'point_balance' => 'integer',
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

    public function preferredResidenceComplex(): BelongsTo
    {
        return $this->belongsTo(ResidenceComplex::class, 'preferred_residence_complex_id');
    }

    public function preferredResidenceBuilding(): BelongsTo
    {
        return $this->belongsTo(ResidenceBuilding::class, 'preferred_residence_building_id');
    }

    public function preferredResidenceUnit(): BelongsTo
    {
        return $this->belongsTo(ResidenceUnit::class, 'preferred_residence_unit_id');
    }

    public function residentVerificationRequests(): HasMany
    {
        return $this->hasMany(ResidentVerificationRequest::class);
    }

    public function apartmentMatchReviews(): HasMany
    {
        return $this->hasMany(ApartmentMatchReview::class);
    }

    public function userResidences(): HasMany
    {
        return $this->hasMany(UserResidence::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function hiddenPosts(): HasMany
    {
        return $this->hasMany(PostHide::class);
    }

    public function blockedUsers(): HasMany
    {
        return $this->hasMany(BlockedUser::class, 'blocker_id');
    }

    public function blockedByUsers(): HasMany
    {
        return $this->hasMany(BlockedUser::class, 'blocked_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function fcmTokens(): HasMany
    {
        return $this->hasMany(FcmToken::class);
    }

    public function postLikes(): HasMany
    {
        return $this->hasMany(PostLike::class);
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
