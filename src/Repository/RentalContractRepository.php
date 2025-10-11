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

    /**
     * @return RentalContract[] Returns active contracts
     */
    public function findActiveContracts(): array
    {
        return $this->createQueryBuilder('rc')
            ->andWhere('rc.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('rc.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return RentalContract[] Returns contracts ending soon
     */
    public function findContractsEndingSoon(int $days = 30): array
    {
        $endDate = (new \DateTime())->modify("+{$days} days");
        
        return $this->createQueryBuilder('rc')
            ->andWhere('rc.status = :status')
            ->andWhere('rc.endDate <= :endDate')
            ->andWhere('rc.endDate >= :today')
            ->setParameter('status', 'active')
            ->setParameter('endDate', $endDate)
            ->setParameter('today', new \DateTime())
            ->orderBy('rc.endDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return RentalContract[] Returns contracts by tenant
     */
    public function findByTenant(int $tenantId): array
    {
        return $this->createQueryBuilder('rc')
            ->andWhere('rc.tenant = :tenantId')
            ->setParameter('tenantId', $tenantId)
            ->orderBy('rc.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return RentalContract[] Returns contracts by property
     */
    public function findByProperty(int $propertyId): array
    {
        return $this->createQueryBuilder('rc')
            ->andWhere('rc.property = :propertyId')
            ->setParameter('propertyId', $propertyId)
            ->orderBy('rc.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getContractStats(): array
    {
        return $this->createQueryBuilder('rc')
            ->select([
                'COUNT(rc.id) as total',
                'SUM(CASE WHEN rc.status = \'active\' THEN 1 ELSE 0 END) as active',
                'SUM(CASE WHEN rc.status = \'terminated\' THEN 1 ELSE 0 END) as terminated',
                'SUM(CASE WHEN rc.status = \'pending\' THEN 1 ELSE 0 END) as pending',
                'SUM(rc.monthlyRent) as totalMonthlyRent'
            ])
            ->getQuery()
            ->getSingleResult();
    }
}