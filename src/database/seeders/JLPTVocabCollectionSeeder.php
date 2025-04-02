<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JLPTVocabCollectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tags table Seeder
        $tags = [
            'Japanese'
        ];

        foreach ($tags as $tag) {
            DB::table('tags')->insert([
                'name' => $tag,
            ]);
        }

        // Collections table Seeder
        $collections = [
            [
                'collection_name' => 'JLPT N5 Vocabulary Collection',
                'description' => 'Includes basic words (about 800) for beginners, covering everyday topics like greetings, numbers, time, and simple verbs.',
                'privacy' => '1',
                'tag' => 'Japanese',
                'user_id' => 1, // ID của admin
            ],
            [
                'collection_name' => 'JLPT N4 Vocabulary Collection',
                'description' => 'Expands to around 1,500 words, adding more verbs, adjectives, and expressions for daily conversations and simple reading comprehension.',
                'privacy' => '1',
                'tag' => 'Japanese',
                'user_id' => 1, // ID của admin
            ],
            [
                'collection_name' => 'JLPT N3 Vocabulary Collection',
                'description' => 'Contains about 3,750 words, covering more abstract terms and nuanced expressions for intermediate learners, enabling smoother conversations and reading.',
                'privacy' => '1',
                'tag' => 'Japanese',
                'user_id' => 1, // ID của admin
            ],
            [
                'collection_name' => 'JLPT N2 Vocabulary Collection',
                'description' => 'Features around 6,000 words, including formal and business-related vocabulary, helping learners understand news, essays, and professional discussions.',
                'privacy' => '1',
                'tag' => 'Japanese',
                'user_id' => 1, // ID của admin
            ],
            [
                'collection_name' => 'JLPT N1 Vocabulary Collection',
                'description' => 'The most advanced level, with about 10,000 words, covering complex and literary expressions for near-native comprehension of academic, business, and literary texts.',
                'privacy' => '1',
                'tag' => 'Japanese',
                'user_id' => 1, // ID của admin
            ],
        ];

        foreach ($collections as $collection) {
            $collection_id = DB::table('collections')->insertGetId([
                'collection_name' => $collection['collection_name'],
                'description' => $collection['description'],
                'privacy' => $collection['privacy'],
                'user_id' => $collection['user_id'],
            ]);

            $tag = $collection['tag'];

            // Kiểm tra tag trước khi lấy tag_id
            if (!empty($tag)) {
                $tag_id = DB::table('tags')->where('name', $tag)->value('id');

                // Nếu tag không tồn tại, hãy tạo tag mới
                if (is_null($tag_id)) {
                    $tag_id = DB::table('tags')->insertGetId(['name' => $tag]);
                }
            } else {
                // Ghi log hoặc xử lý lỗi nếu tag không hợp lệ
                \Log::error('Tag is empty or not valid for collection: ' . $collection['collection_name']);
                continue; // Bỏ qua collection này nếu tag không hợp lệ
            }

            // Collection_tag table Seeder
            DB::table('collection_tag')->updateOrInsert(
                ['collection_id' => $collection_id, 'tag_id' => $tag_id],
                []
            );
        }
    }
}
