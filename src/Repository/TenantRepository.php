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

    public function findActive(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('t.lastName', 'ASC')
            ->addOrderBy('t.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function searchTenants(string $query = null): array
    {
        $qb = $this->createQueryBuilder('t');

        if ($query) {
            $qb->andWhere('t.firstName LIKE :query OR t.lastName LIKE :query OR t.email LIKE :query OR t.phone LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        return $qb->orderBy('t.lastName', 'ASC')
                  ->addOrderBy('t.firstName', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    public function findWithActiveContracts(): array
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.rentalContracts', 'c')
            ->andWhere('c.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('t.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getStatistics(): array
    {
        $total = $this->count([]);
        
        $statusStats = $this->createQueryBuilder('t')
            ->select('t.status, COUNT(t.id) as count')
            ->groupBy('t.status')
            ->getQuery()
            ->getResult();

        $ageStats = $this->createQueryBuilder('t')
            ->select('
                COUNT(CASE WHEN DATEDIFF(CURRENT_DATE(), t.birthDate) / 365 < 30 THEN 1 END) as under30,
                COUNT(CASE WHEN DATEDIFF(CURRENT_DATE(), t.birthDate) / 365 BETWEEN 30 AND 50 THEN 1 END) as between30and50,
                COUNT(CASE WHEN DATEDIFF(CURRENT_DATE(), t.birthDate) / 365 > 50 THEN 1 END) as over50
            ')
            ->getQuery()
            ->getSingleResult();

        return [
            'total' => $total,
            'statusStats' => $statusStats,
            'ageStats' => $ageStats,
        ];
    }
}