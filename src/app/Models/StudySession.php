<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudySession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'collection_id',
        'study_type',
        'cards_studied',
        'correct_answers',
        'duration_minutes',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'cards_studied' => 'integer',
        'correct_answers' => 'integer',
        'duration_minutes' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    // Scopes
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('started_at', [$startDate, $endDate]);
    }

    public function scopeByStudyType($query, $type)
    {
        return $query->where('study_type', $type);
    }

    // Helper methods
    public function getAccuracyPercentage(): float
    {
        if ($this->cards_studied == 0) return 0;
        return round(($this->correct_answers / $this->cards_studied) * 100, 2);
    }

    public function endSession()
    {
        $this->ended_at = now();
        $this->duration_minutes = $this->started_at->diffInMinutes($this->ended_at);
        $this->save();

        // Update learning statistics
        LearningStatistic::updateDailyStats($this->user_id, $this->started_at->toDateString());
    }
}
