<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'collection_id',
        'rating',
        'review',
    ];

    protected $casts = [
        'rating' => 'decimal:1',
    ];

    // Relationships
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Events
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($rating) {
            $rating->collection->updateRating();
        });

        static::deleted(function ($rating) {
            $rating->collection->updateRating();
        });
    }
}
