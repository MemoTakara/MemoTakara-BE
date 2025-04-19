<?php

namespace App\Http\Controllers;

use App\Models\RecentCollection;
use App\Models\Collections;
use Illuminate\Http\Request;

class RecentCollectionController extends Controller
{
    // Lưu lịch sử truy cập
    public function store(Request $request)
    {
        $userId = $request->user()->id;
        $collectionId = $request->input('collection_id');

        // Xoá bản ghi cũ nếu đã có
        RecentCollection::where('user_id', $userId)
            ->where('collection_id', $collectionId)
            ->delete();

        // Thêm mới
        RecentCollection::create([
            'user_id' => $userId,
            'collection_id' => $collectionId,
        ]);

        return response()->json(['message' => 'Saved']);
    }

    // Lấy danh sách đã xem gần đây
    public function index($userId)
    {
        $recent = RecentCollection::where('user_id', $userId)
            ->orderBy('updated_at', 'desc')
            ->take(10)
            ->pluck('collection_id');

        $collections = Collections::whereIn('id', $recent)
            ->where('privacy', 1)
            ->get();

        // Sắp xếp theo thứ tự recent
        $sorted = $recent->map(function ($id) use ($collections) {
            return $collections->firstWhere('id', $id);
        })->filter();

        return response()->json($sorted->values());
    }
}
