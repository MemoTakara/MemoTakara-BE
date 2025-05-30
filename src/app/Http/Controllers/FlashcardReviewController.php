<?php

namespace App\Http\Controllers;

use App\Models\FlashcardReviewLog;
use App\Models\Flashcard;
use App\Models\FlashcardStatus;
use App\Models\UserFlashcard;
use App\Services\SM2Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FlashcardReviewController extends Controller
{
    protected $sm2Service;

    public function __construct(SM2Service $sm2Service)
    {
        $this->sm2Service = $sm2Service;
    }

    // trạng thái của flashcard (5 trạng thái)
    public function getProgressSummary($collectionId)
    {
        $userId = Auth::id();
        $now = now();

        // Lấy toàn bộ ID flashcard trong collection
        $allFlashcardIds = Flashcard::where('collection_id', $collectionId)->pluck('id');

        // Lấy danh sách flashcard đã có trạng thái học
        $statuses = FlashcardStatus::where('user_id', $userId)
            ->whereIn('flashcard_id', $allFlashcardIds)
            ->get()
            ->keyBy('flashcard_id'); // để dễ kiểm tra tồn tại

        $summary = [
            'new' => 0,
            'learning' => 0,
            'due' => 0,
        ];

        foreach ($allFlashcardIds as $flashcardId) {
            if (!isset($statuses[$flashcardId])) {
                $summary['new']++;
            } else {
                $status = $statuses[$flashcardId];
                if ($status->next_review_at <= $now) {
                    $summary['due']++;
                } else {
                    $summary['learning']++;
                }
            }
        }

        return response()->json($summary);
    }

    // flashcard đến hạn
    public function getDueFlashcards($collectionId)
    {
        $userId = Auth::id();
        $now = now();

        // Lấy toàn bộ flashcard trong collection
        $allFlashcards = Flashcard::where('collection_id', $collectionId)->get();

        // Lấy trạng thái flashcard của user trong collection
        $statuses = FlashcardStatus::where('user_id', $userId)
            ->whereIn('flashcard_id', $allFlashcards->pluck('id'))
            ->get()
            ->keyBy('flashcard_id');

        $dueFlashcards = [];

        foreach ($allFlashcards as $flashcard) {
            $status = $statuses->get($flashcard->id);

            if (!$status) {
                // flashcard mới chưa học
                $dueFlashcards[] = $flashcard;
            } else {
                // flashcard đã học, check ngày đến hạn
                if ($status->next_review_at <= $now) {
                    $dueFlashcards[] = $flashcard;
                }
            }
        }

        return response()->json([
            'count' => count($dueFlashcards),
            'flashcards' => $dueFlashcards,
        ]);
    }

    // update trạng thái khi học
    public function storeReviewResult(Request $request)
    {
        $request->merge([
            'quality' => (int)$request->quality,
        ]);

        $request->validate([
            'flashcard_id' => 'required|exists:flashcards,id',
            'quality' => 'required|integer|min:0|max:5',
        ]);

        $userId = auth()->id();
        $flashcardId = $request->flashcard_id;
        $quality = $request->quality;
        $now = now();

        $status = FlashcardStatus::firstOrCreate(
            [
                'user_id' => $userId,
                'flashcard_id' => $flashcardId,
            ],
            [
                'status' => 'new',
                'interval' => 1,
                'ease_factor' => 2.5,
                'repetitions' => 0,
                'last_reviewed_at' => now(),
                'next_review_at' => now(),
            ]
        );

        $sm2Result = $this->sm2Service->calculate(
            $status->interval,
            $status->ease_factor,
            $status->repetitions,
            $quality
        );

        $status->update([
            'interval' => $sm2Result['interval'],
            'ease_factor' => $sm2Result['ease_factor'],
            'repetitions' => $sm2Result['repetitions'],
            'last_reviewed_at' => now(),
            'next_review_at' => now()->copy()->addDays($sm2Result['interval']),
            'status' => $this->getUIStatusFromSM2($sm2Result),
        ]);

        FlashcardReviewLog::create([
            'user_id' => $userId,
            'flashcard_id' => $flashcardId,
            'quality' => $quality,
            'prev_interval' => $status->interval,
            'new_interval' => $sm2Result['interval'],
            'prev_ease_factor' => $status->ease_factor,
            'new_ease_factor' => $sm2Result['ease_factor'],
            'prev_repetitions' => $status->repetitions,
            'new_repetitions' => $sm2Result['repetitions'],
            'reviewed_at' => now(),
        ]);

        return response()->json(['message' => 'Review updated']);
    }

    /**
     * Trả về trạng thái học phục vụ UI (không ảnh hưởng logic SM-2)
     */
    private function getUIStatusFromSM2(array $sm2): string
    {
        if ($sm2['repetitions'] === 0) {
            return 'learning';
        } elseif ($sm2['repetitions'] < 3) {
            return 'young';
        } elseif ($sm2['interval'] >= 20) {
            return 'mastered';
        } else {
            return 'reviewing';
        }
    }
}
