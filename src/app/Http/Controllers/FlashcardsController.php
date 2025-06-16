<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Flashcard;
use App\Models\Collection;
use Illuminate\Support\Facades\Auth;

class FlashcardsController extends Controller
{
    // Lấy danh sách flashcard theo collection
//    public function index($collection_id)
//    {
//        // Lấy ID người dùng hiện tại (nếu có)
//        $userId = Auth::id();
//
//        // Kiểm tra người dùng có đăng nhập hay không
//        if (!$userId) {
//            return response()->json(['error' => 'User not authenticated'], 401);
//        }
//
//        // Tìm collection theo collection_id
//        $collection = Collection::where('id', $collection_id)
//            ->where(function ($query) use ($userId) {
//                $query->where('privacy', 1) // Collection công khai
//                ->orWhere('user_id', $userId); // Collection của người dùng
//            })
//            ->with('flashcards') // Tải trước flashcards liên quan
//            ->firstOrFail();
//
//        // Trả về danh sách flashcards của collection
//        return response()->json($collection->flashcards ?? []);
//    }

    // Thêm flashcard
    public function store(Request $request)
    {
        $request->validate([
            'collection_id' => 'required|exists:collections,id',
            'front' => 'required|string',
            'back' => 'required|string',
            'kanji' => 'nullable|string',
            'vocabulary_meaning' => 'required|string',
            'image' => 'nullable|string',
            'status' => 'required|in:new,learning,re-learning,young,mastered'
        ]);

        $flashcard = Flashcard::create([
            'collection_id' => $request->collection_id,
            'front' => $request->front,
            'back' => $request->back,
            'kanji' => $request->kanji,
            'vocabulary_meaning' => $request->vocabulary_meaning,
            'image' => $request->image,
            'status' => $request->status
        ]);

        return response()->json($flashcard, 201);
    }

    // Lấy chi tiết flashcard
    public function show($id)
    {
        $flashcard = Flashcard::findOrFail($id);
        return response()->json($flashcard);
    }

    // Cập nhật flashcard
    public function update(Request $request, $id)
    {
        $flashcard = Flashcard::findOrFail($id);
        $flashcard->update($request->only(['front', 'back', 'kanji', 'vocabulary_meaning', 'image', 'status']));

        return response()->json($flashcard);
    }

    // Xóa flashcard
    public function destroy($id)
    {
        $flashcard = Flashcard::findOrFail($id);
        $flashcard->delete();

        return response()->json(['message' => 'Flashcard deleted successfully']);
    }
}
