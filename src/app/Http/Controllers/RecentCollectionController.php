<?php

namespace App\Http\Controllers;

use App\Models\RecentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecentCollectionController extends Controller
{
    // Giới hạn số lượng bản ghi
    private function limitRecentCollections($userId)
    {
        $idsToKeep = RecentCollection::where('user_id', $userId)
            ->orderByDesc('updated_at')
            ->take(50)
            ->pluck('id');

        RecentCollection::where('user_id', $userId)
            ->whereNotIn('id', $idsToKeep)
            ->delete();
    }

    // Lưu lịch sử truy cập
    public function store(Request $request)
    {
        $userId = Auth::id();
        $collectionId = $request->input('collection_id');

        RecentCollection::updateOrCreate(
            ['user_id' => $userId, 'collection_id' => $collectionId],
            ['updated_at' => now()]
        );

        $this->limitRecentCollections($userId);

        return response()->json(['message' => 'Saved successfully']);
    }

    // Lấy danh sách đã xem gần đây (công khai + riêng tư của chính user)
    public function index()
    {
        $userId = Auth::id();

        $recentCollections = RecentCollection::where('user_id', $userId)
            ->with(['collection' => function ($query) use ($userId) {
                $query->with(['user:id,username,role'])
                    ->withCount('flashcards')
                    ->where(function ($q) use ($userId) {
                        $q->where('privacy', 1)
                            ->orWhere('user_id', $userId);
                    });
            }])
            ->orderByDesc('updated_at')
            ->take(50)
            ->get()
            ->filter(function ($item) {
                // Loại bỏ các bản ghi không còn collection (bị xóa)
                return $item->collection !== null;
            })
            ->values() // reset index
            ->map(function ($item) {
                return [
                    'collection' => $item->collection,
                ];
            });

        return response()->json($recentCollections);
    }
}
