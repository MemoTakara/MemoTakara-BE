<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashcardStatus extends Model
{
    use HasFactory;

    protected $table = 'flashcard_statuses';

    protected $fillable = [
        'user_id', 'flashcard_id', 'status',
        'study_mode',
        'interval', 'interval_minutes',
        'ease_factor', 'repetitions',
        'lapses', 'is_leech',
        'last_reviewed_at', 'next_review_at', 'due_date',
    ];

    protected $casts = [
        'interval' => 'integer',
        'interval_minutes' => 'integer',
        'ease_factor' => 'float',
        'repetitions' => 'integer',
        'lapses' => 'integer',
        'is_leech' => 'boolean',
        'last_reviewed_at' => 'datetime',
        'next_review_at' => 'datetime',
        'due_date' => 'datetime',
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
    public function scopeDue($query, $userId = null)
    {
        $query = $query->where('due_date', '<=', now());
        if ($userId) {
            $query->where('user_id', $userId);
        }
        return $query;
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // SM-2 Algorithm Implementation
    public function updateSM2($quality)
    {
        $settings = SystemSetting::getSettings();
        $minEaseFactor = $settings['sm2_min_ease_factor'] ?? 1.3;

        // Log current state
        FlashcardReviewLog::create([
            'user_id' => $this->user_id,
            'flashcard_id' => $this->flashcard_id,
            'study_type' => 'flashcard',
            'study_mode' => $this->study_mode,
            'quality' => $quality,
            'prev_interval' => $this->interval_minutes,
            'prev_ease_factor' => $this->ease_factor,
            'prev_repetitions' => $this->repetitions,
            'reviewed_at' => now(),
        ]);

        if ($quality >= 3) {
            // Correct response
            if ($this->repetitions == 0) {
                $this->interval_minutes = $settings['sm2_initial_interval'] ?? 1;
            } elseif ($this->repetitions == 1) {
                $this->interval_minutes = $settings['sm2_second_interval'] ?? 6;
            } else {
                $this->interval_minutes = round($this->interval_minutes * $this->ease_factor);
            }
            $this->repetitions++;
            $this->status = $this->determineStatus();
        } else {
            // Incorrect response
            $this->repetitions = 0;
            $this->interval_minutes = $settings['sm2_initial_interval'] ?? 1;
            $this->lapses++;
            $this->status = 'learning';

            // Mark as leech if too many lapses
            if ($this->lapses >= 8) {
                $this->is_leech = true;
            }
        }

        // Update ease factor
        $this->ease_factor = max(
            $minEaseFactor,
            $this->ease_factor + (0.1 - (5 - $quality) * (0.08 + (5 - $quality) * 0.02))
        );

        // Set next review time
        $this->last_reviewed_at = now();
        $this->due_date = now()->addMinutes($this->interval_minutes);
        $this->next_review_at = $this->due_date;

        // Update legacy interval field
        $this->interval = max(1, round($this->interval_minutes / 1440)); // Convert to days

        // Log new state
        $log = FlashcardReviewLog::latest()->first();
        $log->update([
            'new_interval' => $this->interval_minutes,
            'new_ease_factor' => $this->ease_factor,
            'new_repetitions' => $this->repetitions,
        ]);

        $this->save();
    }

    private function determineStatus(): string
    {
        if ($this->repetitions <= 1) {
            return 'learning';
        } elseif ($this->interval_minutes < 1440 * 21) { // Less than 21 days
            return 'young';
        } else {
            return 'mastered';
        }
    }

    // Helper methods
    public function isDue(): bool
    {
        return $this->due_date <= now();
    }

    public function isNew(): bool
    {
        return $this->status === 'new';
    }
}
