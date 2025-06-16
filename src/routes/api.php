<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FlashcardReviewController;
use App\Http\Controllers\GoogleAPI;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\RecentCollectionController;
use App\Http\Controllers\StudyController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CollectionsController;
use App\Http\Controllers\FlashcardsController;
use App\Http\Controllers\AdminController;

Route::middleware('auth:sanctum')->group(function () {
    // Routes liên quan đến collection
    Route::prefix('collections')->controller(CollectionsController::class)->group(function () {
        Route::get('/', 'index'); // Lấy danh sách collection user sở hữu
        Route::post('/', 'store'); // Tạo mới collection
        Route::get('/{id}', 'show'); // Lấy chi tiết 1 collection theo id
        Route::put('/{id}', 'update'); // Cập nhật collection
        Route::delete('/{id}', 'destroy'); // Xóa collection
        Route::put('{id}/update-star', 'updateStarCount'); // Update star count
        Route::post('{id}/duplicate', 'duplicateCollection'); // Duplicate collection
        Route::get('/user/{userId}', 'getPublicCollectionsByUser'); // Lấy danh sách các collection công khai của người dùng
        Route::get('/progress/{collectionId, $userId}', 'getCollectionProgress'); // Get study progress for a collection
    });

    // Routes liên quan đến recent collection
    Route::prefix('recent-collections')->controller(RecentCollectionController::class)->group(function () {
        Route::post('/', 'store');
        Route::get('/', 'index');
    });

    // Routes liên quan đến flashcard
    Route::prefix('flashcards')->controller(FlashcardsController::class)->group(function () {
        Route::post('/', 'store'); // Thêm flashcard
        Route::get('/{id}', 'show'); // Lấy chi tiết flashcard
        Route::put('/{id}', 'update'); // Cập nhật flashcard
        Route::delete('/{id}', 'destroy'); // Xóa flashcard
    });

    // Routes liên quan đến flashcard nhưng FlashcardReviewController
    Route::prefix('fc-review')->controller(FlashcardReviewController::class)->group(function () {
        Route::get('/progress-summary/{collectionId}', 'getProgressSummary');
    });

    Route::prefix('study')->controller(StudyController::class)->group(function () {
        Route::post('/start', 'startSession'); // Bắt đầu phiên học mới
        Route::post('/flashcard/submit', 'submitFlashcardAnswer'); // Gửi câu trả lời kiểu flashcard
        Route::post('/typing/submit', 'submitTypingAnswer'); // Gửi câu trả lời kiểu typing
        Route::post('/matching/submit', 'submitMatchingAnswer'); // Gửi câu trả lời kiểu matching
        Route::post('/submit-quiz-answer', 'submitQuizAnswer');
        Route::post('/end', 'endSession');
        Route::get('/due-cards', 'getDueCards');
        Route::get('/stats', 'getStudyStats');
        Route::post('/reset-card', 'resetCard');
    });

    // Routes liên quan đến thống kê
    Route::prefix('progress')->controller(ProgressController::class)->group(function () {
        Route::get('/dashboard', 'getDashboard');
        Route::get('/collection/{collectionId}', 'getCollectionProgress');
        Route::get('/analytics', 'getAnalytics');
        Route::get('/heatmap', 'getStudyHeatmap');
        Route::get('/leaderboard', 'getLeaderboard');
        Route::get('/streak', 'getStudyStreak');
    });

    // Routes liên quan đến user (Yêu cầu đăng nhập)
    Route::prefix('users')->controller(UserController::class)->group(function () {
        Route::get('/', 'getUser'); // Lấy thông tin user
        Route::post('/change-password', 'changePassword'); // Tự đổi pass
        Route::post('/updateAccount', 'updateAccount'); // Update profile
        Route::delete('/delete', 'deleteAccount'); // User tự xóa tài khoản
    });

    // Routes link-unlink Google
    Route::prefix('auth/google')->controller(GoogleAPI::class)->group(function () {
        Route::post('/unlink', 'unlinkGoogle');
    });

    // Routes liên quan đến admin (Chỉ dành cho admin)
    Route::prefix('admins')->controller(AdminController::class)->group(function () {
        // Quản lý người dùng
        Route::get('/users', 'getUsers'); // Lấy danh sách người dùng
        Route::post('/users', 'addUsers'); // Add user
        Route::post('/users/{id}/toggle-lock', 'toggleUserStatus'); // Khóa/Mở khóa tài khoản
        Route::delete('/user/{id}', 'deleteUser');

        // Quản lý thông báo
        Route::get('/notifications', 'getNotifications');
        Route::post('/notifications/send', 'sendNotification'); // Gửi thông báo

        // Quản lý collection
        Route::get('/collections', 'getAllCollections'); // Xem danh sách collection
        Route::post('/collections', 'createCollection'); // Tạo collection
        Route::put('/collections/{id}', 'updateCollection'); // Cập nhật collection
        Route::delete('/collections/{id}', 'deleteCollection'); // Xóa collection

        // CRUD từ vựng của collection
        Route::get('/flashcards', 'getAllFlashcards'); // Xem danh sách tất cả flashcard trong hệ thống
        Route::get('/collections/{id}/flashcards', 'getFlashcards'); // Xem danh sách flashcard
        Route::post('/collections/flashcards', 'addFlashcard'); // Thêm từ vựng
        Route::put('/flashcards/{flashcardId}', 'updateFlashcard'); // Cập nhật từ vựng
        Route::delete('/flashcards/{flashcardId}', 'deleteFlashcard'); // Xóa từ vựng
    });

    // Đăng xuất
    Route::post('/logout', [AuthController::class, 'logout']);
});

// Routes không yêu cầu đăng nhập
Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'register'); // Đăng ký
    Route::post('/login', 'login'); // Đăng nhập
    Route::post('/forgot-password', 'forgotPassword'); // Gửi email reset password
    Route::post('/reset-password', 'resetPassword'); // Đặt lại mật khẩu
});

Route::controller(GoogleAPI::class)->group(function () {
    Route::get('/auth/google/redirect', 'redirect');
    Route::get('/auth/google/callback', 'callback');
});

Route::controller(CollectionsController::class)->group(function () {
    Route::get('/search-public', 'searchPublicCollections');    // search api
    Route::get('/public-collections', 'getPublicCollections');  // Lấy danh sách collection công khai
    Route::get('/public-collections/{id}', 'getPublicCollectionDetail');    // public collection with flashcard
});
