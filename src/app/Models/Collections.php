<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Collections extends Model
{
    use HasFactory;
    protected $fillable = [
        'collection_name',
        'description',
        'privacy',
        'tag',
        'star_count',
        'user_id',
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function flashcards()
    {
        return $this->hasMany(Flashcards::class, 'collection_id');
    }

    public function tags() {
        return $this->belongsToMany(Tags::class, 'collection_tag', 'collection_id', 'tag_id');
    }
}
