<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

class OpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(private readonly OpenApiFactoryInterface $decorated) {}

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $paths   = $openApi->getPaths();

        // ── Auth ──────────────────────────────────────────────────────────────

        $paths->addPath('/api/register', new PathItem(post: new Operation(
            operationId: 'register',
            tags: ['Auth'],
            responses: [
                '201' => ['description' => 'Compte créé, token JWT retourné'],
                '422' => ['description' => 'Erreur de validation'],
            ],
            summary: 'Créer un compte',
            requestBody: new RequestBody(
                content: new ArrayObject([
                    'application/json' => ['schema' => ['type' => 'object', 'required' => ['email', 'password'], 'properties' => [
                        'email'    => ['type' => 'string', 'example' => 'user@example.com'],
                        'password' => ['type' => 'string', 'example' => 'Password123'],
                    ]]],
                ]),
            ),
            security: [],
        )));

        // ── Profile ───────────────────────────────────────────────────────────

        $paths->addPath('/api/profile', new PathItem(
            get: new Operation(
                operationId: 'getProfile',
                tags: ['Profil'],
                responses: ['200' => ['description' => 'Profil utilisateur']],
                summary: 'Récupérer le profil de l\'utilisateur connecté',
            ),
            put: new Operation(
                operationId: 'updateProfile',
                tags: ['Profil'],
                responses: ['200' => ['description' => 'Profil mis à jour']],
                summary: 'Mettre à jour le profil',
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'application/json' => ['schema' => ['type' => 'object', 'properties' => [
                            'fullName'      => ['type' => 'string'],
                            'weight'        => ['type' => 'number'],
                            'height'        => ['type' => 'number'],
                            'age'           => ['type' => 'integer'],
                            'goal'          => ['type' => 'string', 'enum' => ['bulk', 'cut', 'maintain', 'strength']],
                            'activityLevel' => ['type' => 'string', 'enum' => ['sedentary', 'light', 'moderate', 'active', 'very_active']],
                        ]]],
                    ]),
                ),
            ),
        ));

        // ── Programmes ────────────────────────────────────────────────────────

        $paths->addPath('/api/programs/generate', new PathItem(post: new Operation(
            operationId: 'generateProgram',
            tags: ['Programmes'],
            responses: [
                '201' => ['description' => 'Programme généré et sauvegardé'],
                '503' => ['description' => 'Erreur API IA'],
            ],
            summary: 'Générer un programme d\'entraînement via IA (Groq)',
            requestBody: new RequestBody(
                content: new ArrayObject([
                    'application/json' => ['schema' => ['type' => 'object', 'properties' => [
                        'goal'            => ['type' => 'string', 'enum' => ['bulk', 'cut', 'maintain', 'strength']],
                        'experienceLevel' => ['type' => 'string', 'enum' => ['beginner', 'intermediate', 'advanced']],
                        'sessionsPerWeek' => ['type' => 'integer', 'enum' => [3, 4, 5, 6]],
                        'programDuration' => ['type' => 'integer', 'enum' => [4, 8, 12]],
                        'sessionDuration' => ['type' => 'integer', 'enum' => [45, 60, 90]],
                        'equipment'       => ['type' => 'string', 'enum' => ['full_gym', 'home_gym', 'bodyweight']],
                    ]]],
                ]),
            ),
        )));

        $paths->addPath('/api/programs', new PathItem(get: new Operation(
            operationId: 'listPrograms',
            tags: ['Programmes'],
            responses: ['200' => ['description' => 'Liste des programmes']],
            summary: 'Lister les programmes de l\'utilisateur',
        )));

        $paths->addPath('/api/programs/{id}', new PathItem(get: new Operation(
            operationId: 'getProgram',
            tags: ['Programmes'],
            responses: [
                '200' => ['description' => 'Programme avec contenu complet'],
                '404' => ['description' => 'Programme introuvable'],
            ],
            summary: 'Récupérer un programme par ID',
        )));

        // ── Workouts ──────────────────────────────────────────────────────────

        $paths->addPath('/api/workouts/stats', new PathItem(get: new Operation(
            operationId: 'workoutStats',
            tags: ['Workouts'],
            responses: ['200' => ['description' => 'thisWeek, thisMonth']],
            summary: 'Statistiques de séances (semaine / mois)',
        )));

        $paths->addPath('/api/workouts', new PathItem(
            get: new Operation(
                operationId: 'listWorkouts',
                tags: ['Workouts'],
                responses: ['200' => ['description' => 'Liste des séances avec exercices']],
                summary: 'Lister les séances',
            ),
            post: new Operation(
                operationId: 'createWorkout',
                tags: ['Workouts'],
                responses: ['201' => ['description' => 'Séance créée']],
                summary: 'Créer une séance',
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'application/json' => ['schema' => ['type' => 'object', 'properties' => [
                            'date'      => ['type' => 'string', 'example' => '2026-02-23'],
                            'notes'     => ['type' => 'string'],
                            'completed' => ['type' => 'boolean'],
                            'programId' => ['type' => 'string', 'nullable' => true],
                            'exercises' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                                'exerciseId' => ['type' => 'string'],
                                'sets'       => ['type' => 'integer'],
                                'reps'       => ['type' => 'string'],
                                'weight'     => ['type' => 'number', 'nullable' => true],
                            ]]],
                        ]]],
                    ]),
                ),
            ),
        ));

        $paths->addPath('/api/workouts/{id}', new PathItem(get: new Operation(
            operationId: 'getWorkout',
            tags: ['Workouts'],
            responses: [
                '200' => ['description' => 'Séance avec exercices'],
                '404' => ['description' => 'Séance introuvable'],
            ],
            summary: 'Récupérer une séance par ID',
        )));

        // ── Nutrition ─────────────────────────────────────────────────────────

        $paths->addPath('/api/meals', new PathItem(
            get: new Operation(
                operationId: 'listMeals',
                tags: ['Nutrition'],
                responses: ['200' => ['description' => 'Liste des repas']],
                summary: 'Lister les repas (optionnel: ?date=YYYY-MM-DD)',
            ),
            post: new Operation(
                operationId: 'createMeal',
                tags: ['Nutrition'],
                responses: ['201' => ['description' => 'Repas ajouté']],
                summary: 'Ajouter un repas',
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'application/json' => ['schema' => ['type' => 'object', 'required' => ['name', 'calories'], 'properties' => [
                            'name'     => ['type' => 'string', 'example' => 'Poulet riz'],
                            'calories' => ['type' => 'integer', 'example' => 650],
                            'date'     => ['type' => 'string', 'example' => '2026-02-23'],
                        ]]],
                    ]),
                ),
            ),
        ));

        $paths->addPath('/api/meals/{id}', new PathItem(delete: new Operation(
            operationId: 'deleteMeal',
            tags: ['Nutrition'],
            responses: [
                '204' => ['description' => 'Repas supprimé'],
                '404' => ['description' => 'Repas introuvable'],
            ],
            summary: 'Supprimer un repas',
        )));

        $paths->addPath('/api/nutrition/stats', new PathItem(get: new Operation(
            operationId: 'nutritionStats',
            tags: ['Nutrition'],
            responses: ['200' => ['description' => 'caloriesToday, caloriesWeekAverage']],
            summary: 'Calories du jour et moyenne hebdomadaire',
        )));

        return $openApi;
    }
}
