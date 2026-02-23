<?php

namespace App\DataFixtures;

use App\Entity\Exercise;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ExerciseFixtures extends Fixture
{
    private const EXERCISES = [
        // Pectoraux
        ['name' => 'Bench Press', 'muscleGroup' => 'Pectoraux', 'difficulty' => 'intermediate', 'description' => 'Développé couché à la barre, exercice de base pour les pectoraux.'],
        ['name' => 'Incline Bench Press', 'muscleGroup' => 'Pectoraux', 'difficulty' => 'intermediate', 'description' => 'Développé incliné pour cibler les pectoraux supérieurs.'],
        ['name' => 'Dips', 'muscleGroup' => 'Pectoraux', 'difficulty' => 'intermediate', 'description' => 'Pompes aux barres parallèles, travail des pectoraux et triceps.'],
        ['name' => 'Push-up', 'muscleGroup' => 'Pectoraux', 'difficulty' => 'beginner', 'description' => 'Pompe classique au poids du corps.'],
        // Dos
        ['name' => 'Deadlift', 'muscleGroup' => 'Dos', 'difficulty' => 'advanced', 'description' => 'Soulevé de terre, exercice roi du dos complet.'],
        ['name' => 'Pull-up', 'muscleGroup' => 'Dos', 'difficulty' => 'intermediate', 'description' => 'Traction à la barre fixe, excellent pour le grand dorsal.'],
        ['name' => 'Barbell Row', 'muscleGroup' => 'Dos', 'difficulty' => 'intermediate', 'description' => 'Rowing barre, travail du dos et des biceps.'],
        ['name' => 'Lat Pulldown', 'muscleGroup' => 'Dos', 'difficulty' => 'beginner', 'description' => 'Tirage vertical à la poulie haute.'],
        // Jambes
        ['name' => 'Squat', 'muscleGroup' => 'Jambes', 'difficulty' => 'intermediate', 'description' => 'Squat barre, exercice fondamental pour les jambes et fessiers.'],
        ['name' => 'Leg Press', 'muscleGroup' => 'Jambes', 'difficulty' => 'beginner', 'description' => 'Presse à cuisses, alternative au squat sur machine.'],
        ['name' => 'Romanian Deadlift', 'muscleGroup' => 'Jambes', 'difficulty' => 'intermediate', 'description' => 'Soulevé de terre jambes tendues, ischio-jambiers.'],
        ['name' => 'Lunges', 'muscleGroup' => 'Jambes', 'difficulty' => 'beginner', 'description' => 'Fentes avant au poids du corps ou avec haltères.'],
        ['name' => 'Leg Curl', 'muscleGroup' => 'Jambes', 'difficulty' => 'beginner', 'description' => 'Curl jambe couché sur machine, ischio-jambiers.'],
        ['name' => 'Calf Raise', 'muscleGroup' => 'Jambes', 'difficulty' => 'beginner', 'description' => 'Élévations des mollets.'],
        // Épaules
        ['name' => 'Overhead Press', 'muscleGroup' => 'Épaules', 'difficulty' => 'intermediate', 'description' => 'Développé militaire barre, deltoïdes et trapèzes.'],
        ['name' => 'Lateral Raise', 'muscleGroup' => 'Épaules', 'difficulty' => 'beginner', 'description' => 'Élévations latérales aux haltères pour les deltoïdes latéraux.'],
        ['name' => 'Face Pull', 'muscleGroup' => 'Épaules', 'difficulty' => 'beginner', 'description' => 'Tirage visage à la corde, deltoïdes postérieurs.'],
        // Biceps
        ['name' => 'Barbell Curl', 'muscleGroup' => 'Biceps', 'difficulty' => 'beginner', 'description' => 'Curl barre, exercice de base des biceps.'],
        ['name' => 'Hammer Curl', 'muscleGroup' => 'Biceps', 'difficulty' => 'beginner', 'description' => 'Curl marteau aux haltères, brachial et biceps.'],
        ['name' => 'Incline Dumbbell Curl', 'muscleGroup' => 'Biceps', 'difficulty' => 'intermediate', 'description' => 'Curl incliné, étirement maximal des biceps.'],
        // Triceps
        ['name' => 'Tricep Pushdown', 'muscleGroup' => 'Triceps', 'difficulty' => 'beginner', 'description' => 'Extension triceps à la poulie haute.'],
        ['name' => 'Skull Crusher', 'muscleGroup' => 'Triceps', 'difficulty' => 'intermediate', 'description' => 'Extension couché barre, long chef des triceps.'],
        ['name' => 'Close Grip Bench Press', 'muscleGroup' => 'Triceps', 'difficulty' => 'intermediate', 'description' => 'Développé couché prise serrée pour les triceps.'],
        // Abdominaux
        ['name' => 'Plank', 'muscleGroup' => 'Abdominaux', 'difficulty' => 'beginner', 'description' => 'Gainage planaire, renforcement de la sangle abdominale.'],
        ['name' => 'Crunch', 'muscleGroup' => 'Abdominaux', 'difficulty' => 'beginner', 'description' => 'Crunch classique, abdominaux supérieurs.'],
        ['name' => 'Leg Raise', 'muscleGroup' => 'Abdominaux', 'difficulty' => 'intermediate', 'description' => 'Levées de jambes, abdominaux inférieurs.'],
        ['name' => 'Cable Crunch', 'muscleGroup' => 'Abdominaux', 'difficulty' => 'intermediate', 'description' => 'Crunch à la poulie, abdominaux avec charge.'],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::EXERCISES as $data) {
            $exercise = new Exercise();
            $exercise->setName($data['name']);
            $exercise->setMuscleGroup($data['muscleGroup']);
            $exercise->setDifficulty($data['difficulty']);
            $exercise->setDescription($data['description']);
            $manager->persist($exercise);
        }

        $manager->flush();
    }
}
