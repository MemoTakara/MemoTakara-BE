<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecentCollection extends Model
{
    use HasFactory;

    protected $table = 'recent_collections';

    protected $fillable = [
        'user_id',
        'collection_id'
    ];
}
