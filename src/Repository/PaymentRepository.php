<?php

namespace App\Repository;

use App\Entity\Payment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payment>
 */
class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    /**
     * Trouve les paiements avec filtres
     */
    public function findWithFilters(?string $status = null, ?string $type = null, ?int $year = null, ?int $month = null): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($status) {
            $qb->andWhere('p.status = :status')
               ->setParameter('status', $status);
        }

        if ($type) {
            $qb->andWhere('p.type = :type')
               ->setParameter('type', $type);
        }

        if ($year) {
            $startDate = new \DateTime("{$year}-01-01");
            $endDate = new \DateTime("{$year}-12-31");
            $qb->andWhere('p.dueDate BETWEEN :startYear AND :endYear')
               ->setParameter('startYear', $startDate)
               ->setParameter('endYear', $endDate);
        }

        if ($month && $year) {
            $startDate = new \DateTime("{$year}-{$month}-01");
            $endDate = clone $startDate;
            $endDate->modify('last day of this month');
            $qb->andWhere('p.dueDate BETWEEN :startMonth AND :endMonth')
               ->setParameter('startMonth', $startDate)
               ->setParameter('endMonth', $endDate);
        }

        return $qb->orderBy('p.dueDate', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Trouve les paiements en retard
     */
    public function findOverdue(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.dueDate < :now')
            ->andWhere('p.status != :status')
            ->setParameter('now', new \DateTime())
            ->setParameter('status', 'Payé')
            ->orderBy('p.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les paiements payés par période
     */
    public function findPaidByPeriod(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.paidDate BETWEEN :start AND :end')
            ->andWhere('p.status = :status')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->setParameter('status', 'Payé')
            ->orderBy('p.paidDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Revenus mensuels
     */
    public function getMonthlyIncome(): float
    {
        $startDate = new \DateTime('first day of this month');
        $endDate = new \DateTime('last day of this month');

        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.amount)')
            ->where('p.paidDate BETWEEN :start AND :end')
            ->andWhere('p.status = :status')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->setParameter('status', 'Payé')
            ->getQuery()
            ->getSingleScalarResult();

        return (float)($result ?? 0);
    }

    /**
     * Revenus totaux par période
     */
    public function getTotalRevenueByPeriod(\DateTime $startDate, \DateTime $endDate): float
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.amount)')
            ->where('p.paidDate BETWEEN :start AND :end')
            ->andWhere('p.status = :status')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->setParameter('status', 'Payé')
            ->getQuery()
            ->getSingleScalarResult();

        return (float)($result ?? 0);
    }

    /**
     * Trouve les paiements par contrat
     */
    public function findByLease(int $leaseId): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.lease = :leaseId')
            ->setParameter('leaseId', $leaseId)
            ->orderBy('p.dueDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des paiements
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('p');

        return [
            'total' => $qb->select('COUNT(p.id)')->getQuery()->getSingleScalarResult(),
            'paid' => $qb->select('COUNT(p.id)')
                ->where('p.status = :status')
                ->setParameter('status', 'Payé')
                ->getQuery()
                ->getSingleScalarResult(),
            'pending' => $qb->select('COUNT(p.id)')
                ->where('p.status = :status')
                ->setParameter('status', 'En attente')
                ->getQuery()
                ->getSingleScalarResult(),
            'overdue' => count($this->findOverdue()),
        ];
    }
}
