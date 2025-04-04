<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    // Khai báo tên bảng (nếu bảng không theo quy tắc đặt tên mặc định)
    protected $table = 'notifications';

    // Các thuộc tính có thể được gán hàng loạt
    protected $fillable = [
        'user_id',
        'message',
    ];

    // Định nghĩa mối quan hệ với model User (nếu cần)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
