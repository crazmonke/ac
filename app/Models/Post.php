<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'board_id',
        'post_topic_id',
        'apartment_id',
        'residence_complex_id',
        'region_sido',
        'region_sigungu',
        'region_eupmyeondong',
        'user_id',
        'title',
        'body',
        'is_notice',
        'is_anonymous',
        'visibility',
        'audience_scope',
        'is_guest_visible',
        'view_count',
        'comment_count',
    ];

    protected function casts(): array
    {
        return [
            'is_notice' => 'boolean',
            'is_anonymous' => 'boolean',
            'is_guest_visible' => 'boolean',
        ];
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(PostTopic::class, 'post_topic_id');
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function residenceComplex(): BelongsTo
    {
        return $this->belongsTo(ResidenceComplex::class, 'residence_complex_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(PostFile::class);
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function poll(): HasOne
    {
        return $this->hasOne(Poll::class);
    }
}
