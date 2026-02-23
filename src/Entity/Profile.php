<?php

namespace App\Entity;

use App\Repository\ProfileRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProfileRepository::class)]
class Profile
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\OneToOne(inversedBy: 'profile')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fullName = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 30, max: 300)]
    private ?float $weight = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 100, max: 250)]
    private ?float $height = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 13, max: 120)]
    private ?int $age = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Choice(choices: ['bulk', 'cut', 'maintain', 'strength'])]
    private ?string $goal = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1, max: 5)]
    private ?int $activityLevel = null;

    #[ORM\Column(nullable: true)]
    private ?int $dailyCalorieTarget = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): static
    {
        $this->fullName = $fullName;
        return $this;
    }

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function setWeight(?float $weight): static
    {
        $this->weight = $weight;
        return $this;
    }

    public function getHeight(): ?float
    {
        return $this->height;
    }

    public function setHeight(?float $height): static
    {
        $this->height = $height;
        return $this;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(?int $age): static
    {
        $this->age = $age;
        return $this;
    }

    public function getGoal(): ?string
    {
        return $this->goal;
    }

    public function setGoal(?string $goal): static
    {
        $this->goal = $goal;
        return $this;
    }

    public function getActivityLevel(): ?int
    {
        return $this->activityLevel;
    }

    public function setActivityLevel(?int $activityLevel): static
    {
        $this->activityLevel = $activityLevel;
        return $this;
    }

    public function getDailyCalorieTarget(): ?int
    {
        return $this->dailyCalorieTarget;
    }

    public function setDailyCalorieTarget(?int $dailyCalorieTarget): static
    {
        $this->dailyCalorieTarget = $dailyCalorieTarget;
        return $this;
    }
}
