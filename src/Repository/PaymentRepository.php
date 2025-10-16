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
     * Trouve les paiements en retard depuis au moins X jours
     * Utile pour envoyer des rappels après un délai configuré
     *
     * @param int $days Nombre de jours de retard minimum
     * @return array Liste des paiements en retard
     */
    public function findOverdueByDays(int $days = 7): array
    {
        $reminderDate = new \DateTime();
        $reminderDate->modify("-{$days} days");

        return $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->andWhere('p.dueDate <= :reminderDate')
            ->setParameter('status', 'En attente')
            ->setParameter('reminderDate', $reminderDate)
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

    /**
     * Trouve les paiements d'un locataire avec filtres
     */
    public function findByTenantWithFilters(int $tenantId, ?string $status = null, ?string $type = null, ?int $year = null, ?int $month = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->join('p.lease', 'l')
            ->join('l.tenant', 't')
            ->where('t.id = :tenantId')
            ->setParameter('tenantId', $tenantId);

        if ($status) {
            $qb->andWhere('p.status = :status')
               ->setParameter('status', $status);
        }

        if ($type) {
            $qb->andWhere('p.type = :type')
               ->setParameter('type', $type);
        }

        if ($year) {
            $startDate = new \DateTime("$year-01-01");
            $endDate = new \DateTime("$year-12-31 23:59:59");
            $qb->andWhere('p.dueDate BETWEEN :startDate AND :endDate')
               ->setParameter('startDate', $startDate)
               ->setParameter('endDate', $endDate);
        }

        if ($month) {
            $startDate = new \DateTime("$year-$month-01");
            $endDate = new \DateTime("$year-$month-31 23:59:59");
            $qb->andWhere('p.dueDate BETWEEN :startDate AND :endDate')
               ->setParameter('startDate', $startDate)
               ->setParameter('endDate', $endDate);
        }

        return $qb->orderBy('p.dueDate', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Trouve les paiements gérés par un gestionnaire avec filtres
     */
    public function findByManagerWithFilters(int $ownerId, ?string $status = null, ?string $type = null, ?int $year = null, ?int $month = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->join('p.lease', 'l')
            ->join('l.property', 'prop')
            ->join('prop.owner', 'o')
            ->where('o.id = :ownerId')
            ->setParameter('ownerId', $ownerId);

        if ($status) {
            $qb->andWhere('p.status = :status')
               ->setParameter('status', $status);
        }

        if ($type) {
            $qb->andWhere('p.type = :type')
               ->setParameter('type', $type);
        }

        if ($year) {
            $startDate = new \DateTime("$year-01-01");
            $endDate = new \DateTime("$year-12-31 23:59:59");
            $qb->andWhere('p.dueDate BETWEEN :startDate AND :endDate')
               ->setParameter('startDate', $startDate)
               ->setParameter('endDate', $endDate);
        }

        if ($month) {
            $startDate = new \DateTime("$year-$month-01");
            $endDate = new \DateTime("$year-$month-31 23:59:59");
            $qb->andWhere('p.dueDate BETWEEN :startDate AND :endDate')
               ->setParameter('startDate', $startDate)
               ->setParameter('endDate', $endDate);
        }

        return $qb->orderBy('p.dueDate', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Trouve les paiements en retard pour un gestionnaire
     */
    public function findOverdueByManager(int $ownerId): array
    {
        $qb = $this->createQueryBuilder('p')
            ->join('p.lease', 'l')
            ->join('l.property', 'prop')
            ->join('prop.owner', 'o')
            ->where('o.id = :ownerId')
            ->andWhere('p.status != :status')
            ->andWhere('p.dueDate < :now')
            ->setParameter('ownerId', $ownerId)
            ->setParameter('status', 'Payé')
            ->setParameter('now', new \DateTime());

        return $qb->orderBy('p.dueDate', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Trouve les paiements en retard pour un locataire
     */
    public function findOverdueByTenant(int $tenantId): array
    {
        $qb = $this->createQueryBuilder('p')
            ->join('p.lease', 'l')
            ->join('l.tenant', 't')
            ->where('t.id = :tenantId')
            ->andWhere('p.status != :status')
            ->andWhere('p.dueDate < :now')
            ->setParameter('tenantId', $tenantId)
            ->setParameter('status', 'Payé')
            ->setParameter('now', new \DateTime());

        return $qb->orderBy('p.dueDate', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Calcule le revenu mensuel pour un gestionnaire
     */
    public function getMonthlyIncomeByManager(int $ownerId): float
    {
        $currentMonth = new \DateTime('first day of this month');
        $nextMonth = new \DateTime('first day of next month');

        $qb = $this->createQueryBuilder('p')
            ->select('SUM(p.amount)')
            ->join('p.lease', 'l')
            ->join('l.property', 'prop')
            ->join('prop.owner', 'o')
            ->where('o.id = :ownerId')
            ->andWhere('p.status = :status')
            ->andWhere('p.createdAt >= :startDate')
            ->andWhere('p.createdAt < :endDate')
            ->setParameter('ownerId', $ownerId)
            ->setParameter('status', 'Payé')
            ->setParameter('startDate', $currentMonth)
            ->setParameter('endDate', $nextMonth);

        $result = $qb->getQuery()->getSingleScalarResult();
        return $result ? (float) $result : 0.0;
    }
<<<<<<< HEAD

    /**
     * Trouve les paiements avec filtres par organisation
     */
    public function findByOrganizationWithFilters(int $organizationId, ?string $status = null, ?string $type = null, ?int $year = null, ?int $month = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.organization = :organizationId')
            ->setParameter('organizationId', $organizationId);

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
     * Trouve les paiements avec filtres par société
     */
    public function findByCompanyWithFilters(int $companyId, ?string $status = null, ?string $type = null, ?int $year = null, ?int $month = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.company = :companyId')
            ->setParameter('companyId', $companyId);

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
     * Trouve les paiements en retard par organisation
     */
    public function findOverdueByOrganization(int $organizationId): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.organization = :organizationId')
            ->andWhere('p.dueDate < :now')
            ->andWhere('p.status != :status')
            ->setParameter('organizationId', $organizationId)
            ->setParameter('now', new \DateTime())
            ->setParameter('status', 'Payé')
            ->orderBy('p.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les paiements en retard par société
     */
    public function findOverdueByCompany(int $companyId): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.company = :companyId')
            ->andWhere('p.dueDate < :now')
            ->andWhere('p.status != :status')
            ->setParameter('companyId', $companyId)
            ->setParameter('now', new \DateTime())
            ->setParameter('status', 'Payé')
            ->orderBy('p.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule les revenus mensuels par organisation
     */
    public function getMonthlyIncomeByOrganization(int $organizationId): float
    {
        $startDate = new \DateTime('first day of this month');
        $endDate = new \DateTime('last day of this month');

        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.amount)')
            ->where('p.organization = :organizationId')
            ->andWhere('p.paidDate BETWEEN :start AND :end')
            ->andWhere('p.status = :status')
            ->setParameter('organizationId', $organizationId)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->setParameter('status', 'Payé')
            ->getQuery()
            ->getSingleScalarResult();

        return (float)($result ?? 0);
    }

    /**
     * Calcule les revenus mensuels par société
     */
    public function getMonthlyIncomeByCompany(int $companyId): float
    {
        $startDate = new \DateTime('first day of this month');
        $endDate = new \DateTime('last day of this month');

        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.amount)')
            ->where('p.company = :companyId')
            ->andWhere('p.paidDate BETWEEN :start AND :end')
            ->andWhere('p.status = :status')
            ->setParameter('companyId', $companyId)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->setParameter('status', 'Payé')
            ->getQuery()
            ->getSingleScalarResult();

        return (float)($result ?? 0);
    }

    /**
     * Statistiques des paiements par organisation
     */
    public function getStatisticsByOrganization(int $organizationId): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.organization = :organizationId')
            ->setParameter('organizationId', $organizationId);

        return [
            'total' => $qb->select('COUNT(p.id)')->getQuery()->getSingleScalarResult(),
            'paid' => $qb->select('COUNT(p.id)')
                ->where('p.organization = :organizationId')
                ->andWhere('p.status = :status')
                ->setParameter('organizationId', $organizationId)
                ->setParameter('status', 'Payé')
                ->getQuery()
                ->getSingleScalarResult(),
            'pending' => $qb->select('COUNT(p.id)')
                ->where('p.organization = :organizationId')
                ->andWhere('p.status = :status')
                ->setParameter('organizationId', $organizationId)
                ->setParameter('status', 'En attente')
                ->getQuery()
                ->getSingleScalarResult(),
            'overdue' => count($this->findOverdueByOrganization($organizationId)),
        ];
    }

    /**
     * Statistiques des paiements par société
     */
    public function getStatisticsByCompany(int $companyId): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.company = :companyId')
            ->setParameter('companyId', $companyId);

        return [
            'total' => $qb->select('COUNT(p.id)')->getQuery()->getSingleScalarResult(),
            'paid' => $qb->select('COUNT(p.id)')
                ->where('p.company = :companyId')
                ->andWhere('p.status = :status')
                ->setParameter('companyId', $companyId)
                ->setParameter('status', 'Payé')
                ->getQuery()
                ->getSingleScalarResult(),
            'pending' => $qb->select('COUNT(p.id)')
                ->where('p.company = :companyId')
                ->andWhere('p.status = :status')
                ->setParameter('companyId', $companyId)
                ->setParameter('status', 'En attente')
                ->getQuery()
                ->getSingleScalarResult(),
            'overdue' => count($this->findOverdueByCompany($companyId)),
        ];
    }
=======
>>>>>>> 6e87c3851b8abe300389f1559fefe39834f199e8
}
