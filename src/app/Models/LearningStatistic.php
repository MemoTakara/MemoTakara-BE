<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningStatistic extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'study_date',
        'new_cards',
        'learning_cards',
        'review_cards',
        'mastered_cards',
        'total_study_time',
        'total_sessions',
    ];

    protected $casts = [
        'study_date' => 'date',
        'new_cards' => 'integer',
        'learning_cards' => 'integer',
        'review_cards' => 'integer',
        'mastered_cards' => 'integer',
        'total_study_time' => 'integer',
        'total_sessions' => 'integer',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Static methods
    public static function updateDailyStats($userId, $date, $duration = 0, $sessions = 0)
    {
        $statistic = self::firstOrCreate([
            'user_id' => $userId,
            'study_date' => $date,
        ]);

        // Count cards by status
        $cardCounts = FlashcardStatus::where('user_id', $userId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $statistic->new_cards = $cardCounts['new'] ?? 0;
        $statistic->learning_cards = $cardCounts['learning'] ?? 0;
        $statistic->review_cards = ($cardCounts['young'] ?? 0) + ($cardCounts['re-learning'] ?? 0);
        $statistic->mastered_cards = $cardCounts['mastered'] ?? 0;

        // Calculate study time and sessions for today
//        $sessions = StudySession::where('user_id', $userId)
//            ->whereDate('started_at', $date)
//            ->get();
//
//        $statistic->total_study_time = $sessions->sum('duration_minutes');
//        $statistic->total_sessions = $sessions->count();

        $statistic->total_study_time += $duration;
        $statistic->total_sessions += $sessions;

        $statistic->save();
    }

    // Scopes
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('study_date', [$startDate, $endDate]);
    }

    public function scopeRecentDays($query, $days = 30)
    {
        return $query->where('study_date', '>=', now()->subDays($days));
    }
}
