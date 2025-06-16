<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecentCollection extends Model
{
    use HasFactory;

    protected $table = 'recent_collections';

    protected $fillable = [
        'user_id',
        'collection_id'
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    // Scopes
    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderBy('updated_at', 'desc')->limit($limit);
    }
}
