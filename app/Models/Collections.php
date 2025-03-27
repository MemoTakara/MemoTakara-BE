<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Collections extends Model
{
//    public function tags() {
//        return $this->belongsToMany(Tag::class, 'collection_tag', 'collection_id', 'tag_id');
//    }
    use HasFactory;

    protected $fillable = ['title', 'description', 'user_id'];

    public function flashcards()
    {
        return $this->hasMany(Flashcards::class);
    }
}
