<?php
// limit flashcards
namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Flashcard;
use App\Models\FlashcardReviewLog;
use App\Models\FlashcardStatus;
use App\Models\LearningStatistic;
use App\Models\StudySession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudyController extends Controller
{
    // Start a new study session
    public function startSession(Request $request): JsonResponse
    {
        $request->validate([
            'collection_id' => 'required|exists:collections,id',
            'study_type' => 'required|in:flashcard,game_match,typing,handwriting,test',
            'limit' => 'integer|min:5|max:150',
            'new_cards_limit' => 'integer|min:0|max:50',
            'review_cards_limit' => 'integer|min:0|max:100'
        ]);

        $user = Auth::user();
        $collection = Collection::findOrFail($request->collection_id);

        if (!$collection->canBeAccessedBy($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this collection'
            ], 403);
        }

        // Create study session
        $session = StudySession::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
            'study_type' => $request->study_type,
            'started_at' => now(),
            'cards_studied' => 0,
            'correct_answers' => 0
        ]);

        // Get cards to study based on study type
        $cardData = $this->getCardsForSession($request, $user, $collection);
        $cards = $cardData['cards'];
        $cardIds = $cardData['card_ids']; // Lấy danh sách ID các thẻ trong phiên

        // Lưu danh sách card IDs vào session/cache với key duy nhất
        $sessionKey = 'study_session_cards_' . $session->id;
        cache()->put($sessionKey, $cardIds, now()->addHours(24)); // Cache 24 giờ

        // Đếm trạng thái chỉ trong phiên
        $counts = $this->getSessionCardCounts($user->id, $cardIds);

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $session->id,
                'study_type' => $request->study_type,
                'collection' => $collection->only(['id', 'collection_name', 'description']),
                'cards' => $cards,
                'total_cards' => count($cards),
                'card_counts' => $counts
            ]
        ]);
    }

    // Submit answer for flashcard study
    public function submitFlashcardAnswer(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|exists:study_sessions,id',
            'flashcard_id' => 'required|exists:flashcards,id',
            'quality' => 'required|integer|min:0|max:5',
            'study_mode' => 'required|in:front_to_back,back_to_front,both',
            'response_time_ms' => 'integer|min:0'
        ]);

        $user = Auth::user();
        $session = StudySession::where('id', $request->session_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $flashcard = Flashcard::findOrFail($request->flashcard_id);
        $quality = $request->quality;
        $studyMode = $request->study_mode;
        $responseTime = $request->input('response_time_ms', 0);

        // Lấy danh sách card IDs từ cache
        $sessionKey = 'study_session_cards_' . $session->id;
        $cardIds = cache()->get($sessionKey, []);

        // Get or create flashcard status
        $status = FlashcardStatus::firstOrCreate(
            [
                'user_id' => $user->id,
                'flashcard_id' => $flashcard->id
            ],
            [
                'status' => 'new',
                'study_mode' => $studyMode,
                'interval' => 0,
                'interval_minutes' => 0,
                'ease_factor' => 2.5,
                'repetitions' => 0,
                'lapses' => 0,
                'is_leech' => false,
                'due_date' => now()
            ]
        );

        // Lưu trạng thái cũ để log
        $prevInterval = $status->interval;
        $prevEaseFactor = $status->ease_factor;
        $prevRepetitions = $status->repetitions;

        // Update status using SM-2 algorithm
        $status->updateSM2($quality, 'flashcard');

        // Update session stats
        $session->increment('cards_studied');
        if ($quality >= 3) {
            $session->increment('correct_answers');
        }

        // Create review log
        FlashcardReviewLog::create([
            'user_id' => $user->id,
            'flashcard_id' => $flashcard->id,
            'study_type' => 'flashcard',
            'study_mode' => $studyMode,
            'response_time_ms' => $responseTime,
            'quality' => $quality,
            'prev_interval' => $prevInterval,
            'new_interval' => $status->interval,
            'prev_ease_factor' => $prevEaseFactor,
            'new_ease_factor' => $status->ease_factor,
            'prev_repetitions' => $prevRepetitions,
            'new_repetitions' => $status->repetitions,
            'reviewed_at' => now()
        ]);

        // Chỉ đếm trạng thái của các thẻ trong phiên hiện tại
        $cardCounts = $this->getSessionCardCounts($user->id, $cardIds);

        return response()->json([
            'success' => true,
            'data' => [
                'flashcard_id' => $flashcard->id,
                'next_review_at' => $status->next_review_at,
                'interval_minutes' => $status->interval_minutes,
                'status' => $status->status,
                'is_correct' => $quality >= 3,
                'card_counts' => $cardCounts,
            ]
        ]);
    }

    // Submit answer for typing study
    public function submitTypingAnswer(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|exists:study_sessions,id',
            'flashcard_id' => 'required|exists:flashcards,id',
            'answer' => 'required|string',
            'quality' => 'required|integer|min:0|max:5',
            'study_mode' => 'required|in:front_to_back,back_to_front,both',
            'response_time_ms' => 'integer|min:0'
        ]);

        $user = Auth::user();
        $session = StudySession::where('id', $request->session_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $flashcard = Flashcard::findOrFail($request->flashcard_id);
        $userAnswer = trim(strtolower($request->answer));
        $quality = $request->quality;
        $studyMode = $request->study_mode;

        // Determine correct answer based on study mode
        $correctAnswer = $studyMode === 'back_to_front'
            ? trim(strtolower($flashcard->front))
            : trim(strtolower($flashcard->back));

        $responseTime = $request->input('response_time_ms', 0);

        // Lấy danh sách card IDs từ cache
        $sessionKey = 'study_session_cards_' . $session->id;
        $cardIds = cache()->get($sessionKey, []);

        // Calculate accuracy and quality score
        $similarity = $this->calculateStringSimilarity($userAnswer, $correctAnswer);
        $isCorrect = $similarity >= 0.8; // 80% similarity threshold

        // Convert similarity to SM-2 quality score (0-5)
        // $quality = $this->similarityToQuality($similarity);

        // Get or create flashcard status
        $status = FlashcardStatus::firstOrCreate(
            [
                'user_id' => $user->id,
                'flashcard_id' => $flashcard->id
            ],
            [
                'status' => 'new',
                'study_mode' => $studyMode,
                'interval' => 0,
                'interval_minutes' => 0,
                'ease_factor' => 2.5,
                'repetitions' => 0,
                'lapses' => 0,
                'is_leech' => false,
                'due_date' => now()
            ]
        );

        // Lưu trạng thái cũ để log
        $prevInterval = $status->interval;
        $prevEaseFactor = $status->ease_factor;
        $prevRepetitions = $status->repetitions;

        // Update status using SM-2 algorithm
        $status->updateSM2($quality, 'typing');

        // Update session stats
        $session->increment('cards_studied');
        if ($isCorrect && $quality >= 3) {
            $session->increment('correct_answers');
        }

        // Create review log
        FlashcardReviewLog::create([
            'user_id' => $user->id,
            'flashcard_id' => $flashcard->id,
            'study_type' => 'typing',
            'study_mode' => $studyMode,
            'response_time_ms' => $responseTime,
            'quality' => $quality,
            'prev_interval' => $prevInterval,
            'new_interval' => $status->interval,
            'prev_ease_factor' => $prevEaseFactor,
            'new_ease_factor' => $status->ease_factor,
            'prev_repetitions' => $prevRepetitions,
            'new_repetitions' => $status->repetitions,
            'reviewed_at' => now()
        ]);

        // Chỉ đếm trạng thái của các thẻ trong phiên hiện tại
        $cardCounts = $this->getSessionCardCounts($user->id, $cardIds);

        return response()->json([
            'success' => true,
            'data' => [
                'flashcard_id' => $flashcard->id,
                'is_correct' => $isCorrect,
                'similarity' => round($similarity * 100, 2),
                'correct_answer' => $studyMode === 'back_to_front' ? $flashcard->front : $flashcard->back,
                'user_answer' => $request->answer,
                'next_review_at' => $status->next_review_at,
                'interval_minutes' => $status->interval_minutes,
                'status' => $status->status,
                'quality_score' => $quality,
                'card_counts' => $cardCounts,
            ]
        ]);
    }

    // Submit answer for matching study (game_match)
    public function submitMatchingAnswer(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|exists:study_sessions,id',
            'matches' => 'required|array',
            'matches.*.flashcard_id' => 'required|exists:flashcards,id',
            'matches.*.selected_answer' => 'required|string',
            'study_mode' => 'required|in:front_to_back,back_to_front,both',
            'response_time_ms' => 'integer|min:0'
        ]);

        $user = Auth::user();
        $session = StudySession::where('id', $request->session_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $matches = $request->matches;
        $studyMode = $request->study_mode;
        $responseTime = $request->input('response_time_ms', 0);
        $results = [];
        $totalCorrect = 0;

        foreach ($matches as $match) {
            $flashcard = Flashcard::findOrFail($match['flashcard_id']);

            // Determine correct answer based on study mode
            $correctAnswer = $studyMode === 'back_to_front'
                ? $flashcard->front
                : $flashcard->back;

            $isCorrect = trim(strtolower($match['selected_answer'])) === trim(strtolower($correctAnswer));

            if ($isCorrect) {
                $totalCorrect++;
            }

            // For matching, we don't use SM-2 algorithm, just track performance
            // Create review log
            FlashcardReviewLog::create([
                'user_id' => $user->id,
                'flashcard_id' => $flashcard->id,
                'study_type' => 'game_match',
                'study_mode' => $studyMode,
                'response_time_ms' => $responseTime,
                'quality' => $isCorrect ? 5 : 0, // Simple binary scoring
                'prev_interval' => 0,
                'new_interval' => 0,
                'prev_ease_factor' => 2.5,
                'new_ease_factor' => 2.5,
                'prev_repetitions' => 0,
                'new_repetitions' => 0,
                'reviewed_at' => now()
            ]);

            $results[] = [
                'flashcard_id' => $flashcard->id,
                'is_correct' => $isCorrect,
                'correct_answer' => $correctAnswer,
                'selected_answer' => $match['selected_answer']
            ];
        }

        // Update session stats
        $session->increment('cards_studied', count($matches));
        $session->increment('correct_answers', $totalCorrect);

        $accuracy = count($matches) > 0 ? round(($totalCorrect / count($matches)) * 100, 2) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'results' => $results,
                'total_cards' => count($matches),
                'correct_answers' => $totalCorrect,
                'accuracy' => $accuracy,
                'response_time_ms' => $responseTime
            ]
        ]);
    }

    // Submit answer for test study
    public function submitQuizAnswer(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|exists:study_sessions,id',
            'answers' => 'required|array',
            'answers.*.flashcard_id' => 'required|exists:flashcards,id',
            'answers.*.selected_option' => 'required|integer|min:0|max:3',
            'study_mode' => 'required|in:front_to_back,back_to_front,both',
            'response_time_ms' => 'integer|min:0'
        ]);

        $user = Auth::user();
        $session = StudySession::where('id', $request->session_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $answers = $request->answers;
        $studyMode = $request->study_mode;
        $responseTime = $request->input('response_time_ms', 0);
        $results = [];
        $totalCorrect = 0;

        foreach ($answers as $answer) {
            $flashcard = Flashcard::findOrFail($answer['flashcard_id']);
            $selectedOption = $answer['selected_option'];

            // Generate the same options that were sent to the client
            $options = $this->generateTestOptions($flashcard, $flashcard->collection, $studyMode);
            $isCorrect = $selectedOption === $options['correct_index'];

            if ($isCorrect) {
                $totalCorrect++;
            }

            // Create review log
            FlashcardReviewLog::create([
                'user_id' => $user->id,
                'flashcard_id' => $flashcard->id,
                'study_type' => 'test',
                'study_mode' => $studyMode,
                'response_time_ms' => $responseTime,
                'quality' => $isCorrect ? 5 : 0, // Simple binary scoring
                'prev_interval' => 0,
                'new_interval' => 0,
                'prev_ease_factor' => 2.5,
                'new_ease_factor' => 2.5,
                'prev_repetitions' => 0,
                'new_repetitions' => 0,
                'reviewed_at' => now()
            ]);

            $results[] = [
                'flashcard_id' => $flashcard->id,
                'is_correct' => $isCorrect,
                'correct_index' => $options['correct_index'],
                'selected_index' => $selectedOption,
                'correct_answer' => $studyMode === 'back_to_front' ? $flashcard->front : $flashcard->back,
                'options' => $options['options']
            ];
        }

        // Update session stats
        $session->increment('cards_studied', count($answers));
        $session->increment('correct_answers', $totalCorrect);

        $accuracy = count($answers) > 0 ? round(($totalCorrect / count($answers)) * 100, 2) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'results' => $results,
                'total_cards' => count($answers),
                'correct_answers' => $totalCorrect,
                'accuracy' => $accuracy,
                'response_time_ms' => $responseTime
            ]
        ]);
    }

    // End study session
    public function endSession(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|exists:study_sessions,id'
        ]);

        $user = Auth::user();
        $session = StudySession::where('id', $request->session_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($session->ended_at) {
            return response()->json([
                'success' => false,
                'message' => 'Session already ended'
            ], 400);
        }

        // End the session
        $session->update(['ended_at' => now()]);
        $session->endSession();

        // Xóa cache
        $sessionKey = 'study_session_cards_' . $session->id;
        cache()->forget($sessionKey);

        // Update daily statistics
        LearningStatistic::updateDailyStats(
            $user->id,
            $session->started_at->toDateString(),
            $session->duration_minutes,
            1
        );

        return response()->json([
            'success' => true,
            'data' => [
                'session_summary' => [
                    'duration_minutes' => $session->duration_minutes,
                    'cards_studied' => $session->cards_studied,
                    'correct_answers' => $session->correct_answers,
                    'accuracy' => $session->getAccuracyPercentage(),
                    'study_type' => $session->study_type,
                    'collection_name' => $session->collection->collection_name
                ]
            ]
        ]);
    }

    // Get due cards for review (có thể lấy theo collection và limit fc)
    public function getDueCards(Request $request): JsonResponse
    {
        $request->validate([
            'collection_id' => 'sometimes|exists:collections,id',
            'limit' => 'integer|min:1|max:100'
        ]);

        $user = Auth::user();
        $limit = $request->input('limit', 50);

        $query = FlashcardStatus::where('user_id', $user->id)
            ->where('due_date', '<=', now())
            ->with(['flashcard.collection']);

        if ($request->has('collection_id')) {
            $query->whereHas('flashcard', function ($q) use ($request) {
                $q->where('collection_id', $request->collection_id);
            });
        }

        $dueCards = $query->orderBy('due_date')
            ->limit($limit)
            ->get();

        $cards = $dueCards->map(function ($status) {
            return [
                'id' => $status->flashcard->id,
                'front' => $status->flashcard->front,
                'back' => $status->flashcard->back,
                'pronunciation' => $status->flashcard->pronunciation,
                'kanji' => $status->flashcard->kanji,
                'image' => $status->flashcard->image,
                'collection_name' => $status->flashcard->collection->collection_name,
                'collection_id' => $status->flashcard->collection_id,
                'status' => $status->status,
                'due_date' => $status->due_date,
                'interval_minutes' => $status->interval_minutes,
                'repetitions' => $status->repetitions,
                'is_leech' => $status->is_leech
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'due_cards' => $cards,
                'total_due' => $dueCards->count()
            ]
        ]);
    }

    // Get study statistics (tất cả collection)
    public function getStudyStats(Request $request): JsonResponse
    {
        $user = Auth::user();
        $today = now()->toDateString();

        // Today's stats
        $todayStats = LearningStatistic::where('user_id', $user->id)
            ->where('study_date', $today)
            ->first();

        // Due cards count
        $dueCardsCount = FlashcardStatus::where('user_id', $user->id)
            ->where('due_date', '<=', now())
            ->count();

        // New cards count
        $newCardsCount = Flashcard::whereDoesntHave('statuses', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->count();

        // Cards by status
        $statusCounts = FlashcardStatus::where('user_id', $user->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return response()->json([
            'success' => true,
            'data' => [
                'today' => [
                    'study_time_minutes' => $todayStats->total_study_time ?? 0,
                    'cards_studied' => ($todayStats->new_cards ?? 0) + ($todayStats->review_cards ?? 0),
                    'sessions_count' => $todayStats->total_sessions ?? 0
                ],
                'cards_overview' => [
                    'due_cards' => $dueCardsCount,
                    'new_cards' => $newCardsCount,
                    'learning_cards' => $statusCounts['learning'] ?? 0,
                    'review_cards' => ($statusCounts['young'] ?? 0) + ($statusCounts['re-learning'] ?? 0),
                    'mastered_cards' => $statusCounts['mastered'] ?? 0,
                    'total_cards' => array_sum($statusCounts->toArray()) + $newCardsCount
                ]
            ]
        ]);
    }

    // ? Reset card progress (for difficult cards)
    public function resetCard(Request $request): JsonResponse
    {
        $request->validate([
            'flashcard_id' => 'required|exists:flashcards,id'
        ]);

        $user = Auth::user();
        $flashcard = Flashcard::findOrFail($request->flashcard_id);

        $status = FlashcardStatus::where('user_id', $user->id)
            ->where('flashcard_id', $flashcard->id)
            ->first();

        if (!$status) {
            return response()->json([
                'success' => false,
                'message' => 'Card status not found'
            ], 404);
        }

        // Reset to initial state
        $status->update([
            'status' => 'new',
            'interval' => 0,
            'interval_minutes' => 0,
            'ease_factor' => 2.5,
            'repetitions' => 0,
            'lapses' => 0,
            'is_leech' => false,
            'due_date' => now(),
            'next_review_at' => now(),
            'last_reviewed_at' => null
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Card progress reset successfully'
        ]);
    }

    // Get cards for study session
    private function getCardsForSession(Request $request, $user, Collection $collection): array
    {
        $limit = $request->input('limit', 5);
        $newCardsLimit = $request->input('new_cards_limit', 2);
        $reviewCardsLimit = $request->input('review_cards_limit', 3);
        $studyType = $request->study_type;

        $cards = [];
        $cardIds = []; // Lưu trữ ID các thẻ trong phiên
        $allCards = collect();

        // 1. Ưu tiên Due cards (thẻ đến hạn ôn tập)
        $dueCards = $collection->flashcards()
            ->whereHas('statuses', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->whereIn('status', ['re-learning', 'young', 'mastered'])
                    ->where('next_review_at', '<=', now());
            })
            ->with(['statuses' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->inRandomOrder()
            ->limit($reviewCardsLimit)
            ->get();

        $allCards = $allCards->merge($dueCards);
        $remainingLimit = $limit - $allCards->count();

        // 2. Learning cards (thẻ đang học)
        if ($remainingLimit > 0) {
            $learningCards = $collection->flashcards()
                ->whereHas('statuses', function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->where('status', 'learning');
                })
                ->with(['statuses' => function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                }])
                ->inRandomOrder()
                ->limit($remainingLimit)
                ->get();

            $allCards = $allCards->merge($learningCards);
            $remainingLimit = $limit - $allCards->count();
        }

        // 3. New cards cuối cùng (bao gồm cả thẻ chưa có status và thẻ có status 'new')
        if ($remainingLimit > 0) {
            // Thẻ chưa từng học (không có status)
            $unseenCards = $collection->flashcards()
                ->whereDoesntHave('statuses', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->inRandomOrder()
                ->limit(min($newCardsLimit, $remainingLimit))
                ->get();

            $allCards = $allCards->merge($unseenCards);
            $remainingLimit = $limit - $allCards->count();

            // Nếu vẫn còn chỗ, lấy thêm thẻ có status 'new'
            if ($remainingLimit > 0) {
                $newStatusCards = $collection->flashcards()
                    ->whereHas('statuses', function ($query) use ($user) {
                        $query->where('user_id', $user->id)
                            ->where('status', 'new');
                    })
                    ->with(['statuses' => function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    }])
                    ->inRandomOrder()
                    ->limit($remainingLimit)
                    ->get();

                $allCards = $allCards->merge($newStatusCards);
            }
        }

        // Giới hạn tổng số thẻ
        $allCards = $allCards->take($limit);

        // Get new cards (cards without status)
//        $newCardsQuery = $collection->flashcards()
//            ->whereDoesntHave('statuses', function ($query) use ($user) {
//                $query->where('user_id', $user->id);
//            })
//            ->inRandomOrder()
//            ->limit($newCardsLimit);

        // Get review cards (due cards)
//        $reviewCardsQuery = $collection->flashcards()
//            ->whereHas('statuses', function ($query) use ($user) {
//                $query->where('user_id', $user->id)
//                    ->where('due_date', '<=', now());
//            })
//            ->with(['statuses' => function ($query) use ($user) {
//                $query->where('user_id', $user->id);
//            }])
//            ->inRandomOrder()
//            ->limit($reviewCardsLimit);

//        $newCards = $newCardsQuery->get();
//        $reviewCards = $reviewCardsQuery->get();

        // Merge and limit total cards
//        $allCards = $newCards->merge($reviewCards)->take($limit);

        // Đếm trạng thái
//        $counts = $this->getFlashcardStatusCounts($user->id, $collection->id);
//        $counts['new'] += $newCards->count();

        foreach ($allCards as $card) {
            $cardIds[] = $card->id; // Thêm ID vào danh sách
            $status = $card->statuses->first()?->status ?? 'new';

            $cardData = [
                'id' => $card->id,
                'front' => $card->front,
                'back' => $card->back,
                'pronunciation' => $card->pronunciation,
                'kanji' => $card->kanji,
                'image' => $card->image,
                'is_new' => !$card->statuses->count(),
                'status' => $status
            ];

            // Add specific data based on study type
            switch ($studyType) {
                case 'game_match':
                    $cardData['matching_options'] = $this->generateMatchingOptions($card, $collection);
                    break;
                case 'test':
                    $cardData['test_options'] = $this->generateTestOptions($card, $collection);
                    break;
            }

            $cards[] = $cardData;
        }

        return [
            'cards' => $cards,
            'card_ids' => $cardIds // Trả về danh sách ID
        ];
    }

    // Generate matching options for a flashcard
    private function generateMatchingOptions(Flashcard $flashcard, Collection $collection, string $studyMode = 'front_to_back'): array
    {
        $correctAnswer = $studyMode === 'back_to_front' ? $flashcard->front : $flashcard->back;
        $searchField = $studyMode === 'back_to_front' ? 'front' : 'back';

        // Get 3 random wrong answers from the same collection
        $wrongAnswers = $collection->flashcards()
            ->where('id', '!=', $flashcard->id)
            ->inRandomOrder()
            ->limit(3)
            ->pluck($searchField)
            ->toArray();

        // If not enough options, get from other collections with same language
        if (count($wrongAnswers) < 3) {
            $languageField = $studyMode === 'back_to_front' ? 'language_front' : 'language_back';
            $targetLanguage = $studyMode === 'back_to_front' ? $collection->language_front : $collection->language_back;

            $additionalAnswers = Flashcard::whereHas('collection', function ($query) use ($collection, $languageField, $targetLanguage) {
                $query->where($languageField, $targetLanguage)
                    ->where('id', '!=', $collection->id);
            })
                ->where('id', '!=', $flashcard->id)
                ->whereNotIn($searchField, $wrongAnswers)
                ->inRandomOrder()
                ->limit(3 - count($wrongAnswers))
                ->pluck($searchField)
                ->toArray();

            $wrongAnswers = array_merge($wrongAnswers, $additionalAnswers);
        }

        $allOptions = array_merge([$correctAnswer], $wrongAnswers);
        shuffle($allOptions);

        return [
            'options' => $allOptions,
            'correct_answer' => $correctAnswer
        ];
    }

    // Generate test options for a flashcard
    private function generateTestOptions(Flashcard $flashcard, Collection $collection, string $studyMode = 'front_to_back'): array
    {
        $correctAnswer = $studyMode === 'back_to_front' ? $flashcard->front : $flashcard->back;
        $searchField = $studyMode === 'back_to_front' ? 'front' : 'back';

        // Get 3 random wrong answers from the same collection
        $wrongAnswers = $collection->flashcards()
            ->where('id', '!=', $flashcard->id)
            ->inRandomOrder()
            ->limit(3)
            ->pluck($searchField)
            ->toArray();

        // If not enough options, get from other collections with same language
        if (count($wrongAnswers) < 3) {
            $languageField = $studyMode === 'back_to_front' ? 'language_front' : 'language_back';
            $targetLanguage = $studyMode === 'back_to_front' ? $collection->language_front : $collection->language_back;

            $additionalAnswers = Flashcard::whereHas('collection', function ($query) use ($collection, $languageField, $targetLanguage) {
                $query->where($languageField, $targetLanguage)
                    ->where('id', '!=', $collection->id);
            })
                ->where('id', '!=', $flashcard->id)
                ->whereNotIn($searchField, $wrongAnswers)
                ->inRandomOrder()
                ->limit(3 - count($wrongAnswers))
                ->pluck($searchField)
                ->toArray();

            $wrongAnswers = array_merge($wrongAnswers, $additionalAnswers);
        }

        $allOptions = array_merge([$correctAnswer], $wrongAnswers);
        $correctIndex = 0;

        // Shuffle options and find new correct index
        $shuffledOptions = [];
        $indices = range(0, count($allOptions) - 1);
        shuffle($indices);

        foreach ($indices as $i => $originalIndex) {
            $shuffledOptions[] = $allOptions[$originalIndex];
            if ($originalIndex === 0) { // Original correct answer index
                $correctIndex = $i;
            }
        }

        return [
            'options' => $shuffledOptions,
            'correct_index' => $correctIndex
        ];
    }

    // Calculate string similarity using Levenshtein distance
    private function calculateStringSimilarity(string $str1, string $str2): float
    {
        $len1 = strlen($str1);
        $len2 = strlen($str2);

        if ($len1 === 0 && $len2 === 0) {
            return 1.0;
        }

        if ($len1 === 0 || $len2 === 0) {
            return 0.0;
        }

        $distance = levenshtein($str1, $str2);
        $maxLength = max($len1, $len2);

        return 1 - ($distance / $maxLength);
    }

    // Convert similarity score to SM-2 quality score
    private function similarityToQuality(float $similarity): int
    {
        if ($similarity >= 0.95) return 5; // Perfect
        if ($similarity >= 0.85) return 4; // Good
        if ($similarity >= 0.70) return 3; // Acceptable
        if ($similarity >= 0.50) return 2; // Hard but remembered
        if ($similarity >= 0.25) return 1; // Very hard
        return 0; // Failed
    }

    // Count by status for cards in current session only
    private function getSessionCardCounts($userId, $cardIds): array
    {
        if (empty($cardIds)) {
            return [
                'new' => 0,
                'learning' => 0,
                'due' => 0,
            ];
        }

        // Đếm các flashcard chưa từng học (chưa có status) trong phiên
        $unseenCount = Flashcard::whereIn('id', $cardIds)
            ->whereDoesntHave('statuses', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })->count();

        // Đếm các flashcard có status là 'new' trong phiên
        $newStatusCount = FlashcardStatus::where('user_id', $userId)
            ->where('status', 'new')
            ->whereIn('flashcard_id', $cardIds)
            ->count();

        // Cộng hai loại new lại
        $newCount = $unseenCount + $newStatusCount;

        // Đếm các flashcard đang học trong phiên
        $learningCount = FlashcardStatus::where('user_id', $userId)
            ->where('status', 'learning')
            ->whereIn('flashcard_id', $cardIds)
            ->count();

        // Đếm các flashcard đến hạn ôn tập trong phiên
        $dueCount = FlashcardStatus::where('user_id', $userId)
            ->whereIn('status', ['re-learning', 'young', 'mastered'])
            ->where('next_review_at', '<=', now())
            ->whereIn('flashcard_id', $cardIds)
            ->count();

        return [
            'new' => $newCount,
            'learning' => $learningCount,
            'due' => $dueCount,
        ];
    }

    // Count by status (của cả collection)
    private function getFlashcardStatusCounts($userId, $collectionId = null): array
    {
        // Lấy ID các flashcard trong collection (nếu có)
        $flashcardIdsQuery = Flashcard::query();
        if ($collectionId) {
            $flashcardIdsQuery->where('collection_id', $collectionId);
        }
        $flashcardIds = $flashcardIdsQuery->pluck('id');

        // Đếm các flashcard chưa từng học (chưa có status)
        $unseenCount = Flashcard::whereIn('id', $flashcardIds)
            ->whereDoesntHave('statuses', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })->count();

        // Đếm các flashcard có status là 'new'
        $newStatusCount = FlashcardStatus::where('user_id', $userId)
            ->where('status', 'new')
            ->whereIn('flashcard_id', $flashcardIds)
            ->count();

        // Cộng hai loại new lại
        $newCount = $unseenCount + $newStatusCount;

        // Các loại khác
        $learningCount = FlashcardStatus::where('user_id', $userId)
            ->where('status', 'learning')
            ->whereIn('flashcard_id', $flashcardIds)
            ->count();

        $dueCount = FlashcardStatus::where('user_id', $userId)
            ->whereIn('status', ['re-learning', 'young', 'mastered'])
            ->where('next_review_at', '<=', now())
            ->whereIn('flashcard_id', $flashcardIds)
            ->count();

        return [
            'new' => $newCount,
            'learning' => $learningCount,
            'due' => $dueCount,
        ];
    }

}
