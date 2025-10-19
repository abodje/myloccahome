<?php

namespace App\Repository;

use App\Entity\AdvancePayment;
use App\Entity\Lease;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdvancePayment>
 */
class AdvancePaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdvancePayment::class);
    }

    /**
     * Trouve tous les acomptes disponibles pour un bail
     */
    public function findAvailableByLease(Lease $lease): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.lease = :lease')
            ->andWhere('a.remainingBalance > 0')
            ->andWhere('a.status != :status')
            ->setParameter('lease', $lease)
            ->setParameter('status', 'Remboursé')
            ->orderBy('a.paidDate', 'ASC') // FIFO : First In First Out
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le solde total disponible pour un bail
     */
    public function getTotalAvailableBalance(Lease $lease): float
    {
        $result = $this->createQueryBuilder('a')
            ->select('SUM(a.remainingBalance) as total')
            ->where('a.lease = :lease')
            ->andWhere('a.remainingBalance > 0')
            ->andWhere('a.status != :status')
            ->setParameter('lease', $lease)
            ->setParameter('status', 'Remboursé')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : 0;
    }

    /**
     * Trouve tous les acomptes d'un locataire (via ses baux)
     */
    public function findByTenant($tenantId): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.lease', 'l')
            ->join('l.tenant', 't')
            ->where('t.id = :tenantId')
            ->setParameter('tenantId', $tenantId)
            ->orderBy('a.paidDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques sur les acomptes
     */
    public function getStatistics(): array
    {
        // Total des acomptes disponibles
        $totalAvailable = $this->createQueryBuilder('a')
            ->select('COALESCE(SUM(a.remainingBalance), 0) as total')
            ->where('a.remainingBalance > 0')
            ->andWhere('a.status != :status')
            ->setParameter('status', 'Remboursé')
            ->getQuery()
            ->getSingleScalarResult();

        // Total des acomptes utilisés
        $totalUsed = $this->createQueryBuilder('a')
            ->select('COALESCE(SUM(a.amount - a.remainingBalance), 0) as total')
            ->getQuery()
            ->getSingleScalarResult();

        // Nombre d'acomptes actifs
        $activeCount = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.remainingBalance > 0')
            ->andWhere('a.status != :status')
            ->setParameter('status', 'Remboursé')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total_available' => (float) $totalAvailable,
            'total_used' => (float) $totalUsed,
            'active_count' => (int) $activeCount,
            'total_amount' => (float) $totalAvailable + (float) $totalUsed,
        ];
    }

    /**
     * Trouve les acomptes par statut
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.status = :status')
            ->setParameter('status', $status)
            ->orderBy('a.paidDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les acomptes récents (dernier mois)
     */
    public function findRecent(int $days = 30): array
    {
        $date = new \DateTime();
        $date->modify("-{$days} days");

        return $this->createQueryBuilder('a')
            ->where('a.paidDate >= :date')
            ->setParameter('date', $date)
            ->orderBy('a.paidDate', 'DESC')
            ->getQuery()
            ->getResult();
    }


    /**
     * Trouve les acomptes d'un gestionnaire
     */
    public function findByManager(int $ownerId): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.lease', 'l')
            ->join('l.property', 'p')
            ->where('p.owner = :ownerId')
            ->setParameter('ownerId', $ownerId)
            ->orderBy('a.paidDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les acomptes d'une société
     */
    public function findByCompany($company): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.lease', 'l')
            ->join('l.property', 'p')
            ->where('p.company = :company')
            ->setParameter('company', $company)
            ->orderBy('a.paidDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les acomptes d'une organisation
     */
    public function findByOrganization($organization): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.lease', 'l')
            ->join('l.property', 'p')
            ->where('p.organization = :organization')
            ->setParameter('organization', $organization)
            ->orderBy('a.paidDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

