<?php

namespace App\Repository;

use App\Entity\Subscription;
use App\Entity\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subscription>
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    /**
     * Trouve l'abonnement actif d'une organisation
     */
    public function findActiveByOrganization(Organization $organization): ?Subscription
    {
        return $this->createQueryBuilder('s')
            ->where('s.organization = :org')
            ->andWhere('s.status = :status')
            ->andWhere('s.endDate > :now')
            ->setParameter('org', $organization)
            ->setParameter('status', 'ACTIVE')
            ->setParameter('now', new \DateTime())
            ->orderBy('s.endDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve les abonnements qui expirent bientôt
     */
    public function findExpiringSoon(int $days = 7): array
    {
        $now = new \DateTime();
        $futureDate = (clone $now)->modify("+{$days} days");

        return $this->createQueryBuilder('s')
            ->where('s.status = :status')
            ->andWhere('s.endDate BETWEEN :now AND :future')
            ->andWhere('s.autoRenew = :autoRenew')
            ->setParameter('status', 'ACTIVE')
            ->setParameter('now', $now)
            ->setParameter('future', $futureDate)
            ->setParameter('autoRenew', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les abonnements expirés
     */
    public function findExpired(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.status = :status')
            ->andWhere('s.endDate < :now')
            ->setParameter('status', 'ACTIVE')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des abonnements
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('s');

        return [
            'total' => $qb->select('COUNT(s.id)')->getQuery()->getSingleScalarResult(),
            'active' => $qb->select('COUNT(s.id)')
                ->where('s.status = :status')
                ->setParameter('status', 'ACTIVE')
                ->getQuery()
                ->getSingleScalarResult(),
            'expired' => $qb->select('COUNT(s.id)')
                ->where('s.status = :status')
                ->setParameter('status', 'EXPIRED')
                ->getQuery()
                ->getSingleScalarResult(),
            'cancelled' => $qb->select('COUNT(s.id)')
                ->where('s.status = :status')
                ->setParameter('status', 'CANCELLED')
                ->getQuery()
                ->getSingleScalarResult(),
            'monthly_revenue' => $this->getMonthlyRevenue(),
        ];
    }

    /**
     * Calcule le revenu mensuel récurrent (MRR)
     */
    public function getMonthlyRevenue(): float
    {
        $result = $this->createQueryBuilder('s')
            ->select('SUM(CASE WHEN s.billingCycle = :monthly THEN s.amount ELSE s.amount/12 END)')
            ->where('s.status = :status')
            ->setParameter('status', 'ACTIVE')
            ->setParameter('monthly', 'MONTHLY')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }
}

