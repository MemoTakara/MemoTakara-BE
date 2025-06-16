<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\Flashcard;
use App\Models\FlashcardStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('Bắt đầu seed database...');

        // Thứ tự seed quan trọng để đảm bảo foreign key constraints
        $seeders = [
            UsersTableSeeder::class => 'Seeding users...',
            HanziCollectionSeeder::class => 'Seeding HSK collections và tags...',
            HanziFCSeeder::class => 'Seeding HSK flashcards...',
            JLPTVocabCollectionSeeder::class => 'Seeding JLPT collections...',
            JLPTVocabFCSeeder::class => 'Seeding JLPT flashcards...',
            VNMinnaCollectionSeeder::class => 'Seeding Minna collections...',
            VNMinnaFCSeeder::class => 'Seeding Minna flashcards...',
            FCStatusSeeder::class => 'Seeding flashcard statuses (có thể mất thời gian)...'
        ];

        foreach ($seeders as $seederClass => $message) {
            $this->command->info("{$message}");

            try {
                $this->call($seederClass);
                $this->command->info("{$seederClass} completed successfully!");
            } catch (\Exception $e) {
                $this->command->error("Error in {$seederClass}: " . $e->getMessage());

                // Có thể chọn dừng hoặc tiếp tục
                if ($this->command->confirm("Có muốn tiếp tục với seeder tiếp theo không?", true)) {
                    continue;
                } else {
                    $this->command->error("Database seeding stopped.");
                    return;
                }
            }
        }

        $this->command->info('Database seeding completed successfully!');

        // Hiển thị thống kê tổng quan
        $this->displaySeedingStats();
    }

    /**
     * Hiển thị thống kê sau khi seed
     */
    private function displaySeedingStats(): void
    {
        try {
            $stats = [
                'Users' => User::count(),
                'Collections' => Collection::count(),
                'Tags' => DB::table('tags')->count(),
                'Flashcards' => Flashcard::count(),
                'Flashcard Statuses' => FlashcardStatus::count(),
                'Collection-Tag Relationships' => DB::table('collection_tag')->count(),
            ];

            $this->command->info("\nSEEDING STATISTICS:");
            $this->command->info("═══════════════════════");

            foreach ($stats as $item => $count) {
                $this->command->info(sprintf("%-25s: %s", $item, number_format($count)));
            }

            $this->command->info("═══════════════════════");

            // Hiển thị breakdown collections
            $collections = DB::table('collections')
                ->select('collection_name', 'total_cards', 'difficulty_level')
                ->orderBy('id')
                ->get();

            if ($collections->count() > 0) {
                $this->command->info("\nCOLLECTIONS BREAKDOWN:");
                foreach ($collections as $collection) {
                    $this->command->info("  • {$collection->collection_name}: {$collection->total_cards} cards ({$collection->difficulty_level})");
                }
            }

        } catch (\Exception $e) {
            $this->command->warn("Could not generate statistics: " . $e->getMessage());
        }
    }
}
