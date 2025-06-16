<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CollectionTags extends Pivot
{
    use HasFactory;

    protected $table = 'collection_tag';
    protected $fillable = [
        'collection_id',
        'tag_id',
    ];

    public function collection()
    {
        return $this->belongsTo(Collection::class, 'collection_id'); // Đảm bảo tên cột đúng
    }

}
