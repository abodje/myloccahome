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
     * Trouve toutes les dépenses avec les propriétés associées
     */
    public function findAllWithProperties(): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.property', 'p')
            ->addSelect('p')
            ->orderBy('e.expenseDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les dépenses pour une propriété donnée
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
     * Trouve les dépenses par catégorie
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.property', 'p')
            ->addSelect('p')
            ->where('e.category = :category')
            ->setParameter('category', $category)
            ->orderBy('e.expenseDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les dépenses par période
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.property', 'p')
            ->addSelect('p')
            ->where('e.expenseDate BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('e.expenseDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le total des dépenses ce mois
     */
    public function getTotalThisMonth(): float
    {
        $startOfMonth = new \DateTime('first day of this month');
        $endOfMonth = new \DateTime('last day of this month');
        
        $result = $this->createQueryBuilder('e')
            ->select('SUM(e.amount) as total')
            ->where('e.expenseDate BETWEEN :startOfMonth AND :endOfMonth')
            ->setParameter('startOfMonth', $startOfMonth)
            ->setParameter('endOfMonth', $endOfMonth)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Calcule le total des dépenses cette année
     */
    public function getTotalThisYear(): float
    {
        $startOfYear = new \DateTime('first day of January this year');
        $endOfYear = new \DateTime('last day of December this year');
        
        $result = $this->createQueryBuilder('e')
            ->select('SUM(e.amount) as total')
            ->where('e.expenseDate BETWEEN :startOfYear AND :endOfYear')
            ->setParameter('startOfYear', $startOfYear)
            ->setParameter('endOfYear', $endOfYear)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Statistiques par catégorie
     */
    public function getStatisticsByCategory(): array
    {
        return $this->createQueryBuilder('e')
            ->select('e.category, SUM(e.amount) as total, COUNT(e.id) as count')
            ->groupBy('e.category')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques par propriété
     */
    public function getStatisticsByProperty(): array
    {
        return $this->createQueryBuilder('e')
            ->select('p.address, p.city, SUM(e.amount) as total, COUNT(e.id) as count')
            ->leftJoin('e.property', 'p')
            ->where('e.property IS NOT NULL')
            ->groupBy('p.id')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des dépenses
     */
    public function getStatistics(): array
    {
        $thisMonth = $this->getTotalThisMonth();
        $thisYear = $this->getTotalThisYear();
        $total = $this->count([]);
        
        return [
            'total_count' => $total,
            'this_month' => $thisMonth,
            'this_year' => $thisYear,
            'by_category' => $this->getStatisticsByCategory()
        ];
    }
}