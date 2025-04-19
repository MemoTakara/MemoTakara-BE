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
        'interval', 'ease_factor', 'repetitions',
        'last_reviewed_at', 'next_review_at'
    ];

    protected $dates = ['last_reviewed_at', 'next_review_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function flashcard(): BelongsTo
    {
        return $this->belongsTo(
            Flashcards::class,
            'flashcard_id');
    }
}
