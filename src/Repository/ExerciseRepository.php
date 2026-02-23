<?php

namespace App\Repository;

use App\Entity\Exercise;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Exercise>
 */
class ExerciseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Exercise::class);
    }

    public function findByMuscleGroup(string $muscleGroup): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.muscleGroup = :muscleGroup')
            ->setParameter('muscleGroup', $muscleGroup)
            ->orderBy('e.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
