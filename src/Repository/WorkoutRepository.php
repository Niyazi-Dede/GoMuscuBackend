<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Workout;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Workout>
 */
class WorkoutRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Workout::class);
    }

    public function findByUserAndMonth(User $user, int $year, int $month): array
    {
        $start = new \DateTimeImmutable("$year-$month-01");
        $end = $start->modify('last day of this month');

        return $this->createQueryBuilder('w')
            ->andWhere('w.user = :user')
            ->andWhere('w.date >= :start')
            ->andWhere('w.date <= :end')
            ->setParameter('user', $user)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('w.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByUserThisWeek(User $user): int
    {
        $monday = new \DateTimeImmutable('monday this week');
        $sunday = new \DateTimeImmutable('sunday this week');

        return (int) $this->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->andWhere('w.user = :user')
            ->andWhere('w.date >= :monday')
            ->andWhere('w.date <= :sunday')
            ->andWhere('w.completed = true')
            ->setParameter('user', $user)
            ->setParameter('monday', $monday)
            ->setParameter('sunday', $sunday)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByUserThisMonth(User $user): int
    {
        $start = new \DateTimeImmutable('first day of this month');
        $end = new \DateTimeImmutable('last day of this month');

        return (int) $this->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->andWhere('w.user = :user')
            ->andWhere('w.date >= :start')
            ->andWhere('w.date <= :end')
            ->andWhere('w.completed = true')
            ->setParameter('user', $user)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
