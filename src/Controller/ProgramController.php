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

        $violations = $validator->validate($data, new Assert\Collection([
            'goal'            => [new Assert\NotBlank(), new Assert\Choice(choices: ['bulk', 'cut', 'maintain', 'strength'])],
            'experienceLevel' => [new Assert\NotBlank(), new Assert\Choice(choices: ['beginner', 'intermediate', 'advanced'])],
            'sessionsPerWeek' => [new Assert\NotBlank(), new Assert\Choice(choices: [0, 3, 4, 5, 6])],
            'programDuration' => [new Assert\NotBlank(), new Assert\Choice(choices: [4, 8, 12])],
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
        $program->setDurationWeeks($data['programDuration']);
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
        if (isset($content['weeks']) && is_array($content['weeks'])) {
            foreach ($content['weeks'] as &$week) {
                if (isset($week['week_number']) && !isset($week['weekNumber'])) {
                    $week['weekNumber'] = $week['week_number'];
                    unset($week['week_number']);
                }
            }
            unset($week);
        }

        return $content;
    }
}
