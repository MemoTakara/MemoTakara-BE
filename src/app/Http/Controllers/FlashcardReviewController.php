<?php

namespace App\Http\Controllers;

use App\Models\FlashcardReviewLog;
use App\Models\Flashcard;
use App\Models\FlashcardStatus;
use App\Models\StudySession;
use App\Models\LearningStatistic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FlashcardReviewController extends Controller
{
    // Lấy tóm tắt tiến độ học tập theo collection
    public function getProgressSummary($collectionId)
    {
        $userId = Auth::id();
        $now = now();

        // Lấy tổng số flashcard trong collection
        $totalFlashcards = Flashcard::where('collection_id', $collectionId)->count();

        // Lấy thống kê trạng thái flashcard của user
        $statusCounts = FlashcardStatus::where('user_id', $userId)
            ->whereHas('flashcard', function ($query) use ($collectionId) {
                $query->where('collection_id', $collectionId);
            })
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Đếm flashcard đến hạn ôn tập
        $dueCount = FlashcardStatus::where('user_id', $userId)
            ->whereHas('flashcard', function ($query) use ($collectionId) {
                $query->where('collection_id', $collectionId);
            })
            ->where('due_date', '<=', $now)
            ->count();

        // Tính số flashcard mới (chưa có status)
        $studiedCount = array_sum($statusCounts);
        $newCount = $totalFlashcards - $studiedCount;

        $summary = [
            'total' => $totalFlashcards,
            'new' => $newCount,
            'learning' => $statusCounts['learning'] ?? 0,
            'young' => $statusCounts['young'] ?? 0,
            'mastered' => $statusCounts['mastered'] ?? 0,
            'due' => $dueCount,
        ];

        return response()->json($summary);
    }
}
