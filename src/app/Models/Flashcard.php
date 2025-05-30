<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Flashcard extends Model
{
    use HasFactory;

    // Định nghĩa các trường có thể gán
    protected $fillable = [
        'front',
        'back',
        'pronunciation',
        'kanji',
        'language_front',
        'language_back',
        'image',
        'extra_data',
        'collection_id',
    ];

    protected $casts = [
        'extra_data' => 'array',
    ];

    // Relationships
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(FlashcardStatus::class);
    }

    public function reviewLogs(): HasMany
    {
        return $this->hasMany(FlashcardReviewLog::class);
    }

    // Get status for specific user
    public function getStatusForUser($userId)
    {
        return $this->statuses()->where('user_id', $userId)->first();
    }

    // Scopes
    public function scopeByDifficulty($query, $level)
    {
        return $query->where('difficulty_level', $level);
    }

    public function scopeByLanguage($query, $frontLang = null, $backLang = null)
    {
        if ($frontLang) {
            $query->where('language_front', $frontLang);
        }
        if ($backLang) {
            $query->where('language_back', $backLang);
        }
        return $query;
    }

    // Events
    protected static function boot()
    {
        parent::boot();

        static::created(function ($flashcard) {
            $flashcard->collection->updateTotalCards();
        });

        static::deleted(function ($flashcard) {
            $flashcard->collection->updateTotalCards();
        });
    }
}
