<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\CollectionDuplicate;
use App\Models\CollectionRating;
use App\Models\Flashcard;
use App\Models\FlashcardStatus;
use App\Models\Notification;
use App\Models\RecentCollection;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CollectionController extends Controller
{
    // ?????? Lấy danh sách các collection công khai của người dùng
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

    // Search + Display a listing of collections with filters and pagination
    public function index(Request $request): JsonResponse
    {
        $query = Collection::with(['user', 'tags', 'ratings'])
            ->withCount(['flashcards', 'ratings', 'originalDuplicates']);

        // Apply filters
        if ($request->has('search')) {
            $query->search($request->search);
        }

        if ($request->has('user_id')) {
            $query->byUser($request->user_id);
        }

        if ($request->has('difficulty')) {
            $query->byDifficulty($request->difficulty);
        }

        if ($request->has('language_front') || $request->has('language_back')) {
            $query->byLanguage($request->language_front, $request->language_back);
        }

        if ($request->has('tags')) {
            $tags = explode(',', $request->tags);
            $query->whereHas('tags', function ($q) use ($tags) {
                $q->whereIn('name', $tags);
            });
        }

        if ($request->has('featured')) {
            $query->featured();
        }

        if ($request->has('privacy')) {
            if ($request->privacy == 'public') {
                $query->public();
            } else {
                $query->private();
            }
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        switch ($sortBy) {
            case 'rating':
                $query->orderBy('average_rating', $sortOrder);
                break;
            case 'cards_count':
                $query->orderBy('flashcards_count', $sortOrder);
                break;
            case 'duplicates':
                $query->orderBy('original_duplicates_count', $sortOrder);
                break;
            case 'name':
                $query->orderBy('collection_name', $sortOrder);
                break;
            default:
                $query->orderBy('created_at', $sortOrder);
        }

        $perPage = min($request->get('per_page', 10), 50);
        $collections = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $collections,
            'filters' => [
                'difficulties' => ['beginner', 'intermediate', 'advanced'],
                'languages' => ['vi', 'en', 'ja', 'ko', 'zh', 'fr', 'de', 'es'],
                'popular_tags' => Tag::popular(10)->get()
            ]
        ]);
    }

    // Get user's collections
    public function myCollections(Request $request): JsonResponse
    {
        $query = Collection::where('user_id', Auth::id())
            ->with(['tags', 'ratings'])
            ->withCount(['flashcards', 'ratings', 'originalDuplicates']);

        if ($request->has('search')) {
            $query->search($request->search);
        }

        $collections = $query->orderBy('updated_at', 'desc')->paginate(5);

        return response()->json([
            'success' => true,
            'data' => $collections
        ]);
    }

    // Lấy các bộ sưu tập gần đây của người dùng
    public function recentCollections(): JsonResponse
    {
        $recentCollections = RecentCollection::where('user_id', Auth::id())
            ->with(['collection.user', 'collection.tags'])
            ->recent(10)
            ->get()
            ->pluck('collection');

        return response()->json([
            'success' => true,
            'data' => $recentCollections
        ]);
    }

    // :: Store a newly created collection
    public function store(Request $request): JsonResponse
    {
        // Check if user can create more collections
//        if (!Auth::user()->canCreateCollection()) {
//            return response()->json([
//                'success' => false,
//                'message' => 'You have reached the maximum number of collections for your level.'
//            ], 403);
//        }

        $validator = Validator::make($request->all(), [
            'collection_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'privacy' => 'required|integer|in:0,1',
            'language_front' => 'required|string|in:vi,en,ja,ko,zh,fr,de,es',
            'language_back' => 'required|string|in:vi,en,ja,ko,zh,fr,de,es',
            'difficulty_level' => 'required|string|in:beginner,intermediate,advanced',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'metadata' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $collection = Collection::create([
                'collection_name' => $request->collection_name,
                'description' => $request->description,
                'privacy' => $request->privacy,
                'language_front' => $request->language_front,
                'language_back' => $request->language_back,
                'difficulty_level' => $request->difficulty_level,
                'metadata' => $request->metadata ?? [],
                'user_id' => Auth::id(),
                'total_cards' => 0,
                'average_rating' => 0,
                'total_ratings' => 0,
                'total_duplicates' => 0,
                'is_featured' => false
            ]);

            // Attach tags
            if ($request->has('tags')) {
                $tagIds = [];
                foreach ($request->tags as $tagName) {
                    $tag = Tag::firstOrCreate(['name' => trim($tagName)]);
                    $tagIds[] = $tag->id;
                }
                $collection->tags()->sync($tagIds);
            }

            DB::commit();

            $collection->load(['tags', 'user']);

            return response()->json([
                'success' => true,
                'message' => 'Collection created successfully',
                'data' => $collection
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create collection'
            ], 500);
        }
    }

    // Display the specified collection
    public function show($id): JsonResponse
    {
        $collection = Collection::with([
            'user', 'flashcards', 'tags', 'ratings.user'
        ])->withCount(['flashcards', 'ratings', 'originalDuplicates'])
            ->find($id);

        if (!$collection) {
            return response()->json([
                'success' => false,
                'message' => 'Collection not found'
            ], 404);
        }

        Log::info('Show collection auth check', [
            'auth_id' => Auth::id(),
            'token_header' => request()->header('Authorization')
        ]);

        if (!$collection->canBeAccessedBy(Auth::id())) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        // Update recent collections for authenticated user
        if (Auth::check()) {
            RecentCollection::updateOrCreate(
                ['user_id' => Auth::id(), 'collection_id' => $collection->id],
                []
            );
        }

        // Get user's rating for this collection
        $userRating = null;
        if (Auth::check()) {
            $userRating = CollectionRating::where('collection_id', $collection->id)
                ->where('user_id', Auth::id())
                ->first();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'collection' => $collection,
                'user_rating' => $userRating,
                'can_edit' => Auth::check() && $collection->user_id === Auth::id()
            ]
        ]);
    }

    // Update the specified collection
    public function update(Request $request, $id): JsonResponse
    {
        $collection = Collection::find($id);

        if (!$collection) {
            return response()->json([
                'success' => false,
                'message' => 'Collection not found'
            ], 404);
        }

        if ($collection->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'collection_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'privacy' => 'required|integer|in:0,1',
            'language_front' => 'required|string|in:vi,en,ja,ko,zh,fr,de,es',
            'language_back' => 'required|string|in:vi,en,ja,ko,zh,fr,de,es',
            'difficulty_level' => 'required|string|in:beginner,intermediate,advanced',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'metadata' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $collection->update([
                'collection_name' => $request->collection_name,
                'description' => $request->description,
                'privacy' => $request->privacy,
                'language_front' => $request->language_front,
                'language_back' => $request->language_back,
                'difficulty_level' => $request->difficulty_level,
                'metadata' => $request->metadata ?? $collection->metadata
            ]);

            // Update tags
            if ($request->has('tags')) {
                $tagIds = [];
                foreach ($request->tags as $tagName) {
                    $tag = Tag::firstOrCreate(['name' => trim($tagName)]);
                    $tagIds[] = $tag->id;
                }
                $collection->tags()->sync($tagIds);
            }

            DB::commit();

            $collection->load(['tags', 'user']);

            return response()->json([
                'success' => true,
                'message' => 'Collection updated successfully',
                'data' => $collection
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update collection'
            ], 500);
        }
    }

    // Remove the specified collection
    public function destroy($id): JsonResponse
    {
        $collection = Collection::find($id);

        if (!$collection) {
            return response()->json([
                'success' => false,
                'message' => 'Collection not found'
            ], 404);
        }

        if ($collection->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        DB::beginTransaction();
        try {
            // Delete related data
            $collection->flashcards()->delete();
            $collection->ratings()->delete();
            $collection->tags()->detach();
            $collection->originalDuplicates()->delete();
            $collection->duplicatedFrom()->delete();

            // Delete the collection
            $collection->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Collection deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete collection'
            ], 500);
        }
    }

    // :: Duplicate a collection
    public function duplicate($id): JsonResponse
    {
        $originalCollection = Collection::with(['flashcards', 'tags'])->find($id);

        if (!$originalCollection) {
            return response()->json([
                'success' => false,
                'message' => 'Collection not found'
            ], 404);
        }

        if (!$originalCollection->canBeAccessedBy(Auth::id())) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

//        if ($originalCollection->user_id === Auth::id()) {
//            return response()->json([
//                'success' => false,
//                'message' => 'You cannot duplicate your own collection'
//            ], 400);
//        }

//        if (!Auth::user()->canCreateCollection()) {
//            return response()->json([
//                'success' => false,
//                'message' => 'You have reached the maximum number of collections for your level.'
//            ], 403);
//        }

        DB::beginTransaction();
        try {
            // Create duplicated collection
            $duplicatedCollection = Collection::create([
                'collection_name' => $originalCollection->collection_name . ' (Copy)',
                'description' => $originalCollection->description,
                'privacy' => 0, // Always private for duplicated collections
                'language_front' => $originalCollection->language_front,
                'language_back' => $originalCollection->language_back,
                'difficulty_level' => $originalCollection->difficulty_level,
                'metadata' => $originalCollection->metadata,
                'user_id' => Auth::id(),
                'total_cards' => 0,
                'average_rating' => 0,
                'total_ratings' => 0,
                'total_duplicates' => 0,
                'is_featured' => false
            ]);

            // Duplicate flashcards
            foreach ($originalCollection->flashcards as $flashcard) {
                Flashcard::create([
                    'front' => $flashcard->front,
                    'back' => $flashcard->back,
                    'pronunciation' => $flashcard->pronunciation,
                    'kanji' => $flashcard->kanji,
                    'image' => $flashcard->image,
                    'extra_data' => $flashcard->extra_data,
                    'collection_id' => $duplicatedCollection->id
                ]);
            }

            // Attach tags
            $duplicatedCollection->tags()->attach($originalCollection->tags->pluck('id'));

            Log::info('Duplicating collection', [
                'original_collection_id' => $originalCollection->id,
                'duplicated_collection_id' => $duplicatedCollection->id,
                'user_id' => Auth::id()
            ]);
            // Record the duplication
            CollectionDuplicate::create([
                'original_collection_id' => $originalCollection->id,
                'duplicated_collection_id' => $duplicatedCollection->id,
                'user_id' => Auth::id()
            ]);

            // Update total cards count
            $duplicatedCollection->updateTotalCards();

            // Send notification to original author
            if ($originalCollection->user_id !== Auth::id()) {
                Notification::createNotification(
                    $originalCollection->user_id,
                    'collection_duplicated',
                    Auth::user()->name . ' has duplicated your collection "' . $originalCollection->collection_name . '"',
                    [
                        'original_collection_id' => $originalCollection->id,
                        'duplicated_collection_id' => $duplicatedCollection->id,
                        'duplicator_name' => Auth::user()->name,
                        'collection_name' => $originalCollection->collection_name
                    ],
                    Auth::id()
                );
            }

            DB::commit();

            $duplicatedCollection->load(['tags', 'user']);

            return response()->json([
                'success' => true,
                'message' => 'Collection duplicated successfully',
                'data' => $duplicatedCollection
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate collection: ' . $e->getMessage()
            ], 500);
        }
    }

    // Rate a collection
    public function rate(Request $request, $id): JsonResponse
    {
        $collection = Collection::find($id);

        if (!$collection) {
            return response()->json([
                'success' => false,
                'message' => 'Collection not found'
            ], 404);
        }

        if ($collection->user_id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot rate your own collection'
            ], 400);
        }

        if (!$collection->canBeAccessedBy(Auth::id())) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|numeric|min:1|max:5',
            'review' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $isNewRating = !CollectionRating::where('collection_id', $collection->id)
                ->where('user_id', Auth::id())
                ->exists();

            $rating = CollectionRating::updateOrCreate(
                [
                    'collection_id' => $collection->id,
                    'user_id' => Auth::id()
                ],
                [
                    'rating' => $request->rating,
                    'review' => $request->review
                ]
            );

            // Send notification to collection author (only for new ratings)
            if ($isNewRating && $collection->user_id !== Auth::id()) {
                Notification::createNotification(
                    $collection->user_id,
                    'collection_rated',
                    Auth::user()->user_name . ' rated your collection "' . $collection->collection_name . '"  (' . $request->rating . '/5)',
                    [
                        'collection_id' => $collection->id,
                        'rating' => $request->rating,
                        'review' => $request->review,
                        'rater_name' => Auth::user()->name,
                        'collection_name' => $collection->collection_name
                    ],
                    Auth::id()
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Rating submitted successfully',
                'data' => $rating
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit rating'
            ], 500);
        }
    }

    // Get all ratings for a specific collection
    public function getRatings($id): JsonResponse
    {
        $collection = Collection::find($id);

        if (!$collection) {
            return response()->json([
                'success' => false,
                'message' => 'Collection not found'
            ], 404);
        }

        if (!$collection->canBeAccessedBy(Auth::id())) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $ratings = CollectionRating::where('collection_id', $id)
            ->with(['user:id,name,username'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Calculate rating statistics
        $stats = [
            'total_ratings' => $collection->total_ratings,
            'average_rating' => $collection->average_rating,
            'rating_breakdown' => CollectionRating::where('collection_id', $id)
                ->selectRaw('rating, COUNT(*) as count')
                ->groupBy('rating')
                ->orderBy('rating', 'desc')
                ->get()
                ->pluck('count', 'rating')
                ->toArray()
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'ratings' => $ratings,
                'statistics' => $stats,
                'collection' => [
                    'id' => $collection->id,
                    'name' => $collection->collection_name,
                    'average_rating' => $collection->average_rating,
                    'total_ratings' => $collection->total_ratings
                ]
            ]
        ]);
    }

    // Get popular collections
    public function popular(): JsonResponse
    {
        $collections = Collection::public()
            ->with(['user', 'tags'])
            ->withCount(['flashcards', 'ratings'])
            ->where('average_rating', '>=', 4.0)
            ->where('total_ratings', '>=', 1)
            ->orderBy('total_duplicates', 'desc')
            ->orderBy('average_rating', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $collections
        ]);
    }

    // Không dùng: Get featured collections
//    public function featuredCollections(): JsonResponse
//    {
//        $collections = Collection::featured()
//            ->public()
//            ->with(['user', 'tags'])
//            ->withCount(['flashcards', 'ratings'])
//            ->orderBy('average_rating', 'desc')
//            ->limit(10)
//            ->get();
//
//        return response()->json([
//            'success' => true,
//            'data' => $collections
//        ]);
//    }

    // Không dùng: Search collections with advanced filters
//    public function search(Request $request): JsonResponse
//    {
//        $query = Collection::public()
//            ->with(['user', 'tags'])
//            ->withCount(['flashcards', 'ratings', 'originalDuplicates']);
//
//        // Text search
//        if ($request->has('q')) {
//            $query->search($request->q);
//        }
//
//        // Filters
//        if ($request->has('difficulty')) {
//            $query->byDifficulty($request->difficulty);
//        }
//
//        if ($request->has('language_front') || $request->has('language_back')) {
//            $query->byLanguage($request->language_front, $request->language_back);
//        }
//
//        if ($request->has('min_rating')) {
//            $query->where('average_rating', '>=', $request->min_rating);
//        }
//
//        if ($request->has('min_cards')) {
//            $query->where('total_cards', '>=', $request->min_cards);
//        }
//
//        if ($request->has('tags')) {
//            $tags = is_array($request->tags) ? $request->tags : explode(',', $request->tags);
//            $query->whereHas('tags', function ($q) use ($tags) {
//                $q->whereIn('name', $tags);
//            });
//        }
//
//        // Sorting
//        $sortBy = $request->get('sort_by', 'relevance');
//        switch ($sortBy) {
//            case 'rating':
//                $query->orderBy('average_rating', 'desc');
//                break;
//            case 'popular':
//                $query->orderBy('total_duplicates', 'desc');
//                break;
//            case 'newest':
//                $query->orderBy('created_at', 'desc');
//                break;
//            case 'cards_count':
//                $query->orderBy('total_cards', 'desc');
//                break;
//            default:
//                $query->orderBy('average_rating', 'desc')
//                    ->orderBy('total_duplicates', 'desc');
//        }
//
//        $collections = $query->paginate(15);
//
//        return response()->json([
//            'success' => true,
//            'data' => $collections
//        ]);
//    }

    // Không dùng: Get collections by tags
//    public function byTags(Request $request): JsonResponse
//    {
//        $tags = $request->get('tags', []);
//        if (is_string($tags)) {
//            $tags = explode(',', $tags);
//        }
//
//        if (empty($tags)) {
//            return response()->json([
//                'success' => false,
//                'message' => 'Tags parameter is required'
//            ], 400);
//        }
//
//        $collections = Collection::public()
//            ->with(['user', 'tags'])
//            ->withCount(['flashcards', 'ratings'])
//            ->whereHas('tags', function ($query) use ($tags) {
//                $query->whereIn('name', $tags);
//            })
//            ->orderBy('average_rating', 'desc')
//            ->paginate(15);
//
//        return response()->json([
//            'success' => true,
//            'data' => $collections
//        ]);
//    }
}
