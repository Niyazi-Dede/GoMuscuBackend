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
     * Generate a long-term training program through the AI API.
     *
     * @param array{
     *   goal: string,
     *   experienceLevel: string,
     *   sessionsPerWeek: int,
     *   programDuration?: int,
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
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "Tu es un coach sportif professionnel (certifie CSCS / NSCA) avec 10 ans d'experience en preparation physique, musculation, fitness et calisthenie. Tu construis des programmes rigoureux et equilibres, avec un volume d'entrainement realiste adapte au niveau et a la duree de seance. Tu places les seances sur des jours precis de la semaine en respectant la recuperation, en evitant les doublons sur les memes groupes musculaires deux jours d'affilee, et en couvrant l'integralite du corps sur la semaine. Tu NE proposes JAMAIS une seance creuse : le nombre d'exercices doit etre coherent avec la duree de seance (regle de base : environ 1 exercice toutes les 10 a 12 minutes apres l'echauffement). Tu reponds uniquement en JSON valide, sans texte avant ou apres.",
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.4,
                'response_format' => ['type' => 'json_object'],
            ],
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200) {
            throw new \RuntimeException('Erreur API IA : HTTP ' . $statusCode);
        }

        $body = $response->toArray();
        $content = $body['choices'][0]['message']['content'] ?? null;

        if (!$content) {
            throw new \RuntimeException('Reponse IA vide ou invalide.');
        }

        $program = json_decode($content, true);

        if (
            json_last_error() !== JSON_ERROR_NONE
            || !isset($program['title'], $program['sessions'])
            || !is_array($program['sessions'])
            || count($program['sessions']) === 0
        ) {
            throw new \RuntimeException('Format JSON du programme invalide.');
        }

        return $program;
    }

    private function buildPrompt(array $params): string
    {
        $goalLabels = [
            'bulk' => 'Prise de masse musculaire',
            'cut' => 'Perte de poids / seche',
            'maintain' => 'Maintien / tonification',
            'strength' => 'Gain de force',
            'calisthenics' => 'Calisthenie / controle du corps',
        ];

        $levelLabels = [
            'beginner' => 'Debutant (moins de 6 mois de pratique)',
            'intermediate' => 'Intermediaire (6 mois a 2 ans)',
            'advanced' => 'Avance (plus de 2 ans)',
        ];

        $equipmentLabels = [
            'full_gym' => 'Salle de sport complete (barres, halteres, machines)',
            'home_gym' => 'Home gym (halteres, barre, banc)',
            'bodyweight' => 'Poids du corps uniquement',
        ];

        $goal = $goalLabels[$params['goal']] ?? $params['goal'];
        $level = $levelLabels[$params['experienceLevel']] ?? $params['experienceLevel'];
        $equipment = $equipmentLabels[$params['equipment']] ?? $params['equipment'];
        $durationWeeks = (int) ($params['programDuration'] ?? 52);
        $sessionsPerWeek = (int) $params['sessionsPerWeek'];

        $sessionsLabel = $sessionsPerWeek === 0
            ? "Libre (choisis la frequence optimale selon l'objectif et le niveau, entre 3 et 5 seances)"
            : $sessionsPerWeek . ' seances par semaine';

        $programHorizon = match ($durationWeeks) {
            26 => 'environ 6 mois avant reevaluation',
            39 => 'environ 9 mois avant reevaluation',
            default => "environ 1 an, ou jusqu'a changement necessaire",
        };

        $splitSuggestion = $this->splitSuggestion($sessionsPerWeek, $params['goal']);
        $restPattern = $this->restPattern($sessionsPerWeek);
        $volumeRule = $this->volumeRule((int) $params['sessionDuration'], $params['experienceLevel']);

        $sessionsRule = $sessionsPerWeek === 0
            ? "- Choisis toi-meme la frequence optimale (3 a 5 seances), puis genere EXACTEMENT ce nombre de seances distinctes\n- Indique la frequence choisie dans selectionGuidance"
            : "- Genere EXACTEMENT {$sessionsPerWeek} seances distinctes, une par jour d'entrainement de la semaine\n- Chaque seance correspond a un jour precis de la semaine, pas a un template qu'on alterne";

        return <<<PROMPT
Genere un programme d'entrainement long terme en JSON avec exactement cette structure :

{
  "title": "Titre du programme",
  "description": "Description courte (2-3 phrases)",
  "selectionGuidance": "Explique en 1 phrase l'ordre des seances dans la semaine",
  "changeGuidance": "Explique en 1 phrase quand garder le programme et quand le modifier",
  "sessions": [
    {
      "id": "session-1",
      "dayOfWeek": "lundi",
      "day": "Lundi - Push",
      "focus": "Groupes musculaires cibles de la seance",
      "recommended": true,
      "recommendationReason": "Pourquoi commencer par cette seance",
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
  ],
  "tips": ["Conseil 1", "Conseil 2", "Conseil 3"]
}

Parametres du programme :
- Objectif : {$goal}
- Niveau : {$level}
- Frequence : {$sessionsLabel}
- Horizon du programme : {$programHorizon}
- Duree d'une seance : {$params['sessionDuration']} minutes
- Equipement disponible : {$equipment}

Split recommande pour cette frequence :
{$splitSuggestion}

Repartition des jours d'entrainement recommandee :
{$restPattern}

Regles importantes :
{$sessionsRule}
- Le split hebdomadaire DOIT couvrir l'ensemble des groupes musculaires majeurs sur la semaine (pectoraux, dos, epaules, bras, jambes, fessiers, abdos), de maniere equilibree et coherente
- Chaque groupe musculaire doit etre entraine au moins une fois par semaine, sans surcharger ni oublier un groupe
- Place les seances sur des jours precis de la semaine en respectant la recuperation : pas deux seances intenses sur le meme groupe musculaire deux jours d'affilee
- dayOfWeek est OBLIGATOIRE pour chaque seance, en minuscules parmi : "lundi", "mardi", "mercredi", "jeudi", "vendredi", "samedi", "dimanche"
- Tous les dayOfWeek doivent etre distincts (pas deux seances le meme jour)
- Ne liste QUE les jours d'entrainement dans sessions, les jours de repos sont simplement les jours absents
- day (pour l'affichage) reprend le jour de la semaine + le focus, par exemple "Lundi - Push", "Mercredi - Pull", "Vendredi - Legs"
- focus doit lister les groupes musculaires travailles (ex : "Pectoraux, epaules, triceps")
- La seance du premier jour de la semaine (le lundi si present, sinon le plus tot) doit avoir "recommended": true, les autres "recommended": false
- selectionGuidance doit resumer la repartition hebdomadaire (ex : "Lundi Push, Mardi Pull, Jeudi Legs, Samedi Full-body")
- changeGuidance doit indiquer qu'on garde cette base {$programHorizon} et qu'on la change seulement si stagnation durable, douleur, changement d'objectif ou de materiel
- Ne genere aucune semaine et n'utilise pas la cle weeks
- Le programme doit etre stable et repetable sur plusieurs mois, pas different chaque semaine
- Chaque seance doit tenir en {$params['sessionDuration']} minutes
- Adapte les exercices a l'equipement disponible
- Pour l'objectif calisthenie, favorise le controle du corps, la force relative, les exercices au poids du corps et les progressions techniques pertinentes
{$volumeRule}
- Pour chaque seance : 1 a 2 exercices poly-articulaires lourds en debut de seance, puis exercices d'isolation complementaires, et si pertinent un finisher ou du core
- Series par exercice : 3 a 5 series de travail (hors echauffement)
- Temps de repos coherent avec la charge : 2 a 3 minutes pour les gros poly-articulaires lourds, 60 a 90 secondes pour l'isolation
- Inclus 3 a 5 tips pratiques
- Reponds uniquement avec le JSON, rien d'autre
PROMPT;
    }

    private function volumeRule(int $sessionDuration, string $level): string
    {
        // 1 exercice ~ 10-12 min (series + repos + transitions) apres l'echauffement
        [$min, $max] = match ($sessionDuration) {
            45 => [4, 5],
            60 => [5, 6],
            90 => [7, 8],
            default => [5, 6],
        };

        if ($level === 'beginner') {
            $min = max(4, $min - 1);
        }

        return "- OBLIGATOIRE : chaque seance contient entre {$min} et {$max} exercices (ni moins, ni plus). Pour une seance de {$sessionDuration} minutes, moins de {$min} exercices est considere comme une seance bacclee et est INTERDIT";
    }

    private function restPattern(int $sessionsPerWeek): string
    {
        return match ($sessionsPerWeek) {
            1 => "- 1 seance : samedi (ou lundi), 6 jours de repos",
            2 => "- 2 seances : lundi + jeudi (2 a 3 jours entre chaque seance)",
            3 => "- 3 seances : lundi, mercredi, vendredi (1 jour de repos entre chaque)",
            4 => "- 4 seances : lundi, mardi, jeudi, vendredi (repos mercredi, samedi, dimanche)",
            5 => "- 5 seances : lundi, mardi, mercredi, vendredi, samedi (repos jeudi et dimanche)",
            6 => "- 6 seances : lundi a samedi, repos le dimanche (ou repos un jour en milieu de semaine selon le split)",
            default => "- Repartis les seances avec au moins 1 jour de repos entre deux seances sollicitant les memes groupes musculaires",
        };
    }

    private function splitSuggestion(int $sessionsPerWeek, string $goal): string
    {
        if ($goal === 'calisthenics') {
            return match ($sessionsPerWeek) {
                1 => "- 1 seance : full-body calisthenie (tirer, pousser, jambes, gainage)",
                2 => "- 2 seances : Upper (tractions, dips, pompes) / Lower + core (squats, fentes, L-sit)",
                3 => "- 3 seances : Push (pompes, dips, handstand) / Pull (tractions, rows) / Legs + core",
                4 => "- 4 seances : Push / Pull / Legs / Skill + core (ou Upper/Lower x2)",
                5 => "- 5 seances : Push / Pull / Legs / Upper / Lower",
                6 => "- 6 seances : Push / Pull / Legs / Push / Pull / Legs",
                default => "- Choisis un split equilibre (ex : Push / Pull / Legs) couvrant tout le corps",
            };
        }

        return match ($sessionsPerWeek) {
            1 => "- 1 seance : full-body complete (pecs, dos, jambes, epaules, bras, core)",
            2 => "- 2 seances : Upper (pecs, dos, epaules, bras) / Lower (quadris, ischios, fessiers, mollets, abdos)",
            3 => "- 3 seances : Push (pecs, epaules, triceps) / Pull (dos, biceps) / Legs (jambes + abdos) - ou 3 full-body pour debutants",
            4 => "- 4 seances : Upper / Lower / Upper / Lower (variante : horizontal/vertical)",
            5 => "- 5 seances : Push / Pull / Legs / Upper / Lower (ou PPL + 2 full-body)",
            6 => "- 6 seances : Push / Pull / Legs / Push / Pull / Legs (PPL x2)",
            default => "- Choisis un split equilibre (PPL, Upper/Lower, full-body) couvrant l'ensemble du corps sur la semaine",
        };
    }
}
