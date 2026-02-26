<?php

namespace App\Controller;

use App\Entity\Meal;
use App\Entity\User;
use App\Repository\MealRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class NutritionController extends AbstractController
{
    #[Route('/api/meals', name: 'api_meals_list', methods: ['GET'])]
    public function list(Request $request, MealRepository $repository): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $dateParam = $request->query->get('date');
        if ($dateParam) {
            try {
                $date  = new \DateTimeImmutable($dateParam);
                $meals = $repository->findByUserAndDate($user, $date);
            } catch (\Exception) {
                return $this->json(['error' => 'Format de date invalide. Utilisez YYYY-MM-DD.'], 422);
            }
        } else {
            $meals = $repository->findBy(['user' => $user], ['date' => 'DESC', 'id' => 'ASC']);
        }

        return $this->json(array_map(fn(Meal $m) => $this->formatMeal($m), $meals));
    }

    #[Route('/api/meals', name: 'api_meals_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données JSON invalides.'], 400);
        }

        if (empty($data['name'])) {
            return $this->json(['error' => 'Le champ "name" est requis.'], 422);
        }

        if (empty($data['calories']) || !is_numeric($data['calories']) || (int) $data['calories'] <= 0) {
            return $this->json(['error' => 'Le champ "calories" doit être un entier positif.'], 422);
        }

        $meal = new Meal();
        $meal->setUser($user);
        $meal->setName($data['name']);
        $meal->setCalories((int) $data['calories']);

        if (!empty($data['date'])) {
            try {
                $meal->setDate(new \DateTimeImmutable($data['date']));
            } catch (\Exception) {
                return $this->json(['error' => 'Format de date invalide. Utilisez YYYY-MM-DD.'], 422);
            }
        }

        $em->persist($meal);
        $em->flush();

        return $this->json($this->formatMeal($meal), 201);
    }

    #[Route('/api/meals/{id}', name: 'api_meals_delete', methods: ['DELETE'])]
    public function delete(string $id, MealRepository $repository, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $meal = $repository->find($id);

        if (!$meal || $meal->getUser() !== $user) {
            return $this->json(['error' => 'Repas introuvable.'], 404);
        }

        $em->remove($meal);
        $em->flush();

        return $this->json(null, 204);
    }

    #[Route('/api/nutrition/stats', name: 'api_nutrition_stats', methods: ['GET'])]
    public function stats(MealRepository $repository): JsonResponse
    {
        /** @var User $user */
        $user  = $this->getUser();
        $today = new \DateTimeImmutable('today');
        $week  = $today->modify('-6 days');

        $caloriesConsumed = $repository->sumCaloriesByUserAndDate($user, $today);
        $caloriesTarget   = $user->getProfile()?->getDailyCalorieTarget() ?? 0;
        $caloriesWeekAvg  = (int) round($repository->sumCaloriesByUserBetweenDates($user, $week, $today) / 7);

        return $this->json([
            'caloriesConsumed'    => $caloriesConsumed,
            'caloriesTarget'      => $caloriesTarget,
            'remaining'           => max(0, $caloriesTarget - $caloriesConsumed),
            'caloriesWeekAverage' => $caloriesWeekAvg,
        ]);
    }

    private function formatMeal(Meal $meal): array
    {
        return [
            'id'       => $meal->getId(),
            'name'     => $meal->getName(),
            'calories' => $meal->getCalories(),
            'date'     => $meal->getDate()->format('Y-m-d'),
        ];
    }
}
