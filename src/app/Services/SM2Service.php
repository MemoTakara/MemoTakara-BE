<?php

namespace App\Services;

namespace App\Services;

class SM2Service
{
    public function calculate(int $prevInterval, float $prevEF, int $prevRepetitions, int $quality): array
    {
        $repetitions = $quality < 3 ? 0 : $prevRepetitions + 1;
        $EF = $prevEF;

        if ($quality >= 3) {
            $EF += (0.1 - (5 - $quality) * (0.08 + (5 - $quality) * 0.02));
            $EF = max(1.3, $EF);
        }

        $interval = match (true) {
            $repetitions === 1 => 1,
            $repetitions === 2 => 6,
            $repetitions > 2 => round($prevInterval * $EF),
            default => 1,
        };

        return [
            'interval' => $interval,
            'ease_factor' => round($EF, 2),
            'repetitions' => $repetitions,
            'next_review_at' => now()->addDays($interval),
            'last_reviewed_at' => now(),
        ];
    }
}

