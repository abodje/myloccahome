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
     * Trouve tous les paiements avec leurs relations
     */
    public function findAllWithRelations(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.lease', 'l')
            ->leftJoin('l.property', 'prop')
            ->leftJoin('l.tenant', 't')
            ->addSelect('l', 'prop', 't')
            ->orderBy('p.dueDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les paiements en retard
     */
    public function findOverdue(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.lease', 'l')
            ->leftJoin('l.property', 'prop')
            ->leftJoin('l.tenant', 't')
            ->addSelect('l', 'prop', 't')
            ->where('p.status != :paid')
            ->andWhere('p.dueDate < :now')
            ->setParameter('paid', 'Payé')
            ->setParameter('now', new \DateTime())
            ->orderBy('p.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les paiements dus ce mois
     */
    public function findDueThisMonth(): array
    {
        $startOfMonth = new \DateTime('first day of this month');
        $endOfMonth = new \DateTime('last day of this month');
        
        return $this->createQueryBuilder('p')
            ->leftJoin('p.lease', 'l')
            ->leftJoin('l.property', 'prop')
            ->leftJoin('l.tenant', 't')
            ->addSelect('l', 'prop', 't')
            ->where('p.dueDate BETWEEN :startOfMonth AND :endOfMonth')
            ->setParameter('startOfMonth', $startOfMonth)
            ->setParameter('endOfMonth', $endOfMonth)
            ->orderBy('p.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les paiements pour un contrat donné
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
     * Trouve les paiements pour une propriété donnée
     */
    public function findByProperty(int $propertyId): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.lease', 'l')
            ->leftJoin('l.property', 'prop')
            ->leftJoin('l.tenant', 't')
            ->addSelect('l', 'prop', 't')
            ->where('l.property = :propertyId')
            ->setParameter('propertyId', $propertyId)
            ->orderBy('p.dueDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les paiements pour un locataire donné
     */
    public function findByTenant(int $tenantId): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.lease', 'l')
            ->leftJoin('l.property', 'prop')
            ->leftJoin('l.tenant', 't')
            ->addSelect('l', 'prop', 't')
            ->where('l.tenant = :tenantId')
            ->setParameter('tenantId', $tenantId)
            ->orderBy('p.dueDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le total des paiements reçus ce mois
     */
    public function getTotalReceivedThisMonth(): float
    {
        $startOfMonth = new \DateTime('first day of this month');
        $endOfMonth = new \DateTime('last day of this month');
        
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.amount) as total')
            ->where('p.status = :paid')
            ->andWhere('p.paidDate BETWEEN :startOfMonth AND :endOfMonth')
            ->setParameter('paid', 'Payé')
            ->setParameter('startOfMonth', $startOfMonth)
            ->setParameter('endOfMonth', $endOfMonth)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Calcule le total des paiements en attente
     */
    public function getTotalPending(): float
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.amount) as total')
            ->where('p.status = :pending')
            ->setParameter('pending', 'En attente')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Statistiques des paiements
     */
    public function getStatistics(): array
    {
        $overdue = $this->findOverdue();
        $dueThisMonth = $this->findDueThisMonth();
        
        return [
            'overdue_count' => count($overdue),
            'overdue_amount' => array_sum(array_map(fn($p) => (float)$p->getAmount(), $overdue)),
            'due_this_month_count' => count($dueThisMonth),
            'due_this_month_amount' => array_sum(array_map(fn($p) => (float)$p->getAmount(), $dueThisMonth)),
            'received_this_month' => $this->getTotalReceivedThisMonth(),
            'total_pending' => $this->getTotalPending()
        ];
    }

    /**
     * Trouve les paiements par période
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.lease', 'l')
            ->leftJoin('l.property', 'prop')
            ->leftJoin('l.tenant', 't')
            ->addSelect('l', 'prop', 't')
            ->where('p.dueDate BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('p.dueDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}