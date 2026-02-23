<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AIProgramGeneratorService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiKey,
        private readonly string $apiUrl,
        private readonly string $model,
    ) {}

    /**
     * Génère un programme d'entraînement via l'API Groq (Llama).
     *
     * @param array{
     *   goal: string,
     *   experienceLevel: string,
     *   sessionsPerWeek: int,
     *   programDuration: int,
     *   sessionDuration: int,
     *   equipment: string
     * } $params
     */
    public function generateProgram(array $params): array
    {
        $prompt = $this->buildPrompt($params);

        $response = $this->httpClient->request('POST', $this->apiUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'model'           => $this->model,
                'messages'        => [
                    [
                        'role'    => 'system',
                        'content' => 'Tu es un coach sportif expert en musculation et fitness. Tu génères des programmes d\'entraînement personnalisés, progressifs et réalistes. Tu réponds UNIQUEMENT en JSON valide, sans texte avant ou après.',
                    ],
                    [
                        'role'    => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature'     => 0.7,
                'response_format' => ['type' => 'json_object'],
            ],
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200) {
            throw new \RuntimeException('Erreur API IA : HTTP ' . $statusCode);
        }

        $body    = $response->toArray();
        $content = $body['choices'][0]['message']['content'] ?? null;

        if (!$content) {
            throw new \RuntimeException('Réponse IA vide ou invalide.');
        }

        $program = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($program['title'], $program['weeks'])) {
            throw new \RuntimeException('Format JSON du programme invalide.');
        }

        return $program;
    }

    private function buildPrompt(array $p): string
    {
        $goalLabels = [
            'bulk'     => 'Prise de masse musculaire',
            'cut'      => 'Perte de poids / Sèche',
            'maintain' => 'Maintien / Tonification',
            'strength' => 'Gain de force',
        ];

        $levelLabels = [
            'beginner'     => 'Débutant (moins de 6 mois de pratique)',
            'intermediate' => 'Intermédiaire (6 mois à 2 ans)',
            'advanced'     => 'Avancé (plus de 2 ans)',
        ];

        $equipmentLabels = [
            'full_gym'   => 'Salle de sport complète (barres, haltères, machines)',
            'home_gym'   => 'Home gym (haltères, barre, banc)',
            'bodyweight' => 'Poids du corps uniquement',
        ];

        $goal      = $goalLabels[$p['goal']] ?? $p['goal'];
        $level     = $levelLabels[$p['experienceLevel']] ?? $p['experienceLevel'];
        $equipment = $equipmentLabels[$p['equipment']] ?? $p['equipment'];

        return <<<PROMPT
Génère un programme d'entraînement complet en JSON avec exactement cette structure :

{
  "title": "Titre du programme",
  "description": "Description courte (2-3 phrases)",
  "weeks": [
    {
      "week_number": 1,
      "sessions": [
        {
          "day": "Lundi",
          "focus": "Groupe musculaire ciblé",
          "exercises": [
            {
              "name": "Nom de l'exercice",
              "sets": 4,
              "reps": "8-10",
              "rest": "90s",
              "notes": "Conseil technique optionnel"
            }
          ]
        }
      ]
    }
  ],
  "tips": ["Conseil 1", "Conseil 2", "Conseil 3"]
}

Paramètres du programme :
- Objectif : {$goal}
- Niveau : {$level}
- Fréquence : {$p['sessionsPerWeek']} séances par semaine
- Durée du programme : {$p['programDuration']} semaines
- Durée d'une séance : {$p['sessionDuration']} minutes
- Équipement disponible : {$equipment}

Règles importantes :
- Génère TOUTES les {$p['programDuration']} semaines avec progression (semaines différentes)
- {$p['sessionsPerWeek']} séances par semaine, réparties sur des jours différents
- Chaque séance doit tenir en {$p['sessionDuration']} minutes
- Adapte les exercices à l'équipement disponible
- Programme progressif : augmente la charge/volume semaine après semaine
- 4 à 6 exercices par séance maximum
- Inclus 3 à 5 tips pratiques
- Réponds UNIQUEMENT avec le JSON, rien d'autre
PROMPT;
    }
}
