<?php

namespace App\Repository;

use App\Entity\Property;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Property>
 */
class PropertyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Property::class);
    }

    /**
     * Trouve les propriétés avec filtres
     */
    public function findWithFilters(?string $search = null, ?string $status = null, ?string $type = null): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($search) {
            $qb->andWhere('p.address LIKE :search OR p.city LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($status) {
            $qb->andWhere('p.status = :status')
               ->setParameter('status', $status);
        }

        if ($type) {
            $qb->andWhere('p.propertyType = :type')
               ->setParameter('type', $type);
        }

        return $qb->orderBy('p.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }


    /**
     * Trouve toutes les propriétés avec leur statut de location actuel
     */
    public function findAllWithCurrentStatus(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.leases', 'l')
            ->addSelect('l')
            ->orderBy('p.city', 'ASC')
            ->addOrderBy('p.address', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les propriétés disponibles (sans contrat actif)
     */
    public function findAvailable(): array
    {
        $qb = $this->createQueryBuilder('p');

        return $qb
            ->leftJoin('p.leases', 'l', 'WITH',
                $qb->expr()->andX(
                    $qb->expr()->lte('l.startDate', ':now'),
                    $qb->expr()->orX(
                        $qb->expr()->isNull('l.endDate'),
                        $qb->expr()->gte('l.endDate', ':now')
                    )
                )
            )
            ->where($qb->expr()->isNull('l.id'))
            ->setParameter('now', new \DateTime())
            ->orderBy('p.city', 'ASC')
            ->addOrderBy('p.address', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les propriétés occupées
     */
    public function findOccupied(): array
    {
        $qb = $this->createQueryBuilder('p');

        return $qb
            ->innerJoin('p.leases', 'l', 'WITH',
                $qb->expr()->andX(
                    $qb->expr()->lte('l.startDate', ':now'),
                    $qb->expr()->orX(
                        $qb->expr()->isNull('l.endDate'),
                        $qb->expr()->gte('l.endDate', ':now')
                    )
                )
            )
            ->setParameter('now', new \DateTime())
            ->orderBy('p.city', 'ASC')
            ->addOrderBy('p.address', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche par critères
     */
    public function findByCriteria(array $criteria): array
    {
        $qb = $this->createQueryBuilder('p');

        if (!empty($criteria['city'])) {
            $qb->andWhere($qb->expr()->like('p.city', ':city'))
               ->setParameter('city', '%' . $criteria['city'] . '%');
        }

        if (!empty($criteria['propertyType'])) {
            $qb->andWhere('p.propertyType = :propertyType')
               ->setParameter('propertyType', $criteria['propertyType']);
        }

        if (!empty($criteria['minRent'])) {
            $qb->andWhere('p.monthlyRent >= :minRent')
               ->setParameter('minRent', $criteria['minRent']);
        }

        if (!empty($criteria['maxRent'])) {
            $qb->andWhere('p.monthlyRent <= :maxRent')
               ->setParameter('maxRent', $criteria['maxRent']);
        }

        if (!empty($criteria['status'])) {
            $qb->andWhere('p.status = :status')
               ->setParameter('status', $criteria['status']);
        }

        return $qb->orderBy('p.city', 'ASC')
                  ->addOrderBy('p.address', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Calcule le revenu total mensuel de toutes les propriétés
     */
    public function getTotalMonthlyRevenue(): float
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.monthlyRent) as total')
            ->innerJoin('p.leases', 'l')
            ->where('l.startDate <= :now')
            ->andWhere('l.endDate IS NULL OR l.endDate >= :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Statistiques des propriétés
     */
    public function getStatistics(): array
    {
        $total = $this->count([]);
        $available = count($this->findAvailable());
        $occupied = count($this->findOccupied());

        return [
            'total' => $total,
            'available' => $available,
            'occupied' => $occupied,
            'occupancy_rate' => $total > 0 ? round(($occupied / $total) * 100, 2) : 0
        ];
    }

    /**
     * Trouve les propriétés louées par un locataire avec filtres
     */
    public function findByTenantWithFilters(int $tenantId, ?string $search = null, ?string $status = null, ?string $type = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->join('p.leases', 'l')
            ->where('l.tenant = :tenantId')
            ->andWhere('l.status = :leaseStatus')
            ->setParameter('tenantId', $tenantId)
            ->setParameter('leaseStatus', 'active');

        if ($search) {
            $qb->andWhere('p.address LIKE :search OR p.city LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($status) {
            $qb->andWhere('p.status = :status')
               ->setParameter('status', $status);
        }

        if ($type) {
            $qb->andWhere('p.propertyType = :type')
               ->setParameter('type', $type);
        }

        return $qb->orderBy('p.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Trouve les propriétés d'un propriétaire avec filtres
     */
    public function findByOwnerWithFilters(int $ownerId, ?string $search = null, ?string $status = null, ?string $type = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.owner = :ownerId')
            ->setParameter('ownerId', $ownerId);

        if ($search) {
            $qb->andWhere('p.address LIKE :search OR p.city LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($status) {
            $qb->andWhere('p.status = :status')
               ->setParameter('status', $status);
        }

        if ($type) {
            $qb->andWhere('p.propertyType = :type')
               ->setParameter('type', $type);
        }

        return $qb->orderBy('p.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }
}
