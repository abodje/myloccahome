<?php

namespace App\Repository;

use App\Entity\Visit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Visit>
 */
class VisitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Visit::class);
    }

    /**
     * Trouve les visites à venir qui nécessitent un rappel
     */
    public function findUpcomingVisitsNeedingReminder(): array
    {
        $tomorrow = new \DateTime('+1 day');
        $tomorrowStart = (clone $tomorrow)->setTime(0, 0);
        $tomorrowEnd = (clone $tomorrow)->setTime(23, 59, 59);

        return $this->createQueryBuilder('v')
            ->join('v.visitSlot', 'vs')
            ->where('v.status IN (:statuses)')
            ->andWhere('vs.startTime BETWEEN :start AND :end')
            ->andWhere('v.reminderSent = :notSent')
            ->setParameter('statuses', ['pending', 'confirmed'])
            ->setParameter('start', $tomorrowStart)
            ->setParameter('end', $tomorrowEnd)
            ->setParameter('notSent', false)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les visites par statut
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.status = :status')
            ->setParameter('status', $status)
            ->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
