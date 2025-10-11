<?php

namespace App\Repository;

use App\Entity\Maintenance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Maintenance>
 */
class MaintenanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Maintenance::class);
    }

    public function findPending(): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('m.priority', 'DESC')
            ->addOrderBy('m.reportedDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findUrgent(): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.priority = :priority')
            ->andWhere('m.status != :completedStatus')
            ->setParameter('priority', 'urgent')
            ->setParameter('completedStatus', 'completed')
            ->orderBy('m.reportedDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOverdue(): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.scheduledDate < CURRENT_DATE()')
            ->andWhere('m.status NOT IN (:statuses)')
            ->setParameter('statuses', ['completed', 'cancelled'])
            ->orderBy('m.scheduledDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByProperty(int $propertyId): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.property = :propertyId')
            ->setParameter('propertyId', $propertyId)
            ->orderBy('m.reportedDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findScheduledForDate(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.scheduledDate = :date')
            ->andWhere('m.status = :status')
            ->setParameter('date', $date)
            ->setParameter('status', 'scheduled')
            ->orderBy('m.priority', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getMaintenanceCostsByProperty(int $propertyId): float
    {
        $result = $this->createQueryBuilder('m')
            ->select('SUM(m.actualCost) as totalCost')
            ->andWhere('m.property = :propertyId')
            ->andWhere('m.status = :status')
            ->setParameter('propertyId', $propertyId)
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();

        return (float)($result ?? 0);
    }

    public function getMaintenanceCostsByPeriod(int $year, int $month = null): float
    {
        $qb = $this->createQueryBuilder('m')
            ->select('SUM(m.actualCost) as totalCost')
            ->andWhere('YEAR(m.completedDate) = :year')
            ->andWhere('m.status = :status')
            ->setParameter('year', $year)
            ->setParameter('status', 'completed');

        if ($month !== null) {
            $qb->andWhere('MONTH(m.completedDate) = :month')
               ->setParameter('month', $month);
        }

        $result = $qb->getQuery()->getSingleScalarResult();

        return (float)($result ?? 0);
    }

    public function getStatistics(): array
    {
        $total = $this->count([]);
        
        $statusStats = $this->createQueryBuilder('m')
            ->select('m.status, COUNT(m.id) as count')
            ->groupBy('m.status')
            ->getQuery()
            ->getResult();

        $priorityStats = $this->createQueryBuilder('m')
            ->select('m.priority, COUNT(m.id) as count')
            ->groupBy('m.priority')
            ->getQuery()
            ->getResult();

        $typeStats = $this->createQueryBuilder('m')
            ->select('m.type, COUNT(m.id) as count')
            ->groupBy('m.type')
            ->getQuery()
            ->getResult();

        $totalCosts = $this->createQueryBuilder('m')
            ->select('SUM(m.actualCost) as totalCosts')
            ->andWhere('m.status = :status')
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();

        $averageResolutionTime = $this->createQueryBuilder('m')
            ->select('AVG(DATEDIFF(m.completedDate, m.reportedDate)) as avgTime')
            ->andWhere('m.status = :status')
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();

        $urgentCount = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.priority = :priority')
            ->andWhere('m.status != :status')
            ->setParameter('priority', 'urgent')
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => $total,
            'statusStats' => $statusStats,
            'priorityStats' => $priorityStats,
            'typeStats' => $typeStats,
            'totalCosts' => $totalCosts ?? 0,
            'averageResolutionTime' => round($averageResolutionTime ?? 0),
            'urgentCount' => $urgentCount ?? 0,
        ];
    }
}