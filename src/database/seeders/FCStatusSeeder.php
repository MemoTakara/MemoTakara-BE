<?php

namespace Database\Seeders;

use App\Models\Flashcards;
use App\Models\FlashcardStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class FCStatusSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $flashcards = Flashcards::all();

        foreach ($users as $user) {
            foreach ($flashcards as $flashcard) {
                FlashcardStatus::firstOrCreate([
                    'user_id' => $user->id,
                    'flashcard_id' => $flashcard->id,
                ], [
                    'status' => 'new',
                ]);
            }
        }
    }
}
