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

    /**
     * Trouve les locataires d'un gestionnaire
     */
    public function findByManager(int $ownerId, ?string $search = null, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->join('t.leases', 'l')
            ->join('l.property', 'p')
            ->where('p.owner = :ownerId')
            ->setParameter('ownerId', $ownerId);

        if ($search) {
            $qb->andWhere('t.firstName LIKE :search OR t.lastName LIKE :search OR t.email LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($status === 'actif') {
            $qb->andWhere('l.status = :status')
               ->setParameter('status', 'Actif');
        } elseif ($status === 'inactif') {
            $qb->andWhere('l.status != :status')
               ->setParameter('status', 'Actif');
        }

        return $qb->orderBy('t.lastName', 'ASC')
                  ->addOrderBy('t.firstName', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Trouve les locataires d'une société
     */
    public function findByCompany($company, ?string $search = null, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.company = :company')
            ->setParameter('company', $company);

        if ($search) {
            $qb->andWhere('t.firstName LIKE :search OR t.lastName LIKE :search OR t.email LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($status === 'actif') {
            $qb->leftJoin('t.leases', 'l')
               ->andWhere('l.status = :status')
               ->setParameter('status', 'Actif');
        } elseif ($status === 'inactif') {
            $qb->leftJoin('t.leases', 'l')
               ->andWhere('l.status != :status OR l.id IS NULL')
               ->setParameter('status', 'Actif');
        }

        return $qb->orderBy('t.lastName', 'ASC')
                  ->addOrderBy('t.firstName', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Trouve les locataires d'une organisation
     */
    public function findByOrganization($organization, ?string $search = null, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.organization = :organization')
            ->setParameter('organization', $organization);

        if ($search) {
            $qb->andWhere('t.firstName LIKE :search OR t.lastName LIKE :search OR t.email LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($status === 'actif') {
            $qb->leftJoin('t.leases', 'l')
               ->andWhere('l.status = :status')
               ->setParameter('status', 'Actif');
        } elseif ($status === 'inactif') {
            $qb->leftJoin('t.leases', 'l')
               ->andWhere('l.status != :status OR l.id IS NULL')
               ->setParameter('status', 'Actif');
        }

        return $qb->orderBy('t.lastName', 'ASC')
                  ->addOrderBy('t.firstName', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Vérifie si un locataire a un contrat actif
     */
    public function hasActiveLease(int $tenantId): bool
    {
        $result = $this->createQueryBuilder('t')
            ->select('COUNT(l.id)')
            ->leftJoin('t.leases', 'l')
            ->where('t.id = :tenantId')
            ->andWhere('l.status = :status')
            ->setParameter('tenantId', $tenantId)
            ->setParameter('status', 'Actif')
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }
}
