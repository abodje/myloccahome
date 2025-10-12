<?php

namespace App\Repository;

use App\Entity\Lease;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Lease>
 */
class LeaseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lease::class);
    }

    /**
     * Trouve les contrats qui expirent bientôt
     */
    public function findExpiringSoon(int $days = 60): array
    {
        $date = new \DateTime();
        $date->modify("+{$days} days");

        return $this->createQueryBuilder('l')
            ->where('l.endDate IS NOT NULL')
            ->andWhere('l.endDate <= :date')
            ->andWhere('l.endDate > :now')
            ->andWhere('l.status = :status')
            ->setParameter('date', $date)
            ->setParameter('now', new \DateTime())
            ->setParameter('status', 'Actif')
            ->orderBy('l.endDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les contrats par statut
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.status = :status')
            ->setParameter('status', $status)
            ->orderBy('l.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les contrats d'une propriété
     */
    public function findByProperty(int $propertyId): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.property = :propertyId')
            ->setParameter('propertyId', $propertyId)
            ->orderBy('l.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les contrats d'un locataire
     */
    public function findByTenant(int $tenantId): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.tenant = :tenantId')
            ->setParameter('tenantId', $tenantId)
            ->orderBy('l.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des contrats
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('l');

        return [
            'total' => $qb->select('COUNT(l.id)')->getQuery()->getSingleScalarResult(),
            'active' => $qb->select('COUNT(l.id)')
                ->where('l.status = :status')
                ->setParameter('status', 'Actif')
                ->getQuery()
                ->getSingleScalarResult(),
            'terminated' => $qb->select('COUNT(l.id)')
                ->where('l.status = :status')
                ->setParameter('status', 'Terminé')
                ->getQuery()
                ->getSingleScalarResult(),
            'expiring_soon' => count($this->findExpiringSoon()),
        ];
    }
}
