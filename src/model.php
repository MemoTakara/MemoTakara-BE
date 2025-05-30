<?php
// ===== USER MODEL =====
// File: app/Models/User.php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'google_id',
        'password',
        'role',
        'is_active',
        'timezone',
        'study_preferences',
        'daily_study_goal',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'study_preferences' => 'array',
        'daily_study_goal' => 'integer',
    ];

    // Relationships
    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class);
    }

    public function userLevel(): HasOne
    {
        return $this->hasOne(UserLevel::class);
    }

    public function flashcardStatuses(): HasMany
    {
        return $this->hasMany(FlashcardStatus::class);
    }

    public function reviewLogs(): HasMany
    {
        return $this->hasMany(FlashcardReviewLog::class);
    }

    public function studySessions(): HasMany
    {
        return $this->hasMany(StudySession::class);
    }

    public function learningStatistics(): HasMany
    {
        return $this->hasMany(LearningStatistic::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function collectionRatings(): HasMany
    {
        return $this->hasMany(CollectionRating::class);
    }

    public function recentCollections(): HasMany
    {
        return $this->hasMany(RecentCollection::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRole($query, $role)
    {
        return $query->where('role', $role);
    }

    // Helper methods
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function canCreateCollection(): bool
    {
        $userLevel = $this->userLevel;
        if (!$userLevel) return false;

        if ($userLevel->max_collections === -1) return true;

        return $this->collections()->count() < $userLevel->max_collections;
    }
}

// ===== USER LEVEL MODEL =====
// File: app/Models/UserLevel.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'level',
        'max_collections',
        'average_rating',
        'total_ratings',
    ];

    protected $casts = [
        'level' => 'integer',
        'max_collections' => 'integer',
        'average_rating' => 'decimal:2',
        'total_ratings' => 'integer',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Helper methods
    public function updateLevel()
    {
        $settings = SystemSetting::getSettings();
        $thresholds = $settings['user_level_thresholds'] ?? [];

        foreach ($thresholds as $level => $requirements) {
            if ($this->average_rating >= $requirements['rating']) {
                $this->level = $level;
                $this->max_collections = $requirements['max_collections'];
            }
        }

        $this->save();
    }
}

// ===== COLLECTION MODEL =====
// File: app/Models/Collection.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Collection extends Model
{
    use HasFactory;

    protected $table = 'collections';

    protected $fillable = [
        'collection_name',
        'description',
        'privacy',
        'total_cards',
        'average_rating',
        'total_ratings',
        'total_duplicates',
        'language_front',
        'language_back',
        'metadata',
        'difficulty_level',
        'is_featured',
        'user_id',
    ];

    protected $casts = [
        'privacy' => 'integer',
        'total_cards' => 'integer',
        'average_rating' => 'decimal:2',
        'total_ratings' => 'integer',
        'total_duplicates' => 'integer',
        'metadata' => 'array',
        'difficulty_level' => 'string',
        'is_featured' => 'boolean',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function flashcards(): HasMany
    {
        return $this->hasMany(Flashcard::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'collection_tag');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(CollectionRating::class);
    }

    // Sửa: Thêm relationship cho duplicate gốc và duplicate được tạo
    public function originalDuplicates(): HasMany
    {
        return $this->hasMany(CollectionDuplicate::class, 'original_collection_id');
    }

    public function duplicatedFrom(): HasMany
    {
        return $this->hasMany(CollectionDuplicate::class, 'duplicated_collection_id');
    }

    public function studySessions(): HasMany
    {
        return $this->hasMany(StudySession::class);
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('privacy', 1);
    }

    public function scopePrivate($query)
    {
        return $query->where('privacy', 0);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where('collection_name', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%");
    }

    // Thêm scope để filter theo difficulty level
    public function scopeByDifficulty($query, $level)
    {
        return $query->where('difficulty_level', $level);
    }

    // Thêm scope để filter theo ngôn ngữ
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

    // Helper methods
    public function isPublic(): bool
    {
        return $this->privacy === 1;
    }

    public function canBeAccessedBy($userId): bool
    {
        return $this->isPublic() || $this->user_id === $userId;
    }

    public function updateTotalCards()
    {
        $this->total_cards = $this->flashcards()->count();
        $this->save();
    }

    public function updateRating()
    {
        $ratings = $this->ratings();
        $this->total_ratings = $ratings->count();
        $this->average_rating = $ratings->avg('rating') ?? 0;
        $this->save();

        // Update user level nếu có
        $this->user->userLevel?->updateLevel();
    }

    // Thêm method để update số lượt duplicate
    public function updateTotalDuplicates()
    {
        $this->total_duplicates = $this->originalDuplicates()->count();
        $this->save();
    }

    // Thêm method để kiểm tra difficulty level
    public function getDifficultyLevels(): array
    {
        return ['beginner', 'intermediate', 'advanced'];
    }

    public function isValidDifficultyLevel(string $level): bool
    {
        return in_array($level, $this->getDifficultyLevels());
    }

    // Thêm method để kiểm tra ngôn ngữ
    public function getSupportedLanguages(): array
    {
        return ['vi', 'en', 'ja', 'ko', 'zh', 'fr', 'de', 'es'];
    }

    public function isValidLanguage(string $language): bool
    {
        return in_array($language, $this->getSupportedLanguages());
    }
}

// ===== TAG MODEL =====
// File: app/Models/Tag.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    // Relationships
    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'collection_tag');
    }

    // Scopes
    public function scopePopular($query, $limit = 10)
    {
        return $query->withCount('collections')
            ->orderBy('collections_count', 'desc')
            ->limit($limit);
    }
}

// ===== COLLECTION RATING MODEL =====
// File: app/Models/CollectionRating.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'collection_id',
        'user_id',
        'rating',
        'review',
    ];

    protected $casts = [
        'rating' => 'decimal:1',
    ];

    // Relationships
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Events
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($rating) {
            $rating->collection->updateRating();
        });

        static::deleted(function ($rating) {
            $rating->collection->updateRating();
        });
    }
}

// ===== COLLECTION DUPLICATE MODEL =====
// File: app/Models/CollectionDuplicate.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionDuplicate extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_collection_id',
        'duplicated_collection_id',
        'user_id',
    ];

    // Relationships
    public function originalCollection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'original_collection_id');
    }

    public function duplicatedCollection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'duplicated_collection_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Events
    protected static function boot()
    {
        parent::boot();

        static::created(function ($duplicate) {
            $original = $duplicate->originalCollection;
            $original->increment('total_duplicates');
        });
    }
}

// ===== RECENT COLLECTION MODEL =====
// File: app/Models/RecentCollection.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecentCollection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'collection_id',
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
    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderBy('updated_at', 'desc')->limit($limit);
    }
}


// ===== FLASHCARD MODEL =====
// File: app/Models/Flashcard.php

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

// ===== FLASHCARD STATUS MODEL =====
// File: app/Models/FlashcardStatus.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class FlashcardStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'flashcard_id',
        'status',
        'study_mode',
        'interval',
        'interval_minutes',
        'ease_factor',
        'repetitions',
        'lapses',
        'is_leech',
        'last_reviewed_at',
        'next_review_at',
        'due_date',
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

// ===== FLASHCARD REVIEW LOG MODEL =====
// File: app/Models/FlashcardReviewLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashcardReviewLog extends Model
{
    use HasFactory;

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

// ===== STUDY SESSION MODEL =====
// File: app/Models/StudySession.php

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

// ===== LEARNING STATISTIC MODEL =====
// File: app/Models/LearningStatistic.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

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
    public static function updateDailyStats($userId, $date)
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
        $sessions = StudySession::where('user_id', $userId)
            ->whereDate('started_at', $date)
            ->get();

        $statistic->total_study_time = $sessions->sum('duration_minutes');
        $statistic->total_sessions = $sessions->count();

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

// ===== NOTIFICATION MODEL =====
// File: app/Models/Notification.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sender_id',
        'type',
        'message',
        'is_read',
        'data',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'data' => 'array',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query, $limit = 50)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    // Helper methods
    public function markAsRead()
    {
        $this->is_read = true;
        $this->save();
    }

    // Static methods
    public static function createNotification($userId, $type, $message, $data = null, $senderId = null)
    {
        return self::create([
            'user_id' => $userId,
            'sender_id' => $senderId,
            'type' => $type,
            'message' => $message,
            'data' => $data,
        ]);
    }
}

// ===== SYSTEM SETTING MODEL =====
// File: app/Models/SystemSetting.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'description',
        'type',
    ];

    // Cache settings for performance
    public static function getSettings()
    {
        return Cache::remember('system_settings', 3600, function () {
            $settings = self::all();
            $result = [];

            foreach ($settings as $setting) {
                $value = $setting->value;

                switch ($setting->type) {
                    case 'integer':
                        $value = (int)$value;
                        break;
                    case 'float':
                        $value = (float)$value;
                        break;
                    case 'boolean':
                        $value = (bool)$value;
                        break;
                    case 'json':
                        $value = json_decode($value, true);
                        break;
                }

                $result[$setting->key] = $value;
            }

            return $result;
        });
    }

    public static function getSetting($key, $default = null)
    {
        $settings = self::getSettings();
        return $settings[$key] ?? $default;
    }

    public static function setSetting($key, $value, $type = 'string', $description = null)
    {
        if ($type === 'json') {
            $value = json_encode($value);
        }

        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'description' => $description
            ]
        );

        // Clear cache when settings change
        Cache::forget('system_settings');

        return $setting;
    }

    /**
     * Get SM-2 algorithm settings
     */
    public static function getSM2Settings()
    {
        return [
            'initial_interval' => self::getSetting('sm2_initial_interval', 1),
            'second_interval' => self::getSetting('sm2_second_interval', 6),
            'min_ease_factor' => self::getSetting('sm2_min_ease_factor', 1.3),
        ];
    }

    /**
     * Get user level thresholds
     */
    public static function getUserLevelThresholds()
    {
        return self::getSetting('user_level_thresholds', [
            1 => ['rating' => 0, 'max_collections' => 5],
            2 => ['rating' => 2.1, 'max_collections' => 10],
            3 => ['rating' => 3.1, 'max_collections' => 20],
            4 => ['rating' => 4.1, 'max_collections' => -1]
        ]);
    }

    /**
     * Get new cards per day limit
     */
    public static function getNewCardsPerDayLimit()
    {
        return self::getSetting('new_cards_per_day_limit', 20);
    }

    /**
     * Get password reset token expiry in minutes
     */
    public static function getPasswordResetTokenExpiry()
    {
        return self::getSetting('password_reset_token_expiry', 60);
    }

    /**
     * Clear settings cache
     */
    public static function clearCache()
    {
        Cache::forget('system_settings');
    }

    /**
     * Boot method to clear cache when model is saved or deleted
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            self::clearCache();
        });

        static::deleted(function () {
            self::clearCache();
        });
    }

    /**
     * Scope to get settings by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get typed value accessor
     */
    public function getTypedValueAttribute()
    {
        $value = $this->value;

        switch ($this->type) {
            case 'integer':
                return (int)$value;
            case 'float':
                return (float)$value;
            case 'boolean':
                return (bool)$value;
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    /**
     * Set typed value mutator
     */
    public function setValueAttribute($value)
    {
        if ($this->type === 'json' && is_array($value)) {
            $this->attributes['value'] = json_encode($value);
        } else {
            $this->attributes['value'] = $value;
        }
    }
}
