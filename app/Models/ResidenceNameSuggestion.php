<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResidenceNameSuggestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'complex_id',
        'suggested_name',
        'suggested_by',
        'votes_up',
        'votes_down',
        'status',
    ];

    public function complex(): BelongsTo
    {
        return $this->belongsTo(ResidenceComplex::class, 'complex_id');
    }

    public function suggester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suggested_by');
    }
}
