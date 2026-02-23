<?php

namespace App\Entity;

use App\Repository\WorkoutExerciseRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: WorkoutExerciseRepository::class)]
class WorkoutExercise
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'workoutExercises')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Workout $workout = null;

    #[ORM\ManyToOne(inversedBy: 'workoutExercises')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Exercise $exercise = null;

    #[ORM\Column]
    #[Assert\Positive]
    private ?int $sets = null;

    #[ORM\Column(length: 20)]
    private ?string $reps = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?float $weight = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getWorkout(): ?Workout
    {
        return $this->workout;
    }

    public function setWorkout(?Workout $workout): static
    {
        $this->workout = $workout;
        return $this;
    }

    public function getExercise(): ?Exercise
    {
        return $this->exercise;
    }

    public function setExercise(?Exercise $exercise): static
    {
        $this->exercise = $exercise;
        return $this;
    }

    public function getSets(): ?int
    {
        return $this->sets;
    }

    public function setSets(int $sets): static
    {
        $this->sets = $sets;
        return $this;
    }

    public function getReps(): ?string
    {
        return $this->reps;
    }

    public function setReps(string $reps): static
    {
        $this->reps = $reps;
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
}
