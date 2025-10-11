<?php

namespace App\Repository;

use App\Entity\RentalContract;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RentalContract>
 */
class RentalContractRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RentalContract::class);
    }

    public function findActive(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('c.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findExpiringContracts(int $days = 30): array
    {
        $futureDate = new \DateTime("+{$days} days");
        
        return $this->createQueryBuilder('c')
            ->andWhere('c.status = :status')
            ->andWhere('c.endDate <= :futureDate')
            ->andWhere('c.endDate >= CURRENT_DATE()')
            ->setParameter('status', 'active')
            ->setParameter('futureDate', $futureDate)
            ->orderBy('c.endDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByProperty(int $propertyId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.property = :propertyId')
            ->setParameter('propertyId', $propertyId)
            ->orderBy('c.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByTenant(int $tenantId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.tenant = :tenantId')
            ->setParameter('tenantId', $tenantId)
            ->orderBy('c.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getStatistics(): array
    {
        $total = $this->count([]);
        
        $statusStats = $this->createQueryBuilder('c')
            ->select('c.status, COUNT(c.id) as count')
            ->groupBy('c.status')
            ->getQuery()
            ->getResult();

        $monthlyRevenue = $this->createQueryBuilder('c')
            ->select('SUM(c.rentAmount + c.charges) as revenue')
            ->andWhere('c.status = :status')
            ->setParameter('status', 'active')
            ->getQuery()
            ->getSingleScalarResult();

        $averageDuration = $this->createQueryBuilder('c')
            ->select('AVG(DATEDIFF(c.endDate, c.startDate)) as avgDuration')
            ->andWhere('c.endDate IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => $total,
            'statusStats' => $statusStats,
            'monthlyRevenue' => $monthlyRevenue ?? 0,
            'averageDuration' => round($averageDuration ?? 0),
        ];
    }
}