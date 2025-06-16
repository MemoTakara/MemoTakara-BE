<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HanziCollectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tags cần thiết
        $baseTags = [
            'cn' => 'Chinese',
            'hsk' => 'HSK Test',
            'hsk1' => 'HSK Level 1',
            'hsk2' => 'HSK Level 2',
            'hsk3' => 'HSK Level 3',
            'hsk4' => 'HSK Level 4',
            'hsk5' => 'HSK Level 5',
            'hsk6' => 'HSK Level 6',
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
                'collection_name' => 'HSK 1 Vocabulary Collection',
                'description' => 'This collection includes basic vocabulary for beginners learning Chinese, aligned with the HSK Level 1 syllabus. It covers around 150 essential words and phrases for everyday communication, such as greetings, numbers, dates, and common verbs. It is ideal for learners starting their journey in Mandarin.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['cn', 'hsk', 'hsk1', 'beginner'],
                'user_id' => 1, // ID của admin
            ],
            [
                'collection_name' => 'HSK 2 Vocabulary Collection',
                'description' => 'This collection builds upon the foundation of HSK 1, containing around 300 words. It introduces more complex expressions, including those used in daily life and basic workplace interactions. Learners will expand their understanding of adjectives, verbs, and sentence patterns.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['cn', 'hsk', 'hsk2', 'beginner'],
                'user_id' => 1, // ID của admin
            ],
            [
                'collection_name' => 'HSK 3 Vocabulary Collection',
                'description' => 'Designed for intermediate learners, this collection features approximately 600 words. It focuses on enhancing conversational skills, covering a wider range of topics such as travel, hobbies, and opinions. It introduces more complex grammar structures and sentence formations, helping learners communicate with greater fluency.',
                'privacy' => '1',
                'difficulty_level' => 'intermediate',
                'is_featured' => true,
                'tags' => ['cn', 'hsk', 'hsk3', 'intermediate'],
                'user_id' => 1, // ID của admin
            ],
            [
                'collection_name' => 'HSK 4 Vocabulary Collection',
                'description' => 'This collection contains around 1,200 words, expanding learners ability to discuss various topics in greater detail. It includes more abstract terms, idioms, and complex sentence structures, helping learners express thoughts and opinions more naturally in both spoken and written Chinese.',
                'privacy' => '1',
                'difficulty_level' => 'intermediate',
                'is_featured' => true,
                'tags' => ['cn', 'hsk', 'hsk4', 'intermediate'],
                'user_id' => 1, // ID của admin
            ],
            [
                'collection_name' => 'HSK 5 Vocabulary Collection',
                'description' => 'With approximately 2,500 words, this collection is designed for advanced learners aiming for fluency. It covers a broad range of subjects, including culture, society, and business. Learners will encounter more sophisticated vocabulary and idiomatic expressions, improving their ability to read newspapers, watch Chinese media, and engage in deep discussions.',
                'privacy' => '1',
                'difficulty_level' => 'advanced',
                'is_featured' => true,
                'tags' => ['cn', 'hsk', 'hsk5', 'advanced'],
                'user_id' => 1, // ID của admin
            ],
            [
                'collection_name' => 'HSK 6 Vocabulary Collection',
                'description' => 'The most advanced level, HSK 6, includes about 5,000 words. This collection is intended for learners who want to achieve near-native proficiency. It focuses on complex sentence structures, nuanced meanings, and professional terminology. Mastery of this vocabulary enables learners to understand Chinese literature, academic texts, and participate in high-level discussions with ease.',
                'privacy' => '1',
                'difficulty_level' => 'advanced',
                'is_featured' => true,
                'tags' => ['cn', 'hsk', 'hsk6', 'advanced'],
                'user_id' => 1, // ID của admin
            ],
        ];

        foreach ($collections as $collectionData) {
            $collection_id = DB::table('collections')->insertGetId([
                'collection_name' => $collectionData['collection_name'],
                'description' => $collectionData['description'],
                'privacy' => $collectionData['privacy'],
                'difficulty_level' => $collectionData['difficulty_level'],
                'language_front' => 'zh',
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

        $this->command->info('HSK Collections seeding completed successfully!');
    }
}
