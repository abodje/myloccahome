<?php

namespace App\Repository;

use App\Entity\OnlinePayment;
use App\Entity\Lease;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OnlinePayment>
 */
class OnlinePaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OnlinePayment::class);
    }

    /**
     * Trouve une transaction par son ID CinetPay
     */
    public function findByTransactionId(string $transactionId): ?OnlinePayment
    {
        return $this->findOneBy(['transactionId' => $transactionId]);
    }

    /**
     * Trouve toutes les transactions d'un bail
     */
    public function findByLease(Lease $lease): array
    {
        return $this->findBy(['lease' => $lease], ['createdAt' => 'DESC']);
    }

    /**
     * Trouve les transactions par statut
     */
    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status], ['createdAt' => 'DESC']);
    }

    /**
     * Trouve les transactions en attente depuis plus de X minutes
     */
    public function findPendingOlderThan(int $minutes = 30): array
    {
        $date = new \DateTime();
        $date->modify("-{$minutes} minutes");

        return $this->createQueryBuilder('o')
            ->where('o.status = :status')
            ->andWhere('o.createdAt < :date')
            ->setParameter('status', 'pending')
            ->setParameter('date', $date)
            ->orderBy('o.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques globales
     */
    public function getStatistics(): array
    {
        // Total des transactions complétées
        $totalCompleted = $this->createQueryBuilder('o')
            ->select('COALESCE(SUM(o.amount), 0) as total')
            ->where('o.status = :status')
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();

        // Nombre de transactions par statut
        $countByStatus = [];
        $statuses = ['pending', 'completed', 'failed', 'cancelled'];

        foreach ($statuses as $status) {
            $countByStatus[$status] = $this->count(['status' => $status]);
        }

        // Transactions du mois en cours
        $startOfMonth = new \DateTime('first day of this month 00:00:00');
        $monthlyTransactions = $this->createQueryBuilder('o')
            ->select('COUNT(o.id) as count, COALESCE(SUM(o.amount), 0) as total')
            ->where('o.createdAt >= :start')
            ->andWhere('o.status = :status')
            ->setParameter('start', $startOfMonth)
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleResult();

        return [
            'total_amount' => (float) $totalCompleted,
            'count_by_status' => $countByStatus,
            'monthly_count' => (int) $monthlyTransactions['count'],
            'monthly_amount' => (float) $monthlyTransactions['total'],
        ];
    }

    /**
     * Récupère les transactions récentes
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

