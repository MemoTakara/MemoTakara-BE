<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\GoogleAPI;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\StudyController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FlashcardController;
use App\Http\Controllers\AdminController;

Route::middleware('auth:sanctum')->group(function () {
    // Routes liên quan đến collection
    Route::prefix('collections')->controller(CollectionController::class)->group(function () {
//        Route::get('/user/{userId}', 'getPublicCollectionsByUser'); // Lấy danh sách các collection công khai của người dùng
        Route::get('/my-collections', 'myCollections'); // Lấy các bộ sưu tập của người dùng đã xác thực
        Route::get('/recent', 'recentCollections'); // Lấy các bộ sưu tập gần đây của người dùng
        Route::get('/popular', 'popular'); // Lấy các bộ sưu tập phổ biến

        Route::post('/', 'store'); // Tạo một bộ sưu tập mới
        Route::get('/{id}', 'show'); // Lấy thông tin một bộ sưu tập cụ thể
        Route::put('/{id}', 'update'); // Cập nhật một bộ sưu tập cụ thể
        Route::delete('/{id}', 'destroy'); // Xóa một bộ sưu tập cụ thể

        Route::post('/{id}/duplicate', 'duplicate'); // Sao chép một bộ sưu tập
        Route::get('/{collectionId}/rate', 'getRatings'); // Get all ratings for a specific collection
        Route::post('/{id}/rate', 'rate'); // Đánh giá một bộ sưu tập

//        Route::get('/featured', 'featuredCollections'); // Lấy các bộ sưu tập nổi bật
//        Route::get('/search', 'search'); // Tìm kiếm bộ sưu tập với bộ lọc nâng cao
//        Route::get('/by-tags', 'byTags'); // Lấy các bộ sưu tập theo thẻ
    });

    // Routes liên quan đến flashcard
    Route::prefix('flashcards')->controller(FlashcardController::class)->group(function () {
        Route::get('/collection/{collectionId}', 'index'); // Lấy danh sách flashcard trong một collection

        Route::get('/{id}', 'show'); // Lấy thông tin chi tiết của một flashcard
        Route::post('/', 'store'); // Tạo một flashcard mới
        Route::put('/{id}', 'update'); // Cập nhật một flashcard
        Route::delete('/{id}', 'destroy'); // Xóa một flashcard
        Route::post('/bulk', 'bulkStore'); // Tạo flashcard hàng loạt

        Route::post('/{id}/toggle-leech', 'toggleLeech'); // Đánh dấu hoặc bỏ đánh dấu flashcard khó
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
        Route::get('/{fcId}/review-history', 'getReviewHistory'); // Lấy lịch sử ôn tập của một flashcard
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

    // Routes liên quan đến notification
    Route::prefix('notification')->controller(NotificationController::class)->group(function () {
        Route::get('/', 'index'); // Lấy danh sách thông báo của người dùng đã xác thực
        Route::get('/unread-count', 'unreadCount'); // Lấy số lượng thông báo chưa đọc
        Route::get('/recent', 'recent'); // Lấy các thông báo gần đây (50 thông báo mới nhất)

        Route::post('/{id}/mark-read', 'markAsRead'); // Đánh dấu một thông báo là đã đọc
        Route::post('/mark-read-multiple', 'markMultipleAsRead'); // Đánh dấu nhiều thông báo là đã đọc
        Route::post('/mark-all-read', 'markAllAsRead'); // Đánh dấu tất cả thông báo là đã đọc

        Route::delete('/{id}', 'delete'); // Xóa một thông báo
        Route::delete('/delete-multiple', 'deleteMultiple'); // Xóa nhiều thông báo
        Route::delete('/delete-all-read', 'deleteAllRead'); // Xóa tất cả thông báo đã đọc

        Route::get('/statistics', 'statistics'); // Lấy thống kê thông báo

        Route::post('/send', 'send'); // Gửi thông báo (chỉ admin)
        Route::post('/send-bulk', 'sendBulk'); // Gửi thông báo hàng loạt (chỉ admin)

        Route::get('/{id}', 'show'); // Lấy chi tiết một thông báo
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

Route::controller(CollectionController::class)->group(function () {
    Route::get('/collections', 'index'); // Lấy danh sách tất cả bộ sưu tập với bộ lọc và phân trang
    Route::get('/public-collections/{id}', 'show'); // Lấy thông tin một bộ sưu tập cụ thể
});
