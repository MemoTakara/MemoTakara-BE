<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory;

    protected $table = 'tags';

    protected $fillable = [
        'name'
    ];

    // Relationships
    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'collection_tag');
    }

    // Scopes
    public function scopePopular($query, $limit = 10)
    {
        return $query->withCount('collections')
            ->orderBy('collections_count', 'desc')
            ->limit($limit);
    }
}
