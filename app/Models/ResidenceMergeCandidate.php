<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResidenceMergeCandidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_complex_id',
        'target_complex_id',
        'score',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'float',
            'reason' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function sourceComplex(): BelongsTo
    {
        return $this->belongsTo(ResidenceComplex::class, 'source_complex_id');
    }

    public function targetComplex(): BelongsTo
    {
        return $this->belongsTo(ResidenceComplex::class, 'target_complex_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
