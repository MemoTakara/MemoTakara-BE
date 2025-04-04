<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tags extends Model
{
    use HasFactory;

    protected $table = 'tags';

    protected $fillable = [
        'collection_id',
        'tag_id',
        'name'
    ]; // Thêm các trường cần thiết

    /**
     * Quan hệ: Tag có nhiều collections thông qua bảng trung gian collection_tag
     */
    public function collection()
    {
        return $this->belongsToMany(Collections::class, 'collection_tag', 'tag_id', 'collection_id');
    }
}
