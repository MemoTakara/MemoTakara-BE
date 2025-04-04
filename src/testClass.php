<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

public function getPublicCollections()
{
    // Chỉ lấy danh sách collection có privacy = 1 (public)
    $collections = Collections::where('privacy', 1)
        ->with(['user:id,username,role',
            'flashcards:id,collection_id,question,answer']) // Chỉ lấy các trường cần thiết từ flashcards
        ->get();

    return response()->json($collections);
}
