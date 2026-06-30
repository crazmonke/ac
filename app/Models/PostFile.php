<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class PostFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'user_id',
        'original_name',
        'stored_name',
        'disk',
        'path',
        'mime_type',
        'size',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
