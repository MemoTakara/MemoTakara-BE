<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username', //username không thể null
        'email',
        'google_id',
        'password',
        'role',
        'is_active',
        'timezone',
        'study_preferences',
        'daily_study_goal',
    ];
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean', // Ép kiểu boolean để dễ sử dụng
        'study_preferences' => 'array',
        'daily_study_goal' => 'integer',
        'password' => 'hashed',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
//    protected function casts(): array
//    {
//        return [
//            'email_verified_at' => 'datetime',
//        ];
//    }

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
