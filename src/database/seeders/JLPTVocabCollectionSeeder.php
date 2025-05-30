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
        // Tags cần thiết
        $baseTags = [
            'jp' => 'Japanese Language',
            'jlpt' => 'JLPT Test',
            'jlpt_n1' => 'JLPT Level N1',
            'jlpt_n2' => 'JLPT Level N2',
            'jlpt_n3' => 'JLPT Level N3',
            'jlpt_n4' => 'JLPT Level N4',
            'jlpt_n5' => 'JLPT Level N5',
            'beginner' => 'Beginner',
            'intermediate' => 'Intermediate',
            'advanced' => 'Advanced'
        ];

        // Tạo các tag - `tags` table seeder
        foreach ($baseTags as $tagName => $description) {
            DB::table('tags')->updateOrInsert(
                ['name' => $tagName],
                [
                    'name' => $tagName,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }

        // `collections` table seeder
        $collections = [
            [
                'collection_name' => 'JLPT N5 Vocabulary Collection',
                'description' => 'Includes basic words (about 800) for beginners, covering everyday topics like greetings, numbers, time, and simple verbs.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['jp', 'jlpt', 'jlpt_n5', 'beginner'],
                'user_id' => 1, // ID của admin
            ],
            [
                'collection_name' => 'JLPT N4 Vocabulary Collection',
                'description' => 'Expands to around 1,500 words, adding more verbs, adjectives, and expressions for daily conversations and simple reading comprehension.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['jp', 'jlpt', 'jlpt_n4', 'beginner'],
                'user_id' => 1, // ID của admin
            ],
            [
                'collection_name' => 'JLPT N3 Vocabulary Collection',
                'description' => 'Contains about 3,750 words, covering more abstract terms and nuanced expressions for intermediate learners, enabling smoother conversations and reading.',
                'privacy' => '1',
                'difficulty_level' => 'intermediate',
                'is_featured' => true,
                'tags' => ['jp', 'jlpt', 'jlpt_n3', 'intermediate'],
                'user_id' => 1, // ID của admin
            ],
            [
                'collection_name' => 'JLPT N2 Vocabulary Collection',
                'description' => 'Features around 6,000 words, including formal and business-related vocabulary, helping learners understand news, essays, and professional discussions.',
                'privacy' => '1',
                'difficulty_level' => 'advanced',
                'is_featured' => true,
                'tags' => ['jp', 'jlpt', 'jlpt_n2', 'advanced'],
                'user_id' => 1, // ID của admin
            ],
            [
                'collection_name' => 'JLPT N1 Vocabulary Collection',
                'description' => 'The most advanced level, with about 10,000 words, covering complex and literary expressions for near-native comprehension of academic, business, and literary texts.',
                'privacy' => '1',
                'difficulty_level' => 'advanced',
                'is_featured' => true,
                'tags' => ['jp', 'jlpt', 'jlpt_n1', 'advanced'],
                'user_id' => 1, // ID của admin
            ],
        ];

        foreach ($collections as $collectionData) {
            $collection_id = DB::table('collections')->insertGetId([
                'collection_name' => $collectionData['collection_name'],
                'description' => $collectionData['description'],
                'privacy' => $collectionData['privacy'],
                'difficulty_level' => $collectionData['difficulty_level'],
                'language_front' => 'jp',
                'language_back' => 'en',
                'is_featured' => $collectionData['is_featured'],
                'user_id' => $collectionData['user_id'],
                'total_cards' => 0, // Sẽ được cập nhật sau khi import flashcards
                'average_rating' => 0.00,
                'total_ratings' => 0,
                'total_duplicates' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Gắn tags cho collection
            foreach ($collectionData['tags'] as $tagName) {
                $tag_id = DB::table('tags')
                    ->where('name', $tagName)
                    ->value('id');

                if ($tag_id) {
                    DB::table('collection_tag')->updateOrInsert(
                        [
                            'collection_id' => $collection_id,
                            'tag_id' => $tag_id
                        ],
                        []
                    );
                }
            }

            $this->command->info("Created collection: {$collectionData['collection_name']} (ID: {$collection_id})");

        }

        $this->command->info('JLPT Collections seeding completed successfully!');
    }
}
