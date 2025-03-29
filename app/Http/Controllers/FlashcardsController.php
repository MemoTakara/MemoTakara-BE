<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Flashcards;
use App\Models\Collections;
use Illuminate\Support\Facades\Auth;

class FlashcardsController extends Controller
{
    // Lấy danh sách flashcard theo collection
    public function index($collection_id)
    {
        // Lấy ID người dùng hiện tại (nếu có)
        $userId = Auth::id();

        // Tìm collection theo collection_id
        $collection = Collections::where('id', $collection_id)
            ->where(function ($query) use ($userId) {
                $query->where('privacy', 1); // Collection công khai
                if ($userId) {
                    $query->orWhere('user_id', $userId); // Collection của người dùng
                }
            })
            ->firstOrFail();

        // Trả về danh sách flashcards của collection
        return response()->json($collection->flashcards);
    }



    // Thêm flashcard
    public function store(Request $request)
    {
        $request->validate([
            'collection_id' => 'required|exists:collections,id',
            'front' => 'required|string',
            'back' => 'required|string',
            'kanji' => 'nullable|string',
            'audio_file' => 'nullable|string',
            'vocabulary_meaning' => 'required|string',
            'image' => 'nullable|string',
            'status' => 'required|in:new,learning,re-learning,young,mastered'
        ]);

        $flashcard = Flashcards::create([
            'collection_id' => $request->collection_id,
            'front' => $request->front,
            'back' => $request->back,
            'kanji' => $request->kanji,
            'audio_file' => $request->audio_file,
            'vocabulary_meaning' => $request->vocabulary_meaning,
            'image' => $request->image,
            'status' => $request->status
        ]);

        return response()->json($flashcard, 201);
    }

    // Lấy chi tiết flashcard
    public function show($id)
    {
        $flashcard = Flashcards::findOrFail($id);
        return response()->json($flashcard);
    }

    // Cập nhật flashcard
    public function update(Request $request, $id)
    {
        $flashcard = Flashcards::findOrFail($id);
        $flashcard->update($request->only(['front', 'back', 'kanji', 'audio_file', 'vocabulary_meaning', 'image', 'status']));

        return response()->json($flashcard);
    }

    // Xóa flashcard
    public function destroy($id)
    {
        $flashcard = Flashcards::findOrFail($id);
        $flashcard->delete();

        return response()->json(['message' => 'Flashcard deleted successfully']);
    }
}
