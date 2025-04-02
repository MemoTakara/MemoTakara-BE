<?php

namespace App\Http\Controllers;

use App\Models\CollectionRatings;
use Illuminate\Http\Request;
use App\Models\Collections;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CollectionsController extends Controller
{
    // Lấy danh sách collection mà user sở hữu
    public function index()
    {
        $userId = Auth::id();

        // Lấy tất cả collections của user
        $collections = Collections::where('user_id', $userId)
            ->with([
                'tags', // Lấy danh sách tags
                'ratings', // Lấy danh sách đánh giá
                'ratings.user:id,username', // Lấy thông tin người đánh giá (chỉ lấy id, name)
            ])
            ->get();

        // Tính số sao trung bình cho mỗi collection
        $collections->each(function ($collection) {
            $collection->average_rating = $collection->ratings->avg('rating') ?? 0; // Nếu không có rating thì mặc định 0
        });

        return response()->json($collections);
    }

    // Tạo mới collection
    public function store(Request $request)
    {
        $request->validate([
            'collection_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'privacy' => 'boolean',
            'tag' => 'nullable|string',
        ]);

        $collection = Collections::create([
            'user_id' => Auth::id(),
            'collection_name' => $request->collection_name,
            'description' => $request->description,
            'privacy' => $request->privacy ?? 0,
            'tag' => $request->tag,
            'star_count' => $request->star_count ?? 0
        ]);

        return response()->json($collection, 201);
    }

    // Lấy chi tiết 1 collection
    public function show($id)
    {
//        $collection = Collections::where('user_id', Auth::id())->findOrFail($id);
        $collection = Collections::with(['user', 'tags', 'ratings'])->findOrFail($id);
        return response()->json($collection);
    }

    // Cập nhật collection
    public function update(Request $request, $id)
    {
        $collection = Collections::findOrFail($id);

        if ($collection->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'collection_name' => 'string|max:255',
            'description' => 'nullable|string',
            'privacy' => 'boolean',
            'tag' => 'nullable|string',
        ]);

        $collection->update($request->only(['collection_name', 'description', 'privacy', 'tag',]));

        return response()->json($collection);
    }

    // Xóa collection
    public function destroy($id)
    {
        $collection = Collections::findOrFail($id);

        if ($collection->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $collection->delete();

        return response()->json(['message' => 'Collection deleted successfully']);
    }

    // list public collection
    public function getPublicCollections()
    {
        // Chỉ lấy danh sách collection có privacy = 1 (public)
        $collections = Collections::where('privacy', 1)
            ->with('user') // Lấy thông tin người tạo collection
            ->get();

        return response()->json($collections);
    }

    // search api
    public function searchPublicCollections(Request $request)
    {
        $searchTerm = $request->input('query');

        $collections = Collections::where('privacy', 1)
            ->where(function ($query) use ($searchTerm) {
                $query->where('collection_name', 'like', "%$searchTerm%")
                    ->orWhereHas('tags', function ($query) use ($searchTerm) {
                        $query->where('name', 'LIKE', "%{$searchTerm}%");
                    })
                    ->orWhereHas('user', function ($query) use ($searchTerm) {
                        $query->where('username', 'like', "%$searchTerm%");
                    });
            })
            ->with(['user:id,role,username', 'tags:id,name']) // Lấy thông tin user và tags
            ->get();

        return response()->json($collections);
    }

    // update star count
    public function updateStarCount($collectionId)
    {
        // Tính trung bình số sao
        $averageStar = CollectionRatings::where('collection_id', $collectionId)->avg('rating');

        // Cập nhật vào bảng collections
        Collections::where('id', $collectionId)->update(['star_count' => $averageStar]);

        return response()->json([
            'message' => 'Star count updated successfully!',
            'star_count' => $averageStar
        ]);
    }

    // Duplicate collection
    public function duplicateCollection(Request $request, $collectionId)
    {
        $userId = auth()->id(); // Lấy user hiện tại
        $original = Collections::findOrFail($collectionId);

        DB::beginTransaction();
        try {
            // Tạo collection mới
            $newCollection = Collections::create([
                'collection_name' => $original->collection_name . " (Copy)",
                'description' => $original->description,
                'privacy' => 0, // Mặc định private
                'tag' => $original->tag,
                'star_count' => 0, // Bản sao không có đánh giá
                'user_id' => $userId, // Người dùng mới là chủ sở hữu
            ]);

            // Lấy các tag liên kết
            $tags = DB::table('collection_tag')->where('collection_id', $collectionId)->get();

            foreach ($tags as $tag) {
                DB::table('collection_tag')->insert([
                    'collection_id' => $newCollection->id,
                    'tag_id' => $tag->tag_id
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Collection duplicated successfully!', 'new_collection_id' => $newCollection->id]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to duplicate collection'], 500);
        }
    }
}
