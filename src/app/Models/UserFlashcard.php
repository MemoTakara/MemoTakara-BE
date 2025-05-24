<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserFlashcard extends Model
{
    protected $table = 'user_flashcards';

    protected $fillable = [
        'user_id', 'flashcard_id',
        'review_at', 'quality', 'interval', 'repetition', 'ease_factor'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function flashcard()
    {
        return $this->belongsTo(Flashcards::class);
    }
}
