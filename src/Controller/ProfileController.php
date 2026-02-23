<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\CalorieCalculatorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[Route('/api')]
class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'api_profile_get', methods: ['GET'])]
    public function getProfile(): JsonResponse
    {
        /** @var User $user */
        $user    = $this->getUser();
        $profile = $user->getProfile();

        return $this->json([
            'id'                 => $profile->getId(),
            'email'              => $user->getEmail(),
            'fullName'           => $profile->getFullName(),
            'weight'             => $profile->getWeight(),
            'height'             => $profile->getHeight(),
            'age'                => $profile->getAge(),
            'goal'               => $profile->getGoal(),
            'activityLevel'      => $profile->getActivityLevel(),
            'dailyCalorieTarget' => $profile->getDailyCalorieTarget(),
        ]);
    }

    #[Route('/profile', name: 'api_profile_update', methods: ['PUT'])]
    public function updateProfile(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        CalorieCalculatorService $calculator,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données JSON invalides.'], 400);
        }

        $violations = $validator->validate($data, new Assert\Collection([
            'allowExtraFields'  => false,
            'allowMissingFields' => true,
            'fields' => [
                'fullName'      => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 255)]),
                'weight'        => new Assert\Optional([new Assert\Type('numeric'), new Assert\Range(min: 30, max: 300)]),
                'height'        => new Assert\Optional([new Assert\Type('numeric'), new Assert\Range(min: 100, max: 250)]),
                'age'           => new Assert\Optional([new Assert\Type('integer'), new Assert\Range(min: 13, max: 120)]),
                'goal'          => new Assert\Optional([new Assert\Choice(choices: ['bulk', 'cut', 'maintain', 'strength'])]),
                'activityLevel' => new Assert\Optional([new Assert\Type('integer'), new Assert\Range(min: 1, max: 5)]),
            ],
        ]));

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
            }
            return $this->json(['errors' => $errors], 422);
        }

        $profile = $user->getProfile();

        if (isset($data['fullName']))      $profile->setFullName($data['fullName']);
        if (isset($data['weight']))        $profile->setWeight((float) $data['weight']);
        if (isset($data['height']))        $profile->setHeight((float) $data['height']);
        if (isset($data['age']))           $profile->setAge((int) $data['age']);
        if (isset($data['goal']))          $profile->setGoal($data['goal']);
        if (isset($data['activityLevel'])) $profile->setActivityLevel((int) $data['activityLevel']);

        // Recalcul des calories si toutes les données nécessaires sont présentes
        $w = $profile->getWeight();
        $h = $profile->getHeight();
        $a = $profile->getAge();
        $l = $profile->getActivityLevel();
        $g = $profile->getGoal();

        if ($w && $h && $a && $l && $g) {
            $calories = $calculator->calculate($w, $h, $a, $l, $g);
            $profile->setDailyCalorieTarget($calories);
        }

        $em->flush();

        return $this->json([
            'id'                 => $profile->getId(),
            'email'              => $user->getEmail(),
            'fullName'           => $profile->getFullName(),
            'weight'             => $profile->getWeight(),
            'height'             => $profile->getHeight(),
            'age'                => $profile->getAge(),
            'goal'               => $profile->getGoal(),
            'activityLevel'      => $profile->getActivityLevel(),
            'dailyCalorieTarget' => $profile->getDailyCalorieTarget(),
        ]);
    }
}
