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
     * Trouve tous les contrats avec les propriétés et locataires
     */
    public function findAllWithRelations(): array
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.property', 'p')
            ->leftJoin('l.tenant', 't')
            ->addSelect('p', 't')
            ->orderBy('l.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les contrats actifs
     */
    public function findActive(): array
    {
        $qb = $this->createQueryBuilder('l');
        
        return $qb
            ->leftJoin('l.property', 'p')
            ->leftJoin('l.tenant', 't')
            ->addSelect('p', 't')
            ->where($qb->expr()->andX(
                $qb->expr()->lte('l.startDate', ':now'),
                $qb->expr()->orX(
                    $qb->expr()->isNull('l.endDate'),
                    $qb->expr()->gte('l.endDate', ':now')
                )
            ))
            ->setParameter('now', new \DateTime())
            ->orderBy('l.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les contrats qui se terminent bientôt
     */
    public function findEndingSoon(int $days = 30): array
    {
        $futureDate = new \DateTime();
        $futureDate->add(new \DateInterval('P' . $days . 'D'));
        
        $qb = $this->createQueryBuilder('l');
        
        return $qb
            ->leftJoin('l.property', 'p')
            ->leftJoin('l.tenant', 't')
            ->addSelect('p', 't')
            ->where($qb->expr()->andX(
                $qb->expr()->isNotNull('l.endDate'),
                $qb->expr()->between('l.endDate', ':now', ':futureDate')
            ))
            ->setParameter('now', new \DateTime())
            ->setParameter('futureDate', $futureDate)
            ->orderBy('l.endDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve le contrat actif pour une propriété
     */
    public function findCurrentByProperty(int $propertyId): ?Lease
    {
        $qb = $this->createQueryBuilder('l');
        
        return $qb
            ->leftJoin('l.property', 'p')
            ->leftJoin('l.tenant', 't')
            ->addSelect('p', 't')
            ->where('l.property = :propertyId')
            ->andWhere($qb->expr()->andX(
                $qb->expr()->lte('l.startDate', ':now'),
                $qb->expr()->orX(
                    $qb->expr()->isNull('l.endDate'),
                    $qb->expr()->gte('l.endDate', ':now')
                )
            ))
            ->setParameter('propertyId', $propertyId)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve le contrat actif pour un locataire
     */
    public function findCurrentByTenant(int $tenantId): ?Lease
    {
        $qb = $this->createQueryBuilder('l');
        
        return $qb
            ->leftJoin('l.property', 'p')
            ->leftJoin('l.tenant', 't')
            ->addSelect('p', 't')
            ->where('l.tenant = :tenantId')
            ->andWhere($qb->expr()->andX(
                $qb->expr()->lte('l.startDate', ':now'),
                $qb->expr()->orX(
                    $qb->expr()->isNull('l.endDate'),
                    $qb->expr()->gte('l.endDate', ':now')
                )
            ))
            ->setParameter('tenantId', $tenantId)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Calcule le revenu mensuel total des contrats actifs
     */
    public function getTotalMonthlyRevenue(): float
    {
        $qb = $this->createQueryBuilder('l');
        
        $result = $qb
            ->select('SUM(l.monthlyRent + COALESCE(l.charges, 0)) as total')
            ->where($qb->expr()->andX(
                $qb->expr()->lte('l.startDate', ':now'),
                $qb->expr()->orX(
                    $qb->expr()->isNull('l.endDate'),
                    $qb->expr()->gte('l.endDate', ':now')
                )
            ))
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Statistiques des contrats
     */
    public function getStatistics(): array
    {
        $total = $this->count([]);
        $active = count($this->findActive());
        $endingSoon = count($this->findEndingSoon());
        
        return [
            'total' => $total,
            'active' => $active,
            'ending_soon' => $endingSoon,
            'monthly_revenue' => $this->getTotalMonthlyRevenue()
        ];
    }

    /**
     * Trouve les contrats par année
     */
    public function findByYear(int $year): array
    {
        $startOfYear = new \DateTime($year . '-01-01');
        $endOfYear = new \DateTime($year . '-12-31');
        
        return $this->createQueryBuilder('l')
            ->leftJoin('l.property', 'p')
            ->leftJoin('l.tenant', 't')
            ->addSelect('p', 't')
            ->where('l.startDate BETWEEN :startOfYear AND :endOfYear')
            ->setParameter('startOfYear', $startOfYear)
            ->setParameter('endOfYear', $endOfYear)
            ->orderBy('l.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}