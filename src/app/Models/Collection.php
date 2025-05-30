<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
