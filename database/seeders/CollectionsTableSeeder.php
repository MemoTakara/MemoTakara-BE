<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CollectionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('collections')->insert([
            [
                'name' => 'HSK 1 Vocabulary Collection', 
                'description' => 'This collection includes basic vocabulary for beginners learning Chinese, aligned with the HSK Level 1 syllabus. It covers around 150 essential words and phrases for everyday communication, such as greetings, numbers, dates, and common verbs. It is ideal for learners starting their journey in Mandarin.', 
                'privacy' => true,
                'tag' => 'Chinese',
                'star_count' => 4.8,
                'user_id' => 2, // ID của user 2 - admin
            ],
            [
                'name' => 'HSK 2 Vocabulary Collection', 
                'description' => 'This collection builds upon the foundation of HSK 1, containing around 300 words. It introduces more complex expressions, including those used in daily life and basic workplace interactions. Learners will expand their understanding of adjectives, verbs, and sentence patterns.', 
                'privacy' => true,
                'tag' => 'Chinese',
                'star_count' => 4.7,
                'user_id' => 2, // ID của user 2 - admin
            ],
            [
                'name' => 'HSK 3 Vocabulary Collection', 
                'description' => 'Designed for intermediate learners, this collection features approximately 600 words. It focuses on enhancing conversational skills, covering a wider range of topics such as travel, hobbies, and opinions. It introduces more complex grammar structures and sentence formations, helping learners communicate with greater fluency.', 
                'privacy' => true,
                'tag' => 'Chinese',
                'star_count' => 4.6,
                'user_id' => 2, // ID của user 2 - admin
            ],
        ]);
    }
}
