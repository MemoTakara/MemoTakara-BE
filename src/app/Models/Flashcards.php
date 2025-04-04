<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Flashcards extends Model
{
    use HasFactory;

    // Định nghĩa các trường có thể gán
    protected $fillable = [
        'front',
        'back',
        'pronunciation',
        'kanji',
        'audio_file',
        'image',
        'status',
        'collection_id',
    ];

    public function collection()
    {
        return $this->belongsTo(
            Collections::class,
            'collection_id',
            'id'
        );
    }
}
