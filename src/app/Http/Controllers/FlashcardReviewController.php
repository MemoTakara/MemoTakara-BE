<?php

namespace App\Http\Controllers;

use App\Models\Flashcards;
use App\Models\FlashcardStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class FlashcardReviewController extends Controller
{
    /**
     * Cập nhật trạng thái flashcard sau khi ôn bài.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function review(Request $request)
    {
        $request->validate([
            'flashcard_id' => 'required|exists:flashcards,id',
            'quality' => 'required|integer|min:0|max:5', // 0-5 theo SM-2
        ]);

        $user = Auth::user();
        $flashcardId = $request->flashcard_id;
        $quality = $request->quality;

        // Lấy hoặc tạo trạng thái
        $status = FlashcardStatus::firstOrNew([
            'user_id' => $user->id,
            'flashcard_id' => $flashcardId
        ]);

        $now = Carbon::now();

        if ($quality < 3) {
            // Nếu trả lời sai → ôn lại sớm
            $status->repetitions = 0;
            $status->interval = 1;
            $status->ease_factor = max(1.3, $status->ease_factor - 0.2);
        } else {
            // Trả lời đúng → tăng chỉ số theo SM-2
            $status->repetitions++;
            if ($status->repetitions === 1) {
                $status->interval = 1;
            } elseif ($status->repetitions === 2) {
                $status->interval = 6;
            } else {
                $status->interval = round($status->interval * $status->ease_factor);
            }

            // Cập nhật hệ số dễ nhớ
            $status->ease_factor = $status->ease_factor + (0.1 - (5 - $quality) * (0.08 + (5 - $quality) * 0.02));
            $status->ease_factor = max(1.3, $status->ease_factor);
        }

        $status->last_reviewed_at = $now;
        $status->next_review_at = $now->copy()->addDays($status->interval);

        // Tự động cập nhật status text theo số lần học
        if ($status->repetitions == 0) {
            $status->status = 're-learning';
        } elseif ($status->repetitions < 3) {
            $status->status = 'learning';
        } elseif ($status->repetitions < 5) {
            $status->status = 'young';
        } else {
            $status->status = 'mastered';
        }

        $status->save();

        return response()->json([
            'message' => 'Flashcard reviewed successfully.',
            'status' => $status
        ]);
    }

    public function getDueFlashcards()
    {
        $user = Auth::user();
        $now = Carbon::now();

        $dueFlashcards = Flashcards::whereHas('statuses', function ($query) use ($user, $now) {
            $query->where('user_id', $user->id)
                ->where('next_review_at', '<=', $now);
        })->with([
            'collection:id,collection_name',
            'statuses' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }
        ])->get();

        return response()->json([
            'message' => 'Due flashcards fetched successfully.',
            'data' => $dueFlashcards
        ]);
    }

    // Thống kê
    public function getUserStudyProgress()
    {
        $userId = Auth::id();

        // Lấy tất cả flashcard_statuses của user
        $statuses = FlashcardStatus::with('flashcard.collection')
            ->where('user_id', $userId)
            ->get();

        // Tổng hợp dữ liệu theo từng collection
        $progress = $statuses->groupBy(function ($status) {
            return $status->flashcard->collection->id;
        })->map(function ($collectionStatuses, $collectionId) {
            return [
                'collection_id' => $collectionId,
                'collection_title' => $collectionStatuses->first()->flashcard->collection->title,
                'total' => $collectionStatuses->count(),
                'status_breakdown' => $collectionStatuses->groupBy('status')->map->count()
            ];
        })->values();

        return response()->json($progress);
    }

}
