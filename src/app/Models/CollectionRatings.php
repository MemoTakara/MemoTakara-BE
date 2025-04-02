<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionRatings extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'collection_id',
        'rating',
        'review',
    ];

    /**
     * Quan hệ: Đánh giá thuộc về một Collection
     */
    public function collection()
    {
        return $this->belongsTo(Collections::class);
    }

    /**
     * Quan hệ: Đánh giá thuộc về một User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
