<?php

namespace App\Repository;

use App\Entity\Meal;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Meal>
 */
class MealRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Meal::class);
    }

    public function findByUserAndDate(User $user, \DateTimeImmutable $date): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.user = :user')
            ->andWhere('m.date = :date')
            ->setParameter('user', $user)
            ->setParameter('date', $date)
            ->orderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function sumCaloriesByUserAndDate(User $user, \DateTimeImmutable $date): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('SUM(m.calories)')
            ->andWhere('m.user = :user')
            ->andWhere('m.date = :date')
            ->setParameter('user', $user)
            ->setParameter('date', $date)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function sumCaloriesByUserBetweenDates(User $user, \DateTimeImmutable $start, \DateTimeImmutable $end): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('SUM(m.calories)')
            ->andWhere('m.user = :user')
            ->andWhere('m.date >= :start')
            ->andWhere('m.date <= :end')
            ->setParameter('user', $user)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
