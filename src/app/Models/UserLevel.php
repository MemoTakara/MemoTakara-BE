<?php

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
