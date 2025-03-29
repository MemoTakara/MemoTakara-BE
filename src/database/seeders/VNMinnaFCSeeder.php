<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Flashcards;
use Illuminate\Support\Facades\Storage;

class VNMinnaFCSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $folderPath = storage_path('app/datajson/jp-vn-minna/') . '*.json';
        $files = glob($folderPath);

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
                    'kanji' => $item['kanji'],
                    'collection_id' => $item['collection_id'],
                ]);
            }

            $this->command->info("Import thành công: " . basename($file));
        }
    }
}
