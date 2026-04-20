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

    public function countByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->andWhere('w.user = :user')
            ->andWhere('w.completed = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function currentStreakByUser(User $user): int
    {
        $dates = $this->createQueryBuilder('w')
            ->select('DISTINCT w.date')
            ->andWhere('w.user = :user')
            ->andWhere('w.completed = true')
            ->setParameter('user', $user)
            ->orderBy('w.date', 'DESC')
            ->getQuery()
            ->getSingleColumnResult();

        if (empty($dates)) {
            return 0;
        }

        $today = new \DateTimeImmutable('today');
        $yesterday = $today->modify('-1 day');
        $cursor = null;
        $streak = 0;

        foreach ($dates as $raw) {
            $date = $raw instanceof \DateTimeInterface
                ? \DateTimeImmutable::createFromInterface($raw)->setTime(0, 0)
                : new \DateTimeImmutable((string) $raw);
            $date = $date->setTime(0, 0);

            if ($cursor === null) {
                if ($date != $today && $date != $yesterday) {
                    return 0;
                }
                $cursor = $date;
                $streak = 1;
                continue;
            }

            $expected = $cursor->modify('-1 day');
            if ($date == $expected) {
                $cursor = $date;
                $streak++;
            } elseif ($date < $expected) {
                break;
            }
        }

        return $streak;
    }
}
