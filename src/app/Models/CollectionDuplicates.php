<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionDuplicates extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_collection_id',
        'duplicate_collection_id',
        'user_id',
    ];

    /**
     * Quan hệ: Collection gốc
     */
    public function originalCollection()
    {
        return $this->belongsTo(Collections::class, 'original_collection_id');
    }

    /**
     * Quan hệ: Collection được duplicate
     */
    public function duplicatedCollection()
    {
        return $this->belongsTo(Collections::class, 'duplicated_collection_id');
    }

    /**
     * Quan hệ: Ai đã duplicate?
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
