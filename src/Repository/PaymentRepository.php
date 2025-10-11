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

    public function findOverduePayments(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status IN (:statuses)')
            ->andWhere('p.dueDate < CURRENT_DATE()')
            ->setParameter('statuses', ['pending', 'overdue'])
            ->orderBy('p.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findPaymentsByPeriod(string $period): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.period = :period')
            ->setParameter('period', $period)
            ->orderBy('p.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findPaymentsByContract(int $contractId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.rentalContract = :contractId')
            ->setParameter('contractId', $contractId)
            ->orderBy('p.period', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getMonthlyRevenue(int $year, int $month): float
    {
        $period = sprintf('%04d-%02d', $year, $month);
        
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.amount) as revenue')
            ->andWhere('p.period = :period')
            ->andWhere('p.status = :status')
            ->setParameter('period', $period)
            ->setParameter('status', 'paid')
            ->getQuery()
            ->getSingleScalarResult();

        return (float)($result ?? 0);
    }

    public function getYearlyRevenue(int $year): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.period, SUM(p.amount) as revenue')
            ->andWhere('p.period LIKE :year')
            ->andWhere('p.status = :status')
            ->setParameter('year', $year . '%')
            ->setParameter('status', 'paid')
            ->groupBy('p.period')
            ->orderBy('p.period', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getCollectionRate(string $period = null): float
    {
        $qb = $this->createQueryBuilder('p');
        
        if ($period) {
            $qb->andWhere('p.period = :period')
               ->setParameter('period', $period);
        }

        $total = $qb->select('COUNT(p.id)')
                    ->getQuery()
                    ->getSingleScalarResult();

        $paid = $qb->select('COUNT(p.id)')
                   ->andWhere('p.status = :status')
                   ->setParameter('status', 'paid')
                   ->getQuery()
                   ->getSingleScalarResult();

        return $total > 0 ? ($paid / $total) * 100 : 0;
    }

    public function getStatistics(): array
    {
        $total = $this->count([]);
        $currentMonth = date('Y-m');
        
        $statusStats = $this->createQueryBuilder('p')
            ->select('p.status, COUNT(p.id) as count')
            ->groupBy('p.status')
            ->getQuery()
            ->getResult();

        $monthlyStats = $this->createQueryBuilder('p')
            ->select('p.period, COUNT(p.id) as count, SUM(p.amount) as total')
            ->andWhere('p.period >= :sixMonthsAgo')
            ->setParameter('sixMonthsAgo', date('Y-m', strtotime('-6 months')))
            ->groupBy('p.period')
            ->orderBy('p.period', 'DESC')
            ->getQuery()
            ->getResult();

        $overdueCount = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.status IN (:statuses)')
            ->andWhere('p.dueDate < CURRENT_DATE()')
            ->setParameter('statuses', ['pending', 'overdue'])
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => $total,
            'statusStats' => $statusStats,
            'monthlyStats' => $monthlyStats,
            'overdueCount' => $overdueCount ?? 0,
            'collectionRate' => $this->getCollectionRate($currentMonth),
        ];
    }
}