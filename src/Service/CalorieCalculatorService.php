<?php

namespace App\Service;

class CalorieCalculatorService
{
    private const ACTIVITY_FACTORS = [
        1 => 1.2,   // Sédentaire
        2 => 1.375, // Légèrement actif
        3 => 1.55,  // Modérément actif
        4 => 1.725, // Très actif
        5 => 1.9,   // Extrêmement actif
    ];

    private const GOAL_ADJUSTMENTS = [
        'bulk'     => +300,
        'cut'      => -300,
        'maintain' => 0,
        'strength' => +100,
    ];

    /**
     * Calcule la dépense calorique journalière via Mifflin-St Jeor (formule neutre).
     * TMB = (10 × poids kg) + (6.25 × taille cm) - (5 × âge) - 78
     */
    public function calculate(float $weight, float $height, int $age, int $activityLevel, string $goal): int
    {
        // TMB Mifflin-St Jeor (formule neutre sans genre)
        $tmb = (10 * $weight) + (6.25 * $height) - (5 * $age) - 78;

        $factor = self::ACTIVITY_FACTORS[$activityLevel] ?? 1.2;
        $tdee   = (int) round($tmb * $factor);

        $adjustment = self::GOAL_ADJUSTMENTS[$goal] ?? 0;

        return max(1200, $tdee + $adjustment);
    }
}
