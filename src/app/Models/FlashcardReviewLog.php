<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashcardReviewLog extends Model
{
    protected $fillable = [
        'user_id',
        'flashcard_id',
        'study_type',
        'study_mode',
        'response_time_ms',
        'quality',
        'prev_interval',
        'new_interval',
        'prev_ease_factor',
        'new_ease_factor',
        'prev_repetitions',
        'new_repetitions',
        'reviewed_at',
    ];

    protected $casts = [
        'response_time_ms' => 'integer',
        'quality' => 'integer',
        'prev_interval' => 'integer',
        'new_interval' => 'integer',
        'prev_ease_factor' => 'float',
        'new_ease_factor' => 'float',
        'prev_repetitions' => 'integer',
        'new_repetitions' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function flashcard(): BelongsTo
    {
        return $this->belongsTo(Flashcard::class);
    }

    // Scopes
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('reviewed_at', [$startDate, $endDate]);
    }

    public function scopeByStudyType($query, $type)
    {
        return $query->where('study_type', $type);
    }
}
