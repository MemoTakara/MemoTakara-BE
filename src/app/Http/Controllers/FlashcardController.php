<?php

namespace App\Http\Controllers;

use App\Models\FlashcardReviewLog;
use App\Models\FlashcardStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Flashcard;
use App\Models\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FlashcardController extends Controller
{
    // Get all flashcards in a collection
    public function index(Request $request, $collectionId): JsonResponse
    {
        try {
            $collection = Collection::findOrFail($collectionId);

            // Check if user can access this collection
            if (!$collection->canBeAccessedBy(Auth::id())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có quyền truy cập collection này'
                ], 403);
            }

            $query = $collection->flashcards()->with(['statuses' => function ($q) {
                $q->where('user_id', Auth::id());
            }]);

            // Apply filters
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('front', 'like', "%{$search}%")
                        ->orWhere('back', 'like', "%{$search}%")
                        ->orWhere('pronunciation', 'like', "%{$search}%");
                });
            }

            // Pagination
            $perPage = $request->get('per_page', 20);
            $flashcards = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $flashcards,
                'collection' => $collection
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách flashcard: ' . $e->getMessage()
            ], 500);
        }
    }

    // Get flashcard detail
    public function show($id): JsonResponse
    {
        try {
            $flashcard = Flashcard::with([
                'collection',
                'statuses' => function ($q) {
                    $q->where('user_id', Auth::id());
                }
            ])->findOrFail($id);

            // Check access permission
            if (!$flashcard->collection->canBeAccessedBy(Auth::id())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có quyền truy cập flashcard này'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $flashcard
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thông tin flashcard: ' . $e->getMessage()
            ], 500);
        }
    }

    // Create new flashcard
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'collection_id' => 'required|exists:collections,id',
                'front' => 'required|string|max:1000',
                'back' => 'required|string|max:1000',
                'pronunciation' => 'nullable|string|max:500',
                'kanji' => 'nullable|string|max:500',
                'image' => 'nullable|string|max:500',
                'extra_data' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            $collection = Collection::findOrFail($request->collection_id);

            // Check if user owns this collection
            if ($collection->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có quyền thêm flashcard vào collection này'
                ], 403);
            }

            $flashcard = Flashcard::create($request->all());

            // Initialize status for the owner
            FlashcardStatus::create([
                'user_id' => Auth::id(),
                'flashcard_id' => $flashcard->id,
                'status' => 'new',
                'study_mode' => 'front_to_back',
                'interval' => 0,
                'interval_minutes' => 0,
                'ease_factor' => 2.5,
                'repetitions' => 0,
                'lapses' => 0,
                'is_leech' => false,
                'due_date' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tạo flashcard thành công',
                'data' => $flashcard->load('collection')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo flashcard: ' . $e->getMessage()
            ], 500);
        }
    }

    // Update flashcard
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $flashcard = Flashcard::findOrFail($id);

            // Check if user owns this flashcard's collection
            if ($flashcard->collection->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có quyền chỉnh sửa flashcard này'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'front' => 'required|string|max:1000',
                'back' => 'required|string|max:1000',
                'pronunciation' => 'nullable|string|max:500',
                'kanji' => 'nullable|string|max:500',
                'image' => 'nullable|string|max:500',
                'extra_data' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            $flashcard->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật flashcard thành công',
                'data' => $flashcard->load('collection')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật flashcard: ' . $e->getMessage()
            ], 500);
        }
    }

    // Delete flashcard
    public function destroy($id): JsonResponse
    {
        try {
            $flashcard = Flashcard::findOrFail($id);

            // Check if user owns this flashcard's collection
            if ($flashcard->collection->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có quyền xóa flashcard này'
                ], 403);
            }

            // Delete related data
            $flashcard->statuses()->delete();
            $flashcard->reviewLogs()->delete();

            $flashcard->delete();

            return response()->json([
                'success' => true,
                'message' => 'Xóa flashcard thành công'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa flashcard: ' . $e->getMessage()
            ], 500);
        }
    }

    // Bulk create flashcards - Tạo flashcard hàng loạt
    public function bulkStore(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'collection_id' => 'required|exists:collections,id',
                'flashcards' => 'required|array|min:1',
                'flashcards.*.front' => 'required|string|max:1000',
                'flashcards.*.back' => 'required|string|max:1000',
                'flashcards.*.pronunciation' => 'nullable|string|max:500',
                'flashcards.*.kanji' => 'nullable|string|max:500',
                'flashcards.*.image' => 'nullable|string|max:500',
                'flashcards.*.extra_data' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            $collection = Collection::findOrFail($request->collection_id);

            // Check if user owns this collection
            if ($collection->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có quyền thêm flashcard vào collection này'
                ], 403);
            }

            DB::beginTransaction();

            $createdFlashcards = [];
            foreach ($request->flashcards as $flashcardData) {
                $flashcardData['collection_id'] = $request->collection_id;
                $flashcard = Flashcard::create($flashcardData);

                // Initialize status for the owner
                FlashcardStatus::create([
                    'user_id' => Auth::id(),
                    'flashcard_id' => $flashcard->id,
                    'status' => 'new',
                    'study_mode' => 'front_to_back',
                    'interval' => 0,
                    'interval_minutes' => 0,
                    'ease_factor' => 2.5,
                    'repetitions' => 0,
                    'lapses' => 0,
                    'is_leech' => false,
                    'due_date' => now()
                ]);

                $createdFlashcards[] = $flashcard;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Tạo flashcard hàng loạt thành công",
                'data' => $createdFlashcards,
                'count' => count($createdFlashcards)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo flashcard hàng loạt: ' . $e->getMessage()
            ], 500);
        }
    }

    // ?????????????????? Mark flashcard as leech or unmark
    public function toggleLeech(Request $request, $id): JsonResponse
    {
        try {
            $flashcard = Flashcard::findOrFail($id);

            // Check if user can access this flashcard
            if (!$flashcard->collection->canBeAccessedBy(Auth::id())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có quyền truy cập flashcard này'
                ], 403);
            }

            $studyMode = $request->get('study_mode', 'front_to_back');

            $status = FlashcardStatus::where('user_id', Auth::id())
                ->where('flashcard_id', $id)
                ->where('study_mode', $studyMode)
                ->first();

            if (!$status) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy trạng thái flashcard'
                ], 404);
            }

            $status->is_leech = !$status->is_leech;
            $status->save();

            return response()->json([
                'success' => true,
                'message' => $status->is_leech ? 'Đã đánh dấu flashcard khó' : 'Đã bỏ đánh dấu flashcard khó',
                'data' => ['is_leech' => $status->is_leech]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi đánh dấu flashcard: ' . $e->getMessage()
            ], 500);
        }
    }
}
