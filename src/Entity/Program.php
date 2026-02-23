<?php

namespace App\Entity;

use App\Repository\ProgramRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProgramRepository::class)]
class Program
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'json')]
    private array $content = [];

    #[ORM\Column]
    #[Assert\Range(min: 1, max: 52)]
    private ?int $durationWeeks = null;

    #[ORM\Column]
    #[Assert\Range(min: 1, max: 7)]
    private ?int $sessionsPerWeek = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $generatedAt = null;

    #[ORM\OneToMany(mappedBy: 'program', targetEntity: Workout::class)]
    private Collection $workouts;

    public function __construct()
    {
        $this->workouts = new ArrayCollection();
        $this->generatedAt = new \DateTimeImmutable();
    }

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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getContent(): array
    {
        return $this->content;
    }

    public function setContent(array $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getDurationWeeks(): ?int
    {
        return $this->durationWeeks;
    }

    public function setDurationWeeks(int $durationWeeks): static
    {
        $this->durationWeeks = $durationWeeks;
        return $this;
    }

    public function getSessionsPerWeek(): ?int
    {
        return $this->sessionsPerWeek;
    }

    public function setSessionsPerWeek(int $sessionsPerWeek): static
    {
        $this->sessionsPerWeek = $sessionsPerWeek;
        return $this;
    }

    public function getGeneratedAt(): ?\DateTimeImmutable
    {
        return $this->generatedAt;
    }

    public function setGeneratedAt(\DateTimeImmutable $generatedAt): static
    {
        $this->generatedAt = $generatedAt;
        return $this;
    }

    public function getWorkouts(): Collection
    {
        return $this->workouts;
    }
}
