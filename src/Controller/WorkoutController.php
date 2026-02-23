<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Workout;
use App\Entity\WorkoutExercise;
use App\Repository\ExerciseRepository;
use App\Repository\ProgramRepository;
use App\Repository\WorkoutRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/workouts')]
class WorkoutController extends AbstractController
{
    #[Route('/stats', name: 'api_workouts_stats', methods: ['GET'])]
    public function stats(WorkoutRepository $repository): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json([
            'thisWeek'  => $repository->countByUserThisWeek($user),
            'thisMonth' => $repository->countByUserThisMonth($user),
        ]);
    }

    #[Route('', name: 'api_workouts_list', methods: ['GET'])]
    public function list(WorkoutRepository $repository): JsonResponse
    {
        /** @var User $user */
        $user     = $this->getUser();
        $workouts = $repository->findBy(['user' => $user], ['date' => 'DESC']);

        return $this->json(array_map(fn(Workout $w) => $this->formatWorkout($w), $workouts));
    }

    #[Route('/{id}', name: 'api_workouts_get', methods: ['GET'])]
    public function get(string $id, WorkoutRepository $repository): JsonResponse
    {
        /** @var User $user */
        $user    = $this->getUser();
        $workout = $repository->find($id);

        if (!$workout || $workout->getUser() !== $user) {
            return $this->json(['error' => 'Séance introuvable.'], 404);
        }

        return $this->json($this->formatWorkout($workout));
    }

    #[Route('', name: 'api_workouts_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        ExerciseRepository $exerciseRepository,
        ProgramRepository $programRepository,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données JSON invalides.'], 400);
        }

        $workout = new Workout();
        $workout->setUser($user);

        if (!empty($data['date'])) {
            try {
                $workout->setDate(new \DateTimeImmutable($data['date']));
            } catch (\Exception) {
                return $this->json(['error' => 'Format de date invalide. Utilisez YYYY-MM-DD.'], 422);
            }
        }

        if (!empty($data['programId'])) {
            $program = $programRepository->find($data['programId']);
            if (!$program || $program->getUser() !== $user) {
                return $this->json(['error' => 'Programme introuvable.'], 404);
            }
            $workout->setProgram($program);
        }

        if (isset($data['notes'])) {
            $workout->setNotes($data['notes']);
        }

        if (isset($data['completed'])) {
            $workout->setCompleted((bool) $data['completed']);
        }

        if (!empty($data['exercises']) && is_array($data['exercises'])) {
            foreach ($data['exercises'] as $index => $exData) {
                if (empty($exData['exerciseId'])) {
                    return $this->json(['error' => "exercises[$index]: exerciseId est requis."], 422);
                }

                $exercise = $exerciseRepository->find($exData['exerciseId']);
                if (!$exercise) {
                    return $this->json(['error' => "exercises[$index]: exercice introuvable."], 404);
                }

                $we = new WorkoutExercise();
                $we->setExercise($exercise);
                $we->setSets((int) ($exData['sets'] ?? 3));
                $we->setReps((string) ($exData['reps'] ?? '10'));
                $we->setWeight(isset($exData['weight']) ? (float) $exData['weight'] : null);

                $workout->addWorkoutExercise($we);
            }
        }

        $em->persist($workout);
        $em->flush();

        return $this->json($this->formatWorkout($workout), 201);
    }

    private function formatWorkout(Workout $workout): array
    {
        return [
            'id'        => $workout->getId(),
            'date'      => $workout->getDate()->format('Y-m-d'),
            'completed' => $workout->isCompleted(),
            'notes'     => $workout->getNotes(),
            'programId' => $workout->getProgram()?->getId(),
            'exercises' => array_map(fn(WorkoutExercise $we) => [
                'id'           => $we->getId(),
                'exerciseId'   => $we->getExercise()->getId(),
                'exerciseName' => $we->getExercise()->getName(),
                'muscleGroup'  => $we->getExercise()->getMuscleGroup(),
                'sets'         => $we->getSets(),
                'reps'         => $we->getReps(),
                'weight'       => $we->getWeight(),
            ], $workout->getWorkoutExercises()->toArray()),
        ];
    }
}
