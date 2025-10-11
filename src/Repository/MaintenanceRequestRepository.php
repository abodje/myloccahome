<?php

namespace App\Repository;

use App\Entity\MaintenanceRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MaintenanceRequest>
 */
class MaintenanceRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MaintenanceRequest::class);
    }

    /**
     * @return MaintenanceRequest[] Returns pending maintenance requests
     */
    public function findPendingRequests(): array
    {
        return $this->createQueryBuilder('mr')
            ->andWhere('mr.status IN (:statuses)')
            ->setParameter('statuses', ['pending', 'in_progress'])
            ->orderBy('mr.priority', 'DESC')
            ->addOrderBy('mr.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return MaintenanceRequest[] Returns urgent maintenance requests
     */
    public function findUrgentRequests(): array
    {
        return $this->createQueryBuilder('mr')
            ->andWhere('mr.priority = :priority')
            ->andWhere('mr.status != :status')
            ->setParameter('priority', 'urgent')
            ->setParameter('status', 'completed')
            ->orderBy('mr.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return MaintenanceRequest[] Returns requests by property
     */
    public function findByProperty(int $propertyId): array
    {
        return $this->createQueryBuilder('mr')
            ->andWhere('mr.property = :propertyId')
            ->setParameter('propertyId', $propertyId)
            ->orderBy('mr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return MaintenanceRequest[] Returns requests by category
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('mr')
            ->andWhere('mr.category = :category')
            ->setParameter('category', $category)
            ->orderBy('mr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return MaintenanceRequest[] Returns requests assigned to someone
     */
    public function findByAssignee(string $assignedTo): array
    {
        return $this->createQueryBuilder('mr')
            ->andWhere('mr.assignedTo = :assignedTo')
            ->setParameter('assignedTo', $assignedTo)
            ->orderBy('mr.scheduledDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getMaintenanceStats(): array
    {
        return $this->createQueryBuilder('mr')
            ->select([
                'COUNT(mr.id) as total',
                'SUM(CASE WHEN mr.status = \'pending\' THEN 1 ELSE 0 END) as pending',
                'SUM(CASE WHEN mr.status = \'in_progress\' THEN 1 ELSE 0 END) as inProgress',
                'SUM(CASE WHEN mr.status = \'completed\' THEN 1 ELSE 0 END) as completed',
                'SUM(CASE WHEN mr.priority = \'urgent\' AND mr.status != \'completed\' THEN 1 ELSE 0 END) as urgent',
                'SUM(mr.actualCost) as totalCost'
            ])
            ->getQuery()
            ->getSingleResult();
    }

    public function getMonthlyCosts(\DateTimeInterface $month): float
    {
        $startOfMonth = (clone $month)->modify('first day of this month')->setTime(0, 0, 0);
        $endOfMonth = (clone $month)->modify('last day of this month')->setTime(23, 59, 59);

        $result = $this->createQueryBuilder('mr')
            ->select('SUM(mr.actualCost) as cost')
            ->andWhere('mr.completedDate >= :startDate')
            ->andWhere('mr.completedDate <= :endDate')
            ->setParameter('startDate', $startOfMonth)
            ->setParameter('endDate', $endOfMonth)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }
}