<?php

namespace App\Repository;

use App\Entity\Expense;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Expense>
 */
class ExpenseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Expense::class);
    }

    /**
     * Trouve les dépenses par catégorie
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.category = :category')
            ->setParameter('category', $category)
            ->orderBy('e.expenseDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les dépenses d'une propriété
     */
    public function findByProperty(int $propertyId): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.property = :propertyId')
            ->setParameter('propertyId', $propertyId)
            ->orderBy('e.expenseDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Dépenses totales par période
     */
    public function getTotalExpensesByPeriod(\DateTime $startDate, \DateTime $endDate): float
    {
        $result = $this->createQueryBuilder('e')
            ->select('SUM(e.amount)')
            ->where('e.expenseDate BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->getQuery()
            ->getSingleScalarResult();

        return (float)($result ?? 0);
    }

    /**
     * Dépenses par catégorie sur une période
     */
    public function getExpensesByCategory(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('e')
            ->select('e.category, SUM(e.amount) as total')
            ->where('e.expenseDate BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->groupBy('e.category')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche dans les dépenses
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.description LIKE :query')
            ->orWhere('e.supplier LIKE :query')
            ->orWhere('e.invoiceNumber LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('e.expenseDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des dépenses
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('e');
        $currentMonth = new \DateTime('first day of this month');
        $nextMonth = new \DateTime('first day of next month');

        return [
            'total' => $qb->select('COUNT(e.id)')->getQuery()->getSingleScalarResult(),
            'monthly_total' => $this->getTotalExpensesByPeriod($currentMonth, $nextMonth),
            'by_category' => $this->getExpensesByCategory($currentMonth, $nextMonth),
        ];
    }
}
