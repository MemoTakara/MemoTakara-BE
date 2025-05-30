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
    public function scopeByLanguage($query, $frontLang = null, $backLang = null)
    {
        // Sử dụng ngôn ngữ từ collection thay vì từ flashcard
        $query->whereHas('collection', function ($collectionQuery) use ($frontLang, $backLang) {
            if ($frontLang) {
                $collectionQuery->where('language_front', $frontLang);
            }
            if ($backLang) {
                $collectionQuery->where('language_back', $backLang);
            }
        });
        return $query;
    }

    // Scope để filter theo collection difficulty
    public function scopeByDifficulty($query, $level)
    {
        return $query->whereHas('collection', function ($collectionQuery) use ($level) {
            $collectionQuery->where('difficulty_level', $level);
        });
    }

    // Helper methods
    public function hasImage(): bool
    {
        return !empty($this->image);
    }

    public function hasPronunciation(): bool
    {
        return !empty($this->pronunciation);
    }

    public function hasKanji(): bool
    {
        return !empty($this->kanji);
    }

    public function getLanguageFront(): string
    {
        return $this->collection->language_front ?? 'vi';
    }

    public function getLanguageBack(): string
    {
        return $this->collection->language_back ?? 'en';
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
