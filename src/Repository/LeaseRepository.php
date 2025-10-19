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

    /**
     * Trouve les baux expirant bientôt pour un gestionnaire
     */
    public function findExpiringSoonByManager(int $ownerId): array
    {
        $qb = $this->createQueryBuilder('l')
            ->join('l.property', 'p')
            ->join('p.owner', 'o')
            ->where('o.id = :ownerId')
            ->andWhere('l.status = :status')
            ->andWhere('l.endDate BETWEEN :now AND :soon')
            ->setParameter('ownerId', $ownerId)
            ->setParameter('status', 'Actif')
            ->setParameter('now', new \DateTime())
            ->setParameter('soon', new \DateTime('+3 months'));

        return $qb->orderBy('l.endDate', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Trouve les baux expirant bientôt pour un locataire
     */
    public function findExpiringSoonByTenant(int $tenantId): array
    {
        $qb = $this->createQueryBuilder('l')
            ->join('l.tenant', 't')
            ->where('t.id = :tenantId')
            ->andWhere('l.status = :status')
            ->andWhere('l.endDate BETWEEN :now AND :soon')
            ->setParameter('tenantId', $tenantId)
            ->setParameter('status', 'Actif')
            ->setParameter('now', new \DateTime())
            ->setParameter('soon', new \DateTime('+3 months'));

        return $qb->orderBy('l.endDate', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Trouve les baux d'un gestionnaire (via les propriétés qu'il gère)
     */
    public function findByManager(int $ownerId): array
    {
        return $this->createQueryBuilder('l')
            ->join('l.property', 'p')
            ->join('p.owner', 'o')
            ->where('o.id = :ownerId')
            ->setParameter('ownerId', $ownerId)
            ->orderBy('l.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les baux d'un gestionnaire avec un statut spécifique
     */
    public function findByManagerAndStatus(int $ownerId, string $status): array
    {
        return $this->createQueryBuilder('l')
            ->join('l.property', 'p')
            ->join('p.owner', 'o')
            ->where('o.id = :ownerId')
            ->andWhere('l.status = :status')
            ->setParameter('ownerId', $ownerId)
            ->setParameter('status', $status)
            ->orderBy('l.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les baux d'une société
     */
    public function findByCompany(int $companyId): array
    {
        return $this->createQueryBuilder('l')
            ->join('l.property', 'p')
            ->where('p.company = :companyId')
            ->setParameter('companyId', $companyId)
            ->orderBy('l.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les baux d'une société avec un statut spécifique
     */
    public function findByCompanyAndStatus(int $companyId, string $status): array
    {
        return $this->createQueryBuilder('l')
            ->join('l.property', 'p')
            ->where('p.company = :companyId')
            ->andWhere('l.status = :status')
            ->setParameter('companyId', $companyId)
            ->setParameter('status', $status)
            ->orderBy('l.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les baux d'une organisation
     */
    public function findByOrganization(int $organizationId): array
    {
        return $this->createQueryBuilder('l')
            ->join('l.property', 'p')
            ->where('p.organization = :organizationId')
            ->setParameter('organizationId', $organizationId)
            ->orderBy('l.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les baux d'une organisation avec un statut spécifique
     */
    public function findByOrganizationAndStatus(int $organizationId, string $status): array
    {
        return $this->createQueryBuilder('l')
            ->join('l.property', 'p')
            ->where('p.organization = :organizationId')
            ->andWhere('l.status = :status')
            ->setParameter('organizationId', $organizationId)
            ->setParameter('status', $status)
            ->orderBy('l.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
