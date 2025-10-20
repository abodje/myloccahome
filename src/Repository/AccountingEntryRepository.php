<?php

namespace App\Repository;

use App\Entity\AccountingEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccountingEntry>
 */
class AccountingEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccountingEntry::class);
    }

    /**
     * Trouve toutes les écritures comptables triées par date
     */
    public function findAllOrderedByDate(): array
    {
        return $this->createQueryBuilder('ae')
            ->orderBy('ae.entryDate', 'DESC')
            ->addOrderBy('ae.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les écritures par période
     */
    public function findByDateRange(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('ae')
            ->where('ae.entryDate BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('ae.entryDate', 'DESC')
            ->addOrderBy('ae.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les écritures par type
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('ae')
            ->where('ae.type = :type')
            ->setParameter('type', $type)
            ->orderBy('ae.entryDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les écritures par catégorie
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('ae')
            ->where('ae.category = :category')
            ->setParameter('category', $category)
            ->orderBy('ae.entryDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les écritures d'une propriété
     */
    public function findByProperty(int $propertyId): array
    {
        return $this->createQueryBuilder('ae')
            ->where('ae.property = :propertyId')
            ->setParameter('propertyId', $propertyId)
            ->orderBy('ae.entryDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les écritures d'un propriétaire
     */
    public function findByOwner(int $ownerId): array
    {
        return $this->createQueryBuilder('ae')
            ->where('ae.owner = :ownerId')
            ->setParameter('ownerId', $ownerId)
            ->orderBy('ae.entryDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le solde actuel
     */
    public function getCurrentBalance(): float
    {
        $credits = $this->createQueryBuilder('ae')
            ->select('SUM(ae.amount)')
            ->where('ae.type = :type')
            ->setParameter('type', 'CREDIT')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $debits = $this->createQueryBuilder('ae')
            ->select('SUM(ae.amount)')
            ->where('ae.type = :type')
            ->setParameter('type', 'DEBIT')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        return (float)$credits - (float)$debits;
    }

    /**
     * Calcule le solde à une date donnée
     */
    public function getBalanceAtDate(\DateTime $date): float
    {
        $credits = $this->createQueryBuilder('ae')
            ->select('SUM(ae.amount)')
            ->where('ae.type = :type')
            ->andWhere('ae.entryDate <= :date')
            ->setParameter('type', 'CREDIT')
            ->setParameter('date', $date)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $debits = $this->createQueryBuilder('ae')
            ->select('SUM(ae.amount)')
            ->where('ae.type = :type')
            ->andWhere('ae.entryDate <= :date')
            ->setParameter('type', 'DEBIT')
            ->setParameter('date', $date)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        return (float)$credits - (float)$debits;
    }

    /**
     * Statistiques comptables
     */
    public function getAccountingStatistics(): array
    {
        $currentMonth = new \DateTime('first day of this month');
        $nextMonth = new \DateTime('first day of next month');

        $monthlyCredits = $this->createQueryBuilder('ae')
            ->select('SUM(ae.amount)')
            ->where('ae.type = :type')
            ->andWhere('ae.entryDate BETWEEN :start AND :end')
            ->setParameter('type', 'CREDIT')
            ->setParameter('start', $currentMonth)
            ->setParameter('end', $nextMonth)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $monthlyDebits = $this->createQueryBuilder('ae')
            ->select('SUM(ae.amount)')
            ->where('ae.type = :type')
            ->andWhere('ae.entryDate BETWEEN :start AND :end')
            ->setParameter('type', 'DEBIT')
            ->setParameter('start', $currentMonth)
            ->setParameter('end', $nextMonth)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        return [
            'current_balance' => $this->getCurrentBalance(),
            'monthly_credits' => (float)$monthlyCredits,
            'monthly_debits' => (float)$monthlyDebits,
            'monthly_net' => (float)$monthlyCredits - (float)$monthlyDebits,
            'total_entries' => $this->count([]),
        ];
    }

    /**
     * Recherche dans les écritures
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('ae')
            ->where('ae.description LIKE :query')
            ->orWhere('ae.reference LIKE :query')
            ->orWhere('ae.notes LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('ae.entryDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recalcule les soldes courants pour toutes les écritures
     */
    public function recalculateRunningBalances(): void
    {
        $entries = $this->findBy([], ['entryDate' => 'ASC', 'createdAt' => 'ASC']);
        $runningBalance = 0;

        foreach ($entries as $entry) {
            $runningBalance += $entry->getSignedAmount();
            $entry->setRunningBalance((string)$runningBalance);
        }

        $this->getEntityManager()->flush();
    }

    /**
     * Trouve les écritures avec filtres
     */
    public function findWithFilters(?string $type = null, ?string $category = null, ?int $year = null, ?int $month = null): array
    {
        $qb = $this->createQueryBuilder('ae');

        if ($type) {
            $qb->andWhere('ae.type = :type')
               ->setParameter('type', $type);
        }

        if ($category) {
            $qb->andWhere('ae.category = :category')
               ->setParameter('category', $category);
        }

        if ($year) {
            $startDate = new \DateTime("{$year}-01-01");
            $endDate = new \DateTime("{$year}-12-31");
            $qb->andWhere('ae.entryDate BETWEEN :startYear AND :endYear')
               ->setParameter('startYear', $startDate)
               ->setParameter('endYear', $endDate);
        }

        if ($month && $year) {
            $startDate = new \DateTime("{$year}-{$month}-01");
            $endDate = clone $startDate;
            $endDate->modify('last day of this month');
            $qb->andWhere('ae.entryDate BETWEEN :startMonth AND :endMonth')
               ->setParameter('startMonth', $startDate)
               ->setParameter('endMonth', $endDate);
        }

        return $qb->orderBy('ae.entryDate', 'DESC')
                  ->addOrderBy('ae.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Trouve les écritures comptables d'un locataire avec filtres
     */
    public function findByTenantWithFilters(int $tenantId, ?string $type = null, ?string $category = null, ?int $year = null, ?int $month = null): array
    {
        $qb = $this->createQueryBuilder('ae')
            ->where('ae.description LIKE :tenantPattern OR ae.reference LIKE :tenantRefPattern')
            ->setParameter('tenantPattern', '%locataire%' . $tenantId . '%')
            ->setParameter('tenantRefPattern', '%TENANT-' . $tenantId . '%');

        if ($type) {
            $qb->andWhere('ae.type = :type')
               ->setParameter('type', $type);
        }

        if ($category) {
            $qb->andWhere('ae.category = :category')
               ->setParameter('category', $category);
        }

        if ($year) {
            $startDate = new \DateTime("$year-01-01");
            $endDate = new \DateTime("$year-12-31 23:59:59");
            $qb->andWhere('ae.entryDate BETWEEN :startYear AND :endYear')
               ->setParameter('startYear', $startDate)
               ->setParameter('endYear', $endDate);
        }

        if ($month) {
            $startDate = new \DateTime("{$year}-{$month}-01");
            $endDate = clone $startDate;
            $endDate->modify('last day of this month');
            $qb->andWhere('ae.entryDate BETWEEN :startMonth AND :endMonth')
               ->setParameter('startMonth', $startDate)
               ->setParameter('endMonth', $endDate);
        }

        return $qb->orderBy('ae.entryDate', 'DESC')
                  ->addOrderBy('ae.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Trouve les écritures comptables d'un gestionnaire avec filtres
     */
    public function findByManagerWithFilters(int $ownerId, ?string $type = null, ?string $category = null, ?int $year = null, ?int $month = null): array
    {
        $qb = $this->createQueryBuilder('ae')
            ->where('ae.description LIKE :ownerPattern OR ae.reference LIKE :ownerRefPattern')
            ->setParameter('ownerPattern', '%propriétaire%' . $ownerId . '%')
            ->setParameter('ownerRefPattern', '%OWNER-' . $ownerId . '%');

        if ($type) {
            $qb->andWhere('ae.type = :type')
               ->setParameter('type', $type);
        }

        if ($category) {
            $qb->andWhere('ae.category = :category')
               ->setParameter('category', $category);
        }

        if ($year) {
            $startDate = new \DateTime("$year-01-01");
            $endDate = new \DateTime("$year-12-31 23:59:59");
            $qb->andWhere('ae.entryDate BETWEEN :startYear AND :endYear')
               ->setParameter('startYear', $startDate)
               ->setParameter('endYear', $endDate);
        }

        if ($month) {
            $startDate = new \DateTime("{$year}-{$month}-01");
            $endDate = clone $startDate;
            $endDate->modify('last day of this month');
            $qb->andWhere('ae.entryDate BETWEEN :startMonth AND :endMonth')
               ->setParameter('startMonth', $startDate)
               ->setParameter('endMonth', $endDate);
        }

        return $qb->orderBy('ae.entryDate', 'DESC')
                  ->addOrderBy('ae.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Statistiques pour un locataire
     */
    public function getTenantStatistics(int $tenantId): array
    {
        $entries = $this->createQueryBuilder('ae')
            ->where('ae.description LIKE :tenantPattern OR ae.reference LIKE :tenantRefPattern')
            ->setParameter('tenantPattern', '%locataire%' . $tenantId . '%')
            ->setParameter('tenantRefPattern', '%TENANT-' . $tenantId . '%')
            ->getQuery()
            ->getResult();

        $totalCredits = 0;
        $totalDebits = 0;
        $currentMonthCredits = 0;
        $currentMonthDebits = 0;

        $currentMonth = (int)date('m');
        $currentYear = (int)date('Y');

        foreach ($entries as $entry) {
            $amount = (float)$entry->getAmount();
            $entryMonth = (int)$entry->getEntryDate()->format('m');
            $entryYear = (int)$entry->getEntryDate()->format('Y');

            if ($entry->getType() === 'CREDIT') {
                $totalCredits += $amount;
                if ($entryMonth === $currentMonth && $entryYear === $currentYear) {
                    $currentMonthCredits += $amount;
                }
            } else {
                $totalDebits += $amount;
                if ($entryMonth === $currentMonth && $entryYear === $currentYear) {
                    $currentMonthDebits += $amount;
                }
            }
        }

        return [
            'total_credits' => $totalCredits,
            'total_debits' => $totalDebits,
            'balance' => $totalCredits - $totalDebits,
            'current_month_credits' => $currentMonthCredits,
            'current_month_debits' => $currentMonthDebits,
            'total_entries' => count($entries),
        ];
    }

    /**
     * Statistiques pour un gestionnaire
     */
    public function getManagerStatistics(int $ownerId): array
    {
        $entries = $this->createQueryBuilder('ae')
            ->where('ae.description LIKE :ownerPattern OR ae.reference LIKE :ownerRefPattern')
            ->setParameter('ownerPattern', '%propriétaire%' . $ownerId . '%')
            ->setParameter('ownerRefPattern', '%OWNER-' . $ownerId . '%')
            ->getQuery()
            ->getResult();

        $totalCredits = 0;
        $totalDebits = 0;
        $currentMonthCredits = 0;
        $currentMonthDebits = 0;

        $currentMonth = (int)date('m');
        $currentYear = (int)date('Y');

        foreach ($entries as $entry) {
            $amount = (float)$entry->getAmount();
            $entryMonth = (int)$entry->getEntryDate()->format('m');
            $entryYear = (int)$entry->getEntryDate()->format('Y');

            if ($entry->getType() === 'CREDIT') {
                $totalCredits += $amount;
                if ($entryMonth === $currentMonth && $entryYear === $currentYear) {
                    $currentMonthCredits += $amount;
                }
            } else {
                $totalDebits += $amount;
                if ($entryMonth === $currentMonth && $entryYear === $currentYear) {
                    $currentMonthDebits += $amount;
                }
            }
        }

        return [
            'total_credits' => $totalCredits,
            'total_debits' => $totalDebits,
            'balance' => $totalCredits - $totalDebits,
            'current_month_credits' => $currentMonthCredits,
            'current_month_debits' => $currentMonthDebits,
            'total_entries' => count($entries),
        ];
    }

    /**
     * Trouve les écritures comptables filtrées par société
     */
    public function findByCompanyWithFilters($company, ?string $type = null, ?string $category = null, ?int $year = null, ?int $month = null): array
    {
        $qb = $this->createQueryBuilder('ae')
            ->where('ae.company = :company')
            ->setParameter('company', $company);

        if ($type) {
            $qb->andWhere('ae.type = :type')
               ->setParameter('type', $type);
        }

        if ($category) {
            $qb->andWhere('ae.category = :category')
               ->setParameter('category', $category);
        }

        if ($year) {
            $startDate = new \DateTime("{$year}-01-01");
            $endDate = new \DateTime("{$year}-12-31");
            $qb->andWhere('ae.entryDate BETWEEN :startYear AND :endYear')
               ->setParameter('startYear', $startDate)
               ->setParameter('endYear', $endDate);
        }

        if ($month && $year) {
            $startDate = new \DateTime("{$year}-{$month}-01");
            $endDate = clone $startDate;
            $endDate->modify('last day of this month');
            $qb->andWhere('ae.entryDate BETWEEN :startMonth AND :endMonth')
               ->setParameter('startMonth', $startDate)
               ->setParameter('endMonth', $endDate);
        }

        return $qb->orderBy('ae.entryDate', 'DESC')
                  ->addOrderBy('ae.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Trouve les écritures comptables filtrées par organisation
     */
    public function findByOrganizationWithFilters($organization, ?string $type = null, ?string $category = null, ?int $year = null, ?int $month = null): array
    {
        $qb = $this->createQueryBuilder('ae')
            ->where('ae.organization = :organization')
            ->setParameter('organization', $organization);

        if ($type) {
            $qb->andWhere('ae.type = :type')
               ->setParameter('type', $type);
        }

        if ($category) {
            $qb->andWhere('ae.category = :category')
               ->setParameter('category', $category);
        }

        if ($year) {
            $startDate = new \DateTime("{$year}-01-01");
            $endDate = new \DateTime("{$year}-12-31");
            $qb->andWhere('ae.entryDate BETWEEN :startYear AND :endYear')
               ->setParameter('startYear', $startDate)
               ->setParameter('endYear', $endDate);
        }

        if ($month && $year) {
            $startDate = new \DateTime("{$year}-{$month}-01");
            $endDate = clone $startDate;
            $endDate->modify('last day of this month');
            $qb->andWhere('ae.entryDate BETWEEN :startMonth AND :endMonth')
               ->setParameter('startMonth', $startDate)
               ->setParameter('endMonth', $endDate);
        }

        return $qb->orderBy('ae.entryDate', 'DESC')
                  ->addOrderBy('ae.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Calcule les statistiques pour une société
     */
    public function getCompanyStatistics($company): array
    {
        $entries = $this->findByCompanyWithFilters($company);
        return $this->calculateStatistics($entries);
    }

    /**
     * Calcule les statistiques pour une organisation
     */
    public function getOrganizationStatistics($organization): array
    {
        $entries = $this->findByOrganizationWithFilters($organization);
        return $this->calculateStatistics($entries);
    }

    /**
     * Calcule les statistiques à partir d'une liste d'écritures
     */
    private function calculateStatistics(array $entries): array
    {
        $totalCredits = 0;
        $totalDebits = 0;
        $currentMonthCredits = 0;
        $currentMonthDebits = 0;

        $currentMonth = (int)date('m');
        $currentYear = (int)date('Y');

        foreach ($entries as $entry) {
            $amount = (float)$entry->getAmount();
            $entryMonth = (int)$entry->getEntryDate()->format('m');
            $entryYear = (int)$entry->getEntryDate()->format('Y');

            if ($entry->getType() === 'CREDIT') {
                $totalCredits += $amount;
                if ($entryMonth === $currentMonth && $entryYear === $currentYear) {
                    $currentMonthCredits += $amount;
                }
            } else {
                $totalDebits += $amount;
                if ($entryMonth === $currentMonth && $entryYear === $currentYear) {
                    $currentMonthDebits += $amount;
                }
            }
        }

        return [
            'total_credits' => $totalCredits,
            'total_debits' => $totalDebits,
            'balance' => $totalCredits - $totalDebits,
            'current_month_credits' => $currentMonthCredits,
            'current_month_debits' => $currentMonthDebits,
            'total_entries' => count($entries),
        ];
    }

    /**
     * Trouve les écritures par organisation
     */
    public function findByOrganization($organization): array
    {
        return $this->createQueryBuilder('ae')
            ->where('ae.organization = :organization')
            ->setParameter('organization', $organization)
            ->orderBy('ae.entryDate', 'DESC')
            ->addOrderBy('ae.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les écritures par société
     */
    public function findByCompany($company): array
    {
        return $this->createQueryBuilder('ae')
            ->where('ae.company = :company')
            ->setParameter('company', $company)
            ->orderBy('ae.entryDate', 'DESC')
            ->addOrderBy('ae.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les écritures par organisation et société
     */
    public function findByOrganizationAndCompany($organization, $company): array
    {
        return $this->createQueryBuilder('ae')
            ->where('ae.organization = :organization')
            ->andWhere('ae.company = :company')
            ->setParameter('organization', $organization)
            ->setParameter('company', $company)
            ->orderBy('ae.entryDate', 'DESC')
            ->addOrderBy('ae.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }



}
