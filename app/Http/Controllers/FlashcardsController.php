<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Flashcards;
use App\Models\Collections;
use Illuminate\Support\Facades\Auth;

class FlashcardsController extends Controller
{
    public function index($collection_id)
    {
        $collection = Collections::where('user_id', Auth::id())->findOrFail($collection_id);
        return response()->json($collection->flashcards);
    }

    public function store(Request $request)
    {
        $request->validate([
            'collection_id' => 'required|exists:collections,id',
            'front' => 'required|string',
            'back' => 'required|string',
            'audio_file' => 'nullable|string',
            'vocabulary_meaning' => 'required|string',
            'image' => 'nullable|string',
            'status' => 'required|in:new,learning,re-learning,young,mastered'
        ]);

        $flashcard = Flashcards::create([
            'collection_id' => $request->collection_id,
            'front' => $request->front,
            'back' => $request->back,
            'audio_file' => $request->audio_file,
            'vocabulary_meaning' => $request->vocabulary_meaning,
            'image' => $request->image,
            'status' => $request->status
        ]);

        return response()->json($flashcard, 201);
    }

    public function show($id)
    {
        $flashcard = Flashcards::findOrFail($id);
        return response()->json($flashcard);
    }

    public function update(Request $request, $id)
    {
        $flashcard = Flashcards::findOrFail($id);
        $flashcard->update($request->only(['front', 'back', 'audio_file', 'vocabulary_meaning', 'image', 'status']));

        return response()->json($flashcard);
    }

    public function destroy($id)
    {
        $flashcard = Flashcards::findOrFail($id);
        $flashcard->delete();

        return response()->json(['message' => 'Flashcard deleted successfully']);
    }
}
