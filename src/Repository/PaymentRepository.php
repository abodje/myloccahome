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
     * @return Payment[] Returns overdue payments
     */
    public function findOverduePayments(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status IN (:statuses)')
            ->andWhere('p.dueDate < :today')
            ->setParameter('statuses', ['pending', 'overdue'])
            ->setParameter('today', new \DateTime())
            ->orderBy('p.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Payment[] Returns payments due soon
     */
    public function findPaymentsDueSoon(int $days = 7): array
    {
        $dueDate = (new \DateTime())->modify("+{$days} days");
        
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->andWhere('p.dueDate <= :dueDate')
            ->andWhere('p.dueDate >= :today')
            ->setParameter('status', 'pending')
            ->setParameter('dueDate', $dueDate)
            ->setParameter('today', new \DateTime())
            ->orderBy('p.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Payment[] Returns payments by tenant
     */
    public function findByTenant(int $tenantId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.tenant = :tenantId')
            ->setParameter('tenantId', $tenantId)
            ->orderBy('p.dueDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Payment[] Returns payments by contract
     */
    public function findByContract(int $contractId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.rentalContract = :contractId')
            ->setParameter('contractId', $contractId)
            ->orderBy('p.dueDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Payment[] Returns payments by date range
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.dueDate >= :startDate')
            ->andWhere('p.dueDate <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('p.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getPaymentStats(): array
    {
        return $this->createQueryBuilder('p')
            ->select([
                'COUNT(p.id) as total',
                'SUM(CASE WHEN p.status = \'paid\' THEN 1 ELSE 0 END) as paid',
                'SUM(CASE WHEN p.status = \'pending\' THEN 1 ELSE 0 END) as pending',
                'SUM(CASE WHEN p.status = \'overdue\' THEN 1 ELSE 0 END) as overdue',
                'SUM(CASE WHEN p.status = \'paid\' THEN p.amount ELSE 0 END) as totalPaid',
                'SUM(CASE WHEN p.status != \'paid\' THEN p.amount ELSE 0 END) as totalOutstanding'
            ])
            ->getQuery()
            ->getSingleResult();
    }

    public function getMonthlyRevenue(\DateTimeInterface $month): float
    {
        $startOfMonth = (clone $month)->modify('first day of this month')->setTime(0, 0, 0);
        $endOfMonth = (clone $month)->modify('last day of this month')->setTime(23, 59, 59);

        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.amount) as revenue')
            ->andWhere('p.status = :status')
            ->andWhere('p.paidDate >= :startDate')
            ->andWhere('p.paidDate <= :endDate')
            ->setParameter('status', 'paid')
            ->setParameter('startDate', $startOfMonth)
            ->setParameter('endDate', $endOfMonth)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }
}