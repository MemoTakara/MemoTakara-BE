<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Flashcards;
use Illuminate\Support\Facades\Storage;

class JLPTVocabFCSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $folderPath = storage_path('app/datajson/jp-en/'); // Thư mục chứa file JSON
        $files = glob($folderPath . '*.json'); // Lấy tất cả file JSON

        if (empty($files)) {
            $this->command->error('Không tìm thấy file JSON nào trong thư mục!');
            return;
        }

        foreach ($files as $file) {
            $json = file_get_contents($file);
            $data = json_decode($json, true);

            foreach ($data as $item) {
                Flashcards::create([
                    'front' => $item['front'],
                    'back' => $item['back'],
                    'pronunciation' => $item['pronunciation'],
                    'collection_id' => $item['collection_id'],
                ]);
            }

            $this->command->info("Import thành công: " . basename($file));
        }
    }
}
