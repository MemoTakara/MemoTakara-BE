<?php

namespace App\Http\Controllers;

use App\Models\UserFlashcard;
use Illuminate\Http\Request;

class UserFlashcardController extends Controller
{
    public function updateProgress(Request $request)
    {
        $request->validate([
            'flashcard_id' => 'required|exists:flashcards,id',
            'status_id' => 'required|exists:flashcard_statuses,id',
        ]);

        $userFlashcard = UserFlashcard::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'flashcard_id' => $request->flashcard_id
            ],
            [
                'status_id' => $request->status_id,
                'next_review_at' => now()->addDays(1),
                // cập nhật lại spaced repetition fields nếu cần
            ]
        );

        return response()->json($userFlashcard);
    }

    public function getProgress($collectionId)
    {
        $userId = auth()->id();

        $progress = UserFlashcard::whereHas('flashcard.collection', function ($query) use ($collectionId) {
            $query->where('id', $collectionId);
        })
            ->where('user_id', $userId)
            ->with('flashcard', 'status')
            ->get();

        return response()->json($progress);
    }
}
