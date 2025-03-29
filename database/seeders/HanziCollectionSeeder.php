<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HanziCollectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('collections')->insert([
            [
                'collection_name' => 'HSK 1 Vocabulary Collection',
                'description' => 'This collection includes basic vocabulary for beginners learning Chinese, aligned with the HSK Level 1 syllabus. It covers around 150 essential words and phrases for everyday communication, such as greetings, numbers, dates, and common verbs. It is ideal for learners starting their journey in Mandarin.',
                'privacy' => '1',
                'tag' => 'Chinese',
                'user_id' => 1, // ID của admin
            ],
            [
                'collection_name' => 'HSK 2 Vocabulary Collection',
                'description' => 'This collection builds upon the foundation of HSK 1, containing around 300 words. It introduces more complex expressions, including those used in daily life and basic workplace interactions. Learners will expand their understanding of adjectives, verbs, and sentence patterns.',
                'privacy' => '1',
                'tag' => 'Chinese',
                'user_id' => 1, // ID của admin
            ],
            [
                'collection_name' => 'HSK 3 Vocabulary Collection',
                'description' => 'Designed for intermediate learners, this collection features approximately 600 words. It focuses on enhancing conversational skills, covering a wider range of topics such as travel, hobbies, and opinions. It introduces more complex grammar structures and sentence formations, helping learners communicate with greater fluency.',
                'privacy' => '1',
                'tag' => 'Chinese',
                'user_id' => 1, // ID của admin
            ],
            [
                'collection_name' => 'HSK 4 Vocabulary Collection',
                'description' => 'This collection contains around 1,200 words, expanding learners ability to discuss various topics in greater detail. It includes more abstract terms, idioms, and complex sentence structures, helping learners express thoughts and opinions more naturally in both spoken and written Chinese.',
                'privacy' => '1',
                'tag' => 'Chinese',
                'user_id' => 1, // ID của admin
            ],
            [
                'collection_name' => 'HSK 5 Vocabulary Collection',
                'description' => 'With approximately 2,500 words, this collection is designed for advanced learners aiming for fluency. It covers a broad range of subjects, including culture, society, and business. Learners will encounter more sophisticated vocabulary and idiomatic expressions, improving their ability to read newspapers, watch Chinese media, and engage in deep discussions.',
                'privacy' => '1',
                'tag' => 'Chinese',
                'user_id' => 1, // ID của admin
            ],
            [
                'collection_name' => 'HSK 6 Vocabulary Collection',
                'description' => 'The most advanced level, HSK 6, includes about 5,000 words. This collection is intended for learners who want to achieve near-native proficiency. It focuses on complex sentence structures, nuanced meanings, and professional terminology. Mastery of this vocabulary enables learners to understand Chinese literature, academic texts, and participate in high-level discussions with ease.',
                'privacy' => '1',
                'tag' => 'Chinese',
                'user_id' => 1, // ID của admin
            ],
        ]);
    }
}
