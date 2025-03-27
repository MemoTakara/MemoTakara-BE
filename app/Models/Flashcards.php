<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Flashcards extends Model
{
    use HasFactory;

    protected $fillable = ['collection_id', 'front', 'back'];

    public function collection()
    {
        return $this->belongsTo(Collections::class);
    }
}
