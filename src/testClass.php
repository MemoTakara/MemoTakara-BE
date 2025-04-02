<?php

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = ['name', 'email', 'password', 'is_active']; // Thêm 'is_active' vào fillable

    protected $casts = [
        'is_active' => 'boolean', // Ép kiểu boolean để dễ sử dụng
    ];
}
