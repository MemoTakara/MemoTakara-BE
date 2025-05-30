<?php

namespace Database\Seeders;

use App\Models\Flashcard;
use App\Models\FlashcardStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FCStatusSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Bắt đầu tạo flashcard statuses...');

        $users = User::all();
        $totalUsers = $users->count();

        if ($totalUsers === 0) {
            $this->command->error('Không có user nào trong database!');
            return;
        }

        // Lấy flashcards theo batch để tránh memory overflow
        $batchSize = 500;
        $totalFlashcards = Flashcard::count();

        if ($totalFlashcards === 0) {
            $this->command->error('Không có flashcard nào trong database!');
            return;
        }

        $this->command->info("Sẽ tạo statuses cho {$totalUsers} users và {$totalFlashcards} flashcards");

        foreach ($users as $userIndex => $user) {
            $this->command->info("Xử lý user {$user->id} ({$user->name}) - " . ($userIndex + 1) . "/{$totalUsers}");

            $processedCount = 0;

            // Xử lý flashcards theo batch
            Flashcard::chunk($batchSize, function ($flashcards) use ($user, &$processedCount) {
                $statusData = [];
                $currentTime = now();

                foreach ($flashcards as $flashcard) {
                    // Kiểm tra xem status đã tồn tại chưa
                    $exists = FlashcardStatus::where('user_id', $user->id)
                        ->where('flashcard_id', $flashcard->id)
                        ->exists();

                    if (!$exists) {
                        $statusData[] = [
                            'user_id' => $user->id,
                            'flashcard_id' => $flashcard->id,
                            'status' => 'new',
                            'study_mode' => 'front_to_back',
                            'interval' => 1,
                            'interval_minutes' => 1,
                            'ease_factor' => 2.5,
                            'repetitions' => 0,
                            'lapses' => 0,
                            'is_leech' => false,
                            'last_reviewed_at' => null,
                            'next_review_at' => null,
                            'due_date' => $currentTime, // Mặc định due ngay
                            'created_at' => $currentTime,
                            'updated_at' => $currentTime
                        ];
                    }

                    $processedCount++;
                }

                // Bulk insert để tăng hiệu suất
                if (!empty($statusData)) {
                    DB::table('flashcard_statuses')->insert($statusData);
                }

                $this->command->info("  Đã xử lý {$processedCount} flashcards...");
            });
        }

        // Thống kê cuối cùng
        $totalStatuses = FlashcardStatus::count();

        $this->command->info("Flashcard Status seeding completed!");
        $this->command->info("Tổng số statuses đã tạo: {$totalStatuses}");
        $this->command->info("Trung bình: " . round($totalStatuses / $totalUsers) . " statuses/user");
    }
}
