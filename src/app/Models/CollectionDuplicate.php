<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionDuplicate extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_collection_id',
        'duplicated_collection_id',
        'user_id',
    ];

    // Relationships
    public function originalCollection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'original_collection_id');
    }

    public function duplicatedCollection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'duplicated_collection_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Events
    protected static function boot()
    {
        parent::boot();

        static::created(function ($duplicate) {
            $original = $duplicate->originalCollection;
            $original->increment('total_duplicates');
        });
    }
}
