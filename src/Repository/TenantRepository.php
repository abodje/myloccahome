<?php

namespace App\Repository;

use App\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tenant>
 */
class TenantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tenant::class);
    }

    /**
     * @return Tenant[] Returns tenants with active contracts
     */
    public function findActiveTenants(): array
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.rentalContracts', 'rc')
            ->andWhere('rc.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('t.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Tenant[] Returns tenants by search term
     */
    public function searchTenants(string $searchTerm): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.firstName LIKE :search OR t.lastName LIKE :search OR t.email LIKE :search')
            ->setParameter('search', '%' . $searchTerm . '%')
            ->orderBy('t.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByEmail(string $email): ?Tenant
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getTenantStats(): array
    {
        return $this->createQueryBuilder('t')
            ->select([
                'COUNT(t.id) as total',
                'AVG(t.monthlyIncome) as averageIncome'
            ])
            ->getQuery()
            ->getSingleResult();
    }
}