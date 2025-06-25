<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Flashcard;
use App\Models\FlashcardReviewLog;
use App\Models\FlashcardStatus;
use App\Models\LearningStatistic;
use App\Models\StudySession;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ProgressController extends Controller
{
    // Get flashcard review history
    public function getReviewHistory(Request $request, $id): JsonResponse
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

            $perPage = $request->get('per_page', 20);

            $reviewLogs = FlashcardReviewLog::where('user_id', Auth::id())
                ->where('flashcard_id', $id)
                ->orderBy('reviewed_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $reviewLogs,
                'flashcard' => $flashcard
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy lịch sử ôn tập: ' . $e->getMessage()
            ], 500);
        }
    }

    // Get overall progress dashboard
    public function getDashboard(): JsonResponse
    {
        $user = Auth::user();
        $today = now()->toDateString();

        $todayStats = LearningStatistic::where('user_id', $user->id)
            ->where('study_date', $today)
            ->first();

        if (!$todayStats) {
            LearningStatistic::updateDailyStats($user->id, $today);
            $todayStats = LearningStatistic::where('user_id', $user->id)
                ->where('study_date', $today)
                ->first();
        }

        $totalCards = FlashcardStatus::where('user_id', $user->id)->count();
        $statusCounts = FlashcardStatus::where('user_id', $user->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $dueCards = FlashcardStatus::where('user_id', $user->id)
            ->where('due_date', '<=', now())
            ->count();

        $studyStreak = $this->calculateStudyStreak($user->id);
        $goalProgress = $this->calculateGoalProgress($user, $todayStats);

        $recentSessions = StudySession::where('user_id', $user->id)
            ->with('collection:id,collection_name')
            ->orderByDesc('started_at')
            ->limit(5)
            ->get();

        // Get weekly stats for comparison
        $weeklyStats = $this->getWeeklyComparison($user->id);

        return response()->json([
            'success' => true,
            'data' => [
                'today_stats' => [
                    'study_time_minutes' => $todayStats->total_study_time ?? 0,
                    'sessions_count' => $todayStats->total_sessions ?? 0,
                    'cards_studied' => ($todayStats->new_cards ?? 0) + ($todayStats->review_cards ?? 0) + ($todayStats->learning_cards ?? 0),
                    'goal_progress' => $goalProgress
                ],
                'overall_stats' => [
                    'total_cards' => $totalCards,
                    'new_cards' => $statusCounts['new'] ?? 0,
                    'learning_cards' => $statusCounts['learning'] ?? 0,
                    'review_cards' => ($statusCounts['re-learning'] ?? 0),
                    'young_cards' => $statusCounts['young'] ?? 0,
                    'mastered_cards' => $statusCounts['mastered'] ?? 0,
                    'due_cards' => $dueCards,
                    'study_streak' => $studyStreak
                ],
                'weekly_comparison' => $weeklyStats,
                'recent_sessions' => $recentSessions
            ]
        ]);
    }

    // ? Get detailed progress for a collection
    public function getCollectionProgress($collectionId): JsonResponse
    {
        $user = Auth::user();
        $collection = Collection::findOrFail($collectionId);

        if (!$collection->canBeAccessedBy($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this collection'
            ], 403);
        }

        $totalCards = $collection->flashcards()->count();

        $statusCounts = FlashcardStatus::whereHas('flashcard', function ($query) use ($collectionId) {
            $query->where('collection_id', $collectionId);
        })
            ->where('user_id', $user->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $studiedCards = array_sum($statusCounts->toArray());
        $newCards = $totalCards - $studiedCards;

        $recentSessions = StudySession::where('user_id', $user->id)
            ->where('collection_id', $collectionId)
            ->orderByDesc('started_at')
            ->limit(10)
            ->get();

        $dailyProgress = $this->getCollectionDailyProgress($collectionId, $user->id, 30);
        $masteryDistribution = $this->getMasteryDistribution($collectionId, $user->id);

        return response()->json([
            'success' => true,
            'data' => [
                'collection' => $collection,
                'progress' => [
                    'total_cards' => $totalCards,
                    'studied_cards' => $studiedCards,
                    'new_cards' => $newCards,
                    'learning_cards' => $statusCounts['learning'] ?? 0,
                    'review_cards' => ($statusCounts['young'] ?? 0) + ($statusCounts['re-learning'] ?? 0),
                    'mastered_cards' => $statusCounts['mastered'] ?? 0,
                    'progress_percentage' => $totalCards > 0 ? round(($studiedCards / $totalCards) * 100, 2) : 0
                ],
                'mastery_distribution' => $masteryDistribution,
                'recent_sessions' => $recentSessions,
                'daily_progress' => $dailyProgress
            ]
        ]);
    }

    // Get learning analytics
    public function getAnalytics(Request $request): JsonResponse
    {
        $user = Auth::user();
        $days = $request->input('days', 30);
        $startDate = now()->subDays($days);

        $statistics = LearningStatistic::where('user_id', $user->id)
            ->where('study_date', '>=', $startDate)
            ->orderBy('study_date')
            ->get();

        $sessions = StudySession::where('user_id', $user->id)
            ->where('started_at', '>=', $startDate)
            ->with('collection:id,collection_name')
            ->get();

        $reviewLogs = FlashcardReviewLog::where('user_id', $user->id)
            ->where('reviewed_at', '>=', $startDate)
            ->get();

        $analytics = [
            'study_time_trend' => $this->getStudyTimeTrend($statistics),
            'accuracy_trend' => $this->getAccuracyTrend($sessions),
            'cards_mastery_trend' => $this->getCardsMasteryTrend($statistics),
            'collection_performance' => $this->getCollectionPerformance($sessions),
            'weekly_summary' => $this->getWeeklySummary($statistics),
            'peak_study_hours' => $this->getPeakStudyHours($sessions),
            'difficulty_analysis' => $this->getDifficultyAnalysis($reviewLogs),
            'retention_rate' => $this->getRetentionRate($user->id, $days)
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }

    // Get study heatmap data
    public function getStudyHeatmap(Request $request): JsonResponse
    {
        $user = Auth::user();
        $year = $request->input('year', now()->year);

        $heatmapData = FlashcardReviewLog::where('user_id', $user->id)
            ->whereYear('reviewed_at', $year)
            ->selectRaw('DATE(reviewed_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Fill missing dates with 0
        $startDate = Carbon::create($year, 1, 1);
        $endDate = Carbon::create($year, 12, 31);
        $result = [];

        while ($startDate->lte($endDate)) {
            $dateStr = $startDate->toDateString();
            $result[] = [
                'date' => $dateStr,
                'count' => $heatmapData[$dateStr]->count ?? 0
            ];
            $startDate->addDay();
        }

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    // Get leaderboard data
    public function getLeaderboard(Request $request): JsonResponse
    {
        $period = $request->input('period', 'week'); // week, month, all_time
        $limit = $request->input('limit', 10);

        $query = User::with('userLevel')
            ->where('is_active', true);

        switch ($period) {
            case 'week':
                $startDate = now()->startOfWeek();
                break;
            case 'month':
                $startDate = now()->startOfMonth();
                break;
            default:
                $startDate = null;
        }

        if ($startDate) {
            $leaderboard = $query->withCount(['reviewLogs as cards_studied' => function ($q) use ($startDate) {
                $q->where('reviewed_at', '>=', $startDate);
            }])
                ->orderByDesc('cards_studied')
                ->limit($limit)
                ->get();
        } else {
            $leaderboard = $query->withCount('reviewLogs as cards_studied')
                ->orderByDesc('cards_studied')
                ->limit($limit)
                ->get();
        }

        $currentUserRank = $this->getCurrentUserRank(Auth::id(), $period, $startDate);

        return response()->json([
            'success' => true,
            'data' => [
                'leaderboard' => $leaderboard,
                'current_user_rank' => $currentUserRank
            ]
        ]);
    }

    // Get streak day
    public function getStudyStreak(Request $request)
    {
        $userId = $request->user()->id;
        $streak = $this->calculateStudyStreak($userId);
        return response()->json(['streak' => $streak]);
    }

    // Calculate study streak
    private function calculateStudyStreak($userId): int
    {
        $streak = 0;
        $date = today();

        while (true) {
            $hasStudied = FlashcardReviewLog::where('user_id', $userId)
                ->whereDate('reviewed_at', $date)
                ->exists();

            if (!$hasStudied) {
                // If today hasn't been studied yet, don't break the streak
                if ($date->isToday()) {
                    $date = $date->subDay();
                    continue;
                }
                break;
            }

            $streak++;
            $date = $date->subDay();
        }

        return $streak;
    }

    // Calculate goal progress
    private function calculateGoalProgress(User $user, $todayStats): array
    {
        $dailyGoal = $user->daily_study_goal ?? 20; // Default 20 cards per day
        $cardsStudied = $todayStats ? ($todayStats->new_cards + $todayStats->review_cards) : 0;

        return [
            'daily_goal' => $dailyGoal,
            'cards_studied' => $cardsStudied,
            'progress_percentage' => $dailyGoal > 0 ? min(100, round(($cardsStudied / $dailyGoal) * 100, 2)) : 0,
            'goal_achieved' => $cardsStudied >= $dailyGoal
        ];
    }

    // Get weekly comparison data
    private function getWeeklyComparison($userId): array
    {
        $thisWeekStart = now()->startOfWeek();
        $lastWeekStart = now()->subWeek()->startOfWeek();
        $lastWeekEnd = now()->subWeek()->endOfWeek();

        $thisWeekStats = LearningStatistic::where('user_id', $userId)
            ->where('study_date', '>=', $thisWeekStart)
            ->get();

        $lastWeekStats = LearningStatistic::where('user_id', $userId)
            ->whereBetween('study_date', [$lastWeekStart, $lastWeekEnd])
            ->get();

        $thisWeekTotal = $thisWeekStats->sum(function ($stat) {
            return $stat->new_cards + $stat->review_cards;
        });

        $lastWeekTotal = $lastWeekStats->sum(function ($stat) {
            return $stat->new_cards + $stat->review_cards;
        });

        $change = $lastWeekTotal > 0 ? round((($thisWeekTotal - $lastWeekTotal) / $lastWeekTotal) * 100, 1) : 0;

        return [
            'this_week' => $thisWeekTotal,
            'last_week' => $lastWeekTotal,
            'change_percentage' => $change
        ];
    }

    // Get collection daily progress
    private function getCollectionDailyProgress($collectionId, $userId, $days): array
    {
        $startDate = now()->subDays($days);

        return FlashcardReviewLog::whereHas('flashcard', function ($query) use ($collectionId) {
            $query->where('collection_id', $collectionId);
        })
            ->where('user_id', $userId)
            ->where('reviewed_at', '>=', $startDate)
            ->selectRaw('DATE(reviewed_at) as date, COUNT(*) as cards_reviewed, AVG(quality) as avg_quality')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    // Get mastery distribution
    private function getMasteryDistribution($collectionId, $userId): array
    {
        $intervals = FlashcardStatus::whereHas('flashcard', function ($query) use ($collectionId) {
            $query->where('collection_id', $collectionId);
        })
            ->where('user_id', $userId)
            ->selectRaw('
            CASE
                WHEN interval_minutes < 1440 THEN "< 1 day"
                WHEN interval_minutes < 7 * 1440 THEN "1-7 days"
                WHEN interval_minutes < 30 * 1440 THEN "1-4 weeks"
                WHEN interval_minutes < 90 * 1440 THEN "1-3 months"
                ELSE "> 3 months"
            END as interval_group,
            COUNT(*) as count
        ')
            ->groupBy('interval_group')
            ->pluck('count', 'interval_group');

        return $intervals->toArray();
    }

    // Get study time trend
    private function getStudyTimeTrend($statistics): array
    {
        return $statistics->map(function ($stat) {
            return [
                'date' => $stat->study_date,
                'study_time' => $stat->total_study_time,
                'sessions' => $stat->total_sessions
            ];
        })->toArray();
    }

    // Get accuracy trend
    private function getAccuracyTrend($sessions): array
    {
        return $sessions->groupBy(function ($session) {
            return $session->started_at->toDateString();
        })->map(function ($daySessions, $date) {
            $totalCards = $daySessions->sum('cards_studied');
            $correctAnswers = $daySessions->sum('correct_answers');

            return [
                'date' => $date,
                'accuracy' => $totalCards > 0 ? round(($correctAnswers / $totalCards) * 100, 2) : 0,
                'total_cards' => $totalCards
            ];
        })->values()->toArray();
    }

    // Get cards mastery trend
    private function getCardsMasteryTrend($statistics): array
    {
        return $statistics->map(function ($stat) {
            return [
                'date' => $stat->study_date,
                'new_cards' => $stat->new_cards,
                'learning_cards' => $stat->learning_cards,
                'review_cards' => $stat->review_cards,
                'mastered_cards' => $stat->mastered_cards
            ];
        })->toArray();
    }

    // Get collection performance
    private function getCollectionPerformance($sessions): array
    {
        return $sessions->groupBy('collection_id')->map(function ($collectionSessions, $collectionId) {
            $collection = $collectionSessions->first()->collection;
            $totalCards = $collectionSessions->sum('cards_studied');
            $correctAnswers = $collectionSessions->sum('correct_answers');
            $totalTime = $collectionSessions->sum('duration_minutes');

            return [
                'collection_name' => $collection->collection_name,
                'total_cards' => $totalCards,
                'accuracy' => $totalCards > 0 ? round(($correctAnswers / $totalCards) * 100, 2) : 0,
                'total_time' => $totalTime,
                'sessions_count' => $collectionSessions->count()
            ];
        })->values()->toArray();
    }

    // Get weekly summary
    private function getWeeklySummary($statistics): array
    {
        return $statistics->groupBy(function ($stat) {
            return $stat->study_date->startOfWeek()->toDateString();
        })->map(function ($weekStats, $weekStart) {
            return [
                'week_start' => $weekStart,
                'total_study_time' => $weekStats->sum('total_study_time'),
                'total_cards' => $weekStats->sum(function ($stat) {
                    return $stat->new_cards + $stat->review_cards;
                }),
                'days_studied' => $weekStats->count()
            ];
        })->values()->toArray();
    }

    // Get peak study hours
    private function getPeakStudyHours($sessions): array
    {
        $hourCounts = $sessions->groupBy(function ($session) {
            return $session->started_at->format('H');
        })->map(function ($hourSessions) {
            return $hourSessions->count();
        })->sortDesc();

        return $hourCounts->toArray();
    }

    // Get difficulty analysis
    private function getDifficultyAnalysis($reviewLogs): array
    {
        $qualityStats = $reviewLogs->groupBy('quality')->map(function ($logs) {
            return $logs->count();
        });

        return [
            'quality_distribution' => $qualityStats->toArray(),
            'average_quality' => round($reviewLogs->avg('quality'), 2),
            'success_rate' => $reviewLogs->count() > 0 ?
                round(($reviewLogs->where('quality', '>=', 3)->count() / $reviewLogs->count()) * 100, 2) : 0
        ];
    }

    // Get retention rate
    private function getRetentionRate($userId, $days): array
    {
        $cutoffDate = now()->subDays($days);

        $reviewedCards = FlashcardStatus::where('user_id', $userId)
            ->where('last_reviewed_at', '>=', $cutoffDate)
            ->where('repetitions', '>', 0)
            ->count();

        $forgottenCards = FlashcardStatus::where('user_id', $userId)
            ->where('last_reviewed_at', '>=', $cutoffDate)
            ->where('lapses', '>', 0)
            ->count();

        $retentionRate = $reviewedCards > 0 ?
            round((($reviewedCards - $forgottenCards) / $reviewedCards) * 100, 2) : 0;

        return [
            'reviewed_cards' => $reviewedCards,
            'forgotten_cards' => $forgottenCards,
            'retention_rate' => $retentionRate
        ];
    }

    // Get current user rank
    private function getCurrentUserRank($userId, $period, $startDate): array
    {
        $query = User::where('is_active', true);

        if ($startDate) {
            $users = $query->withCount(['reviewLogs as cards_studied' => function ($q) use ($startDate) {
                $q->where('reviewed_at', '>=', $startDate);
            }])
                ->orderByDesc('cards_studied')
                ->get();
        } else {
            $users = $query->withCount('reviewLogs as cards_studied')
                ->orderByDesc('cards_studied')
                ->get();
        }

        $rank = $users->search(function ($user) use ($userId) {
            return $user->id === $userId;
        });

        $currentUser = $users->where('id', $userId)->first();

        return [
            'rank' => $rank !== false ? $rank + 1 : null,
            'total_users' => $users->count(),
            'cards_studied' => $currentUser->cards_studied ?? 0
        ];
    }
}
