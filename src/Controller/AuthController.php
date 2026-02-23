<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[Route('/api')]
class AuthController extends AbstractController
{
    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        JWTTokenManagerInterface $jwtManager,
        ValidatorInterface $validator,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données JSON invalides.'], 400);
        }

        // Validation des champs requis
        $violations = $validator->validate($data, new Assert\Collection([
            'email'    => [new Assert\NotBlank(), new Assert\Email()],
            'password' => [new Assert\NotBlank(), new Assert\Length(min: 8), new Assert\Regex(
                pattern: '/^(?=.*[A-Z])(?=.*\d).+$/',
                message: 'Le mot de passe doit contenir au moins une majuscule et un chiffre.'
            )],
            'fullName' => new Assert\Optional([new Assert\NotBlank()]),
        ]));

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
            }
            return $this->json(['errors' => $errors], 422);
        }

        // Vérification unicité email
        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return $this->json(['error' => 'Cet email est déjà utilisé.'], 409);
        }

        // Création User
        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword($hasher->hashPassword($user, $data['password']));

        // Création Profile vide lié
        $profile = new Profile();
        $profile->setUser($user);
        $profile->setFullName($data['fullName'] ?? null);
        $user->setProfile($profile);

        $em->persist($user);
        $em->persist($profile);
        $em->flush();

        $token = $jwtManager->create($user);

        return $this->json([
            'token' => $token,
            'user'  => [
                'id'    => $user->getId(),
                'email' => $user->getEmail(),
            ],
        ], 201);
    }
}
