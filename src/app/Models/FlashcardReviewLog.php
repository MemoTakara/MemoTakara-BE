<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlashcardReviewLog extends Model
{
    protected $fillable = [
        'user_id',
        'flashcard_id',
        'quality',
        'prev_interval',
        'new_interval',
        'prev_ease_factor',
        'new_ease_factor',
        'prev_repetitions',
        'new_repetitions',
        'reviewed_at',
    ];

    public $timestamps = true;

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];
}
