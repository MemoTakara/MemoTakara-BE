<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Collections extends Model
{
    use HasFactory;

    protected $table = 'collections';

    protected $fillable = [
        'collection_name',
        'description',
        'privacy',
        'tag',
        'rating',
        'review',
        'user_id',
    ];

    /**
     * Quan hệ: Collection thuộc về một User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Quan hệ: Collection có nhiều flashcard
     */
    public function flashcards()
    {
        return $this->hasMany(
            Flashcards::class,
            'collection_id',
            'id'
        );
    }

    /**
     * Quan hệ: Collection có nhiều Tags thông qua bảng trung gian collection_tag
     */
    public function tags()
    {
        return $this->belongsToMany(Tags::class, 'collection_tag', 'collection_id', 'tag_id');
    }

//    public function collectionTags()
//    {
//        return $this->belongsToMany(Tags::class, 'collection_tag', 'collection_id', 'tag_id');
//    }

    /**
     * Quan hệ: Collection có nhiều đánh giá
     */
    public function ratings()
    {
        return $this->hasMany(CollectionRatings::class, 'collection_id');
    }

    /**
     * Quan hệ: Collection có thể bị duplicate
     */
    public function duplicatedCollections()
    {
        return $this->hasMany(CollectionDuplicates::class, 'original_collection_id');
    }

    /**
     * Quan hệ: Collection có thể là bản sao của collection khác
     */
    public function originalCollection()
    {
        return $this->hasOne(CollectionDuplicates::class, 'duplicated_collection_id');
    }

    /**
     * Tính trung bình số sao
     */
    public function averageRating()
    {
        return $this->ratings()->avg('rating') ?? 0.0;
    }
}
