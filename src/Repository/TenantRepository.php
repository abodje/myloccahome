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
     * Trouve les locataires avec des contrats actifs
     */
    public function findWithActiveLeases(): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.leases', 'l')
            ->where('l.status = :status')
            ->setParameter('status', 'Actif')
            ->groupBy('t.id')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche par nom ou email
     */
    public function findByNameOrEmail(string $search): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.firstName LIKE :search')
            ->orWhere('t.lastName LIKE :search')
            ->orWhere('t.email LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('t.lastName', 'ASC')
            ->addOrderBy('t.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des locataires
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('t');

        return [
            'total' => $qb->select('COUNT(t.id)')->getQuery()->getSingleScalarResult(),
            'with_active_lease' => count($this->findWithActiveLeases()),
        ];
    }
}
