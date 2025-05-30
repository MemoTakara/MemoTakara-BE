<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Flashcard;
use Illuminate\Support\Facades\DB;

class HanziFCSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $folderPath = storage_path('app/datajson/cn-en/'); // Thư mục chứa file JSON
        $files = glob($folderPath . '*.json'); // Lấy tất cả file JSON

        if (empty($files)) {
            $this->command->error('Không tìm thấy file JSON nào trong thư mục!');
            return;
        }

        // Track số lượng flashcards cho mỗi collection
        $collectionCardCounts = [];

        foreach ($files as $file) {
            $json = file_get_contents($file);
            $data = json_decode($json, true);

            if (!$data || !is_array($data)) {
                $this->command->error("File không hợp lệ: " . basename($file));
                continue;
            }

            foreach ($data as $item) {
                // Validate dữ liệu cần thiết
                if (!isset($item['front']) || !isset($item['back']) || !isset($item['collection_id'])) {
                    $this->command->warn("Bỏ qua item thiếu dữ liệu trong file: " . basename($file));
                    continue;
                }

                // Kiểm tra collection có tồn tại không
                $collectionExists = DB::table('collections')
                    ->where('id', $item['collection_id'])
                    ->exists();

                if (!$collectionExists) {
                    $this->command->warn("Collection ID {$item['collection_id']} không tồn tại, bỏ qua flashcard");
                    continue;
                }

                Flashcard::create([
                    'front' => $item['front'],
                    'back' => $item['back'],
                    'pronunciation' => $item['pronunciation'] ?? null,
                    'kanji' => $item['kanji'] ?? null,
                    'image' => $item['image'] ?? null,
                    'extra_data' => isset($item['extra_data']) ? json_encode($item['extra_data']) : null,
                    'collection_id' => $item['collection_id'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Đếm số lượng card cho collection
                if (!isset($collectionCardCounts[$item['collection_id']])) {
                    $collectionCardCounts[$item['collection_id']] = 0;
                }
                $collectionCardCounts[$item['collection_id']]++;
            }

            $this->command->info("Import thành công: " . basename($file));
        }

        // Cập nhật total_cards cho các collections
        foreach ($collectionCardCounts as $collectionId => $cardCount) {
            DB::table('collections')
                ->where('id', $collectionId)
                ->update([
                    'total_cards' => $cardCount,
                    'updated_at' => now()
                ]);

            $this->command->info("Updated Collection ID {$collectionId}: {$cardCount} cards");
        }

        $totalFlashcards = array_sum($collectionCardCounts);
        $this->command->info("Hanzi Flashcards seeding completed! Total: {$totalFlashcards} flashcards");
    }
}
