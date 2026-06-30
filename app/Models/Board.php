<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Board extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'apartment_id',
        'name',
        'slug',
        'description',
        'board_type',
        'read_role',
        'write_role',
        'comment_role',
        'allow_file',
        'allow_anonymous',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'allow_file' => 'boolean',
            'allow_anonymous' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BoardCategory::class, 'category_id');
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
