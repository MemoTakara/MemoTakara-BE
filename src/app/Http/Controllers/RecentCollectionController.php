<?php

namespace App\Http\Controllers;

use App\Models\RecentCollection;
use App\Models\Collections;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecentCollectionController extends Controller
{
    // Lưu lịch sử truy cập
    public function store(Request $request)
    {
        $userId = Auth::id();
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
        $recentCollections = RecentCollection::with(
            'collection.user:id,role'
        ) // Eager load collection và user
        ->where('user_id', $userId)
            ->orderBy('updated_at', 'desc')
            ->take(10)
            ->get();

        $collections = $recentCollections->map(function ($recentCollection) {
            $collection = $recentCollection->collection;
            if ($collection && $collection->privacy == 1) {
                return $collection;
            }
            return null;
        })->filter()->values();

        return response()->json($collections);
    }
}
