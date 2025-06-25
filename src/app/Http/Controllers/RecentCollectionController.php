<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\RecentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RecentCollectionController extends Controller
{
    // Lưu lịch sử truy cập
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'collection_id' => 'required|integer|exists:collections,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = Auth::id();
        $collectionId = $request->input('collection_id');
        $collection = Collection::find($collectionId);

        if (!$collection->canBeAccessedBy($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        DB::beginTransaction();
        try {
            RecentCollection::upsert(
                [
                    [
                        'user_id' => $userId,
                        'collection_id' => $collectionId,
                        'updated_at' => now(),
                        'created_at' => now()
                    ]
                ],
                ['user_id', 'collection_id'],
                ['updated_at']
            );

            $this->limitRecentCollections($userId);

            DB::commit();

            $recentCollection = RecentCollection::where('user_id', $userId)
                ->where('collection_id', $collectionId)
                ->first();

            $recentCollection->load(['collection.user', 'collection.tags']);

            return response()->json([
                'success' => true,
                'message' => 'Saved successfully',
                'data' => $recentCollection
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to save recent collection'
            ], 500);
        }
    }

    // Giới hạn số lượng bản ghi
    private function limitRecentCollections($userId)
    {
//        $idsToKeep = RecentCollection::where('user_id', $userId)
//            ->orderByDesc('updated_at')
//            ->take(50)
//            ->pluck('id');
//
//        RecentCollection::where('user_id', $userId)
//            ->whereNotIn('id', $idsToKeep)
//            ->delete();
        $limit = 10;
        $recentCount = RecentCollection::where('user_id', $userId)->count();

        if ($recentCount > $limit) {
            RecentCollection::where('user_id', $userId)
                ->orderBy('updated_at', 'asc')
                ->limit($recentCount - $limit)
                ->delete();
        }
    }
}
