<?php

namespace App\Http\Controllers;

use App\Models\CollectionRating;
use App\Models\Flashcard;
use App\Models\FlashcardStatus;
use App\Models\Tag;
use Illuminate\Http\Request;
use App\Models\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CollectionsController extends Controller
{
    // Lấy danh sách collection mà user sở hữu
    public function index()
    {
        $userId = Auth::id();

        // Lấy tất cả collections của user
        $collections = Collection::where('user_id', $userId)
            ->with([
                'tags', // Lấy danh sách tags
                'ratings', // Lấy danh sách đánh giá
                'ratings.user:id,username', // Lấy thông tin người đánh giá (chỉ lấy id, name)
            ])
            ->withCount('flashcards')
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
            'existing_tags' => 'nullable|array',
            'existing_tags.*' => 'integer|exists:tags,id',
            'new_tags' => 'nullable|array',
            'new_tags.*' => 'string|min:1|max:50',
        ]);

        DB::beginTransaction();

        try {
            // Tạo collection mới
            $collection = Collection::create([
                'user_id' => Auth::id(),
                'collection_name' => $request->collection_name,
                'description' => $request->description,
                'privacy' => $request->privacy ?? 0,
                'star_count' => $request->star_count ?? 0
            ]);

            $allTagIds = [];

            // Gắn các tag có sẵn
            if (!empty($request->existing_tags)) {
                $allTagIds = array_merge($allTagIds, $request->existing_tags);
            }

            // Xử lý tag mới (tối ưu: chỉ insert nếu chưa tồn tại)
            if (!empty($request->new_tags)) {
                $existing = Tag::whereIn('name', $request->new_tags)->pluck('id', 'name')->toArray();

                foreach ($request->new_tags as $tagName) {
                    if (isset($existing[$tagName])) {
                        $allTagIds[] = $existing[$tagName];
                    } else {
                        $newTag = Tag::create(['name' => $tagName]);
                        $allTagIds[] = $newTag->id;
                    }
                }
            }

            // Gắn tất cả tag vào collection
            if (!empty($allTagIds)) {
                $collection->tags()->attach($allTagIds);
            }

            DB::commit();

            return response()->json($collection->load('tags'), 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Tạo collection thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Lấy chi tiết 1 collection
    public function show($id)
    {
        $collection = Collection::with([
            'user:id,username,role',
            'flashcards:id,collection_id,front,back,pronunciation,kanji,audio_file,image',
            'tags',
            'ratings'
        ])->findOrFail($id);
        return response()->json($collection);
    }

    // Cập nhật collection
    public function update(Request $request, $id)
    {
        $collection = Collection::findOrFail($id);

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
        $collection->load(['user'])->loadCount('flashcards');

        return response()->json($collection);
    }

    // Xóa collection
    public function destroy($id)
    {
        $collection = Collection::findOrFail($id);

        if ($collection->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $collection->delete();

        return response()->json(['message' => 'Collection deleted successfully']);
    }

    // list public collection
    public function getPublicCollections()
    {
        $userId = Auth::id(); // hoặc truyền từ client nếu chưa login

        // Chỉ lấy danh sách collection có privacy = 1 (public)
        $collections = Collection::where('privacy', 1)
            ->with([
                'user:id,username,role', // Lấy thông tin người tạo collection
                'flashcards' => function ($query) use ($userId) {
                    $query->with(['statuses' => function ($statusQuery) use ($userId) {
                        $statusQuery->where('user_id', $userId);
                    }])->select(
                        'id', 'collection_id',
                        'front', 'back', 'pronunciation',
                        'kanji', 'audio_file', 'image',
                        'created_at', 'updated_at'); // chọn cột cần thiết
                },
            ])
            ->withCount('flashcards')   // Đếm số flashcard
            ->get();

        // Gán status của từng flashcard theo user
        $collections->each(function ($collection) {
            $collection->flashcards->each(function ($flashcard) {
                $flashcard->status = $flashcard->statuses->first()->status ?? 'new';
                unset($flashcard->statuses); // Xóa để không trả về
            });
        });

        return response()->json($collections);
    }

    // public collection with flashcard list
    public function getPublicCollectionDetail($id)
    {
        $collection = Collection::where('id', $id)
            ->where('privacy', 1)
            ->with([
                'user:id,username,role',
                'flashcards:id,collection_id,front,back,pronunciation,audio_file'
            ])
            ->firstOrFail();

        return response()->json($collection);
    }

    // Lấy danh sách các collection công khai của người dùng
    public function getPublicCollectionsByUser($userId)
    {
        // Lấy danh sách các collection có privacy = 1 (công khai) của người dùng cụ thể
        $collections = Collection::where('user_id', $userId)
            ->where('privacy', 1)
            ->select('id', 'collection_name', 'user_id')
            ->with([
//                'tags', // Lấy danh sách tags
//                'ratings', // Lấy đánh giá
//                'ratings.user:id,username', // Lấy thông tin người đánh giá
//                'flashcards:id,collection_id,status' // Lấy thông tin flashcard
            ])
            ->get();

        // Tính số sao trung bình cho mỗi collection
//        $collections->each(function ($collection) {
//            $collection->average_rating = $collection->ratings->avg('rating') ?? 0; // Nếu không có rating thì mặc định 0
//        });

        return response()->json($collections);
    }

    // search api
    public function searchPublicCollections(Request $request)
    {
        $searchTerm = $request->input('query');

        $collections = Collection::where('privacy', 1)
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
        $averageStar = CollectionRating::where('collection_id', $collectionId)->avg('rating');

        // Cập nhật vào bảng collections
        Collection::where('id', $collectionId)->update(['star_count' => $averageStar]);

        return response()->json([
            'message' => 'Star count updated successfully!',
            'star_count' => $averageStar
        ]);
    }

    // Duplicate collection
    public function duplicateCollection(Request $request, $collectionId)
    {
        $userId = auth()->id(); // Lấy user hiện tại
        $original = Collection::findOrFail($collectionId);

        DB::beginTransaction();

        try {
            // Tạo collection mới
            $newCollection = Collection::create([
                'collection_name' => $original->collection_name . " (Copy)",
                'description' => $original->description,
                'privacy' => 0, // Mặc định private
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

            // Copy flashcards
            $originalFlashcards = Flashcard::where('collection_id', $collectionId)->get();
            foreach ($originalFlashcards as $flashcard) {
                Flashcard::create([
                    'collection_id' => $newCollection->id,
                    'front' => $flashcard->front,
                    'back' => $flashcard->back,
                    'pronunciation' => $flashcard->pronunciation,
                    'kanji' => $flashcard->kanji,
                    'audio_file' => $flashcard->audio_file,
                    'image' => $flashcard->image,
                ]);

                // Status tương ứng
                FlashcardStatus::created([
                    'user_id' => $userId,
                    'flashcard_id' => $flashcard->id,
                ]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Collection duplicated successfully!',
                'new_collection_id' => $newCollection->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate collection',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
