<?php

namespace App\Controller;

use App\Entity\Program;
use App\Entity\User;
use App\Repository\ProgramRepository;
use App\Service\AIProgramGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[Route('/api/programs')]
class ProgramController extends AbstractController
{
    private const DEFAULT_PROGRAM_DURATION = 52;

    #[Route('/generate', name: 'api_programs_generate', methods: ['POST'])]
    public function generate(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        AIProgramGeneratorService $aiService,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données JSON invalides.'], 400);
        }

        $durationWeeks = $this->resolveProgramDuration($data['programDuration'] ?? null);
        $data['programDuration'] = $durationWeeks;

        $violations = $validator->validate($data, new Assert\Collection([
            'goal'            => [new Assert\NotBlank(), new Assert\Choice(choices: ['bulk', 'cut', 'maintain', 'strength', 'calisthenics'])],
            'experienceLevel' => [new Assert\NotBlank(), new Assert\Choice(choices: ['beginner', 'intermediate', 'advanced'])],
            'sessionsPerWeek' => [new Assert\NotBlank(), new Assert\Choice(choices: [0, 1, 2, 3, 4, 5, 6])],
            'programDuration' => new Assert\Optional([new Assert\Type('integer'), new Assert\Range(min: 1, max: 52)]),
            'sessionDuration' => [new Assert\NotBlank(), new Assert\Choice(choices: [45, 60, 90])],
            'equipment'       => [new Assert\NotBlank(), new Assert\Choice(choices: ['full_gym', 'home_gym', 'bodyweight'])],
        ]));

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
            }
            return $this->json(['errors' => $errors], 422);
        }

        try {
            $aiResult = $aiService->generateProgram($data);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => 'La génération du programme a échoué. Veuillez réessayer.', 'detail' => $e->getMessage()], 503);
        }

        $program = new Program();
        $program->setUser($user);
        $program->setTitle($aiResult['title']);
        $program->setDescription($aiResult['description'] ?? null);
        $program->setContent($aiResult);
        $program->setDurationWeeks($durationWeeks);
        $program->setSessionsPerWeek($data['sessionsPerWeek']);

        $em->persist($program);
        $em->flush();

        return $this->json($this->formatProgram($program), 201);
    }

    #[Route('', name: 'api_programs_list', methods: ['GET'])]
    public function list(ProgramRepository $repository): JsonResponse
    {
        /** @var User $user */
        $user     = $this->getUser();
        $programs = $repository->findBy(['user' => $user], ['generatedAt' => 'DESC']);

        return $this->json(array_map(fn(Program $p) => $this->formatProgram($p), $programs));
    }

    #[Route('/{id}', name: 'api_programs_get', methods: ['GET'])]
    public function get(string $id, ProgramRepository $repository): JsonResponse
    {
        /** @var User $user */
        $user    = $this->getUser();
        $program = $repository->find($id);

        if (!$program || $program->getUser() !== $user) {
            return $this->json(['error' => 'Programme introuvable.'], 404);
        }

        return $this->json($this->formatProgram($program));
    }

    private function formatProgram(Program $program): array
    {
        return [
            'id'              => $program->getId(),
            'title'           => $program->getTitle(),
            'description'     => $program->getDescription(),
            'durationWeeks'   => $program->getDurationWeeks(),
            'sessionsPerWeek' => $program->getSessionsPerWeek(),
            'generatedAt'     => $program->getGeneratedAt()->format('c'),
            'content'         => $this->normalizeContent($program->getContent()),
        ];
    }

    /**
     * Normalise les clés du contenu IA (snake_case → camelCase).
     * Permet la rétrocompatibilité avec les programmes générés avant le fix.
     */
    private function normalizeContent(array $content): array
    {
        if (isset($content['sessions']) && is_array($content['sessions'])) {
            $content['sessions'] = $this->normalizeSessions($content['sessions']);
        }

        if (isset($content['weeks']) && is_array($content['weeks'])) {
            foreach ($content['weeks'] as &$week) {
                if (isset($week['week_number']) && !isset($week['weekNumber'])) {
                    $week['weekNumber'] = $week['week_number'];
                    unset($week['week_number']);
                }
            }
            unset($week);

            if (!isset($content['sessions']) || !is_array($content['sessions']) || count($content['sessions']) === 0) {
                $firstWeek = $content['weeks'][0] ?? null;
                $sessions = is_array($firstWeek['sessions'] ?? null) ? $firstWeek['sessions'] : [];

                if ($sessions === []) {
                    foreach ($content['weeks'] as $week) {
                        if (is_array($week['sessions'] ?? null) && count($week['sessions']) > 0) {
                            $sessions = $week['sessions'];
                            break;
                        }
                    }
                }

                if ($sessions !== []) {
                    $content['sessions'] = $this->normalizeSessions($sessions);
                }
            }
        }

        return $content;
    }

    private const WEEK_DAYS = [
        'lundi'    => 1,
        'mardi'    => 2,
        'mercredi' => 3,
        'jeudi'    => 4,
        'vendredi' => 5,
        'samedi'   => 6,
        'dimanche' => 7,
    ];

    private function normalizeSessions(array $sessions): array
    {
        $fallbackDays = array_keys(self::WEEK_DAYS);

        $normalized = array_map(
            function (array $session, int $index) use ($fallbackDays): array {
                if (!isset($session['id']) || !is_string($session['id']) || trim($session['id']) === '') {
                    $session['id'] = sprintf('session-%d', $index + 1);
                }

                $dayOfWeek = is_string($session['dayOfWeek'] ?? null) ? strtolower(trim($session['dayOfWeek'])) : null;
                if ($dayOfWeek === null || !isset(self::WEEK_DAYS[$dayOfWeek])) {
                    $dayOfWeek = $fallbackDays[$index % 7];
                }
                $session['dayOfWeek'] = $dayOfWeek;

                if (!isset($session['day']) || !is_string($session['day']) || trim($session['day']) === '') {
                    $label = ucfirst($dayOfWeek);
                    $focus = is_string($session['focus'] ?? null) ? trim($session['focus']) : '';
                    $session['day'] = $focus !== '' ? "$label - $focus" : $label;
                }

                return $session;
            },
            array_values($sessions),
            array_keys(array_values($sessions)),
        );

        usort(
            $normalized,
            static fn(array $a, array $b): int => (self::WEEK_DAYS[$a['dayOfWeek']] ?? 99) <=> (self::WEEK_DAYS[$b['dayOfWeek']] ?? 99),
        );

        foreach ($normalized as $index => $session) {
            $normalized[$index]['recommended'] = $index === 0;
        }

        return $normalized;
    }

    private function resolveProgramDuration(mixed $rawDuration): int
    {
        if (!is_int($rawDuration)) {
            return self::DEFAULT_PROGRAM_DURATION;
        }

        return match ($rawDuration) {
            26, 39, 52 => $rawDuration,
            default => self::DEFAULT_PROGRAM_DURATION,
        };
    }
}
