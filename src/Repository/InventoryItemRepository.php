<?php

namespace App\Repository;

use App\Entity\InventoryItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InventoryItem>
 */
class InventoryItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InventoryItem::class);
    }

    /**
     * Trouve les éléments par condition
     */
    public function findByCondition(string $condition): array
    {
        return $this->createQueryBuilder('ii')
            ->where('ii.condition = :condition')
            ->setParameter('condition', $condition)
            ->orderBy('ii.room', 'ASC')
            ->addOrderBy('ii.item', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les éléments endommagés
     */
    public function findDamaged(): array
    {
        return $this->createQueryBuilder('ii')
            ->where('ii.condition IN (:conditions)')
            ->setParameter('conditions', ['Endommagé', 'Très mauvais', 'Mauvais'])
            ->orderBy('ii.room', 'ASC')
            ->addOrderBy('ii.item', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les éléments par pièce
     */
    public function findByRoom(string $room): array
    {
        return $this->createQueryBuilder('ii')
            ->where('ii.room = :room')
            ->setParameter('room', $room)
            ->orderBy('ii.category', 'ASC')
            ->addOrderBy('ii.item', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les éléments par catégorie
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('ii')
            ->where('ii.category = :category')
            ->setParameter('category', $category)
            ->orderBy('ii.room', 'ASC')
            ->addOrderBy('ii.item', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les éléments d'un inventaire par pièce
     */
    public function findByInventoryAndRoom(int $inventoryId, string $room): array
    {
        return $this->createQueryBuilder('ii')
            ->where('ii.inventory = :inventoryId')
            ->andWhere('ii.room = :room')
            ->setParameter('inventoryId', $inventoryId)
            ->setParameter('room', $room)
            ->orderBy('ii.category', 'ASC')
            ->addOrderBy('ii.item', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche dans les éléments d'inventaire
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('ii')
            ->where('ii.item LIKE :query')
            ->orWhere('ii.room LIKE :query')
            ->orWhere('ii.category LIKE :query')
            ->orWhere('ii.notes LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('ii.room', 'ASC')
            ->addOrderBy('ii.item', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des éléments d'inventaire
     */
    public function getStatisticsByInventory(int $inventoryId): array
    {
        $qb = $this->createQueryBuilder('ii')
            ->where('ii.inventory = :inventoryId')
            ->setParameter('inventoryId', $inventoryId);

        $total = $qb->select('COUNT(ii.id)')->getQuery()->getSingleScalarResult();

        $excellent = $qb->select('COUNT(ii.id)')
            ->andWhere('ii.condition = :condition')
            ->setParameter('condition', 'Excellent')
            ->getQuery()
            ->getSingleScalarResult();

        $bon = $qb->select('COUNT(ii.id)')
            ->andWhere('ii.condition = :condition')
            ->setParameter('condition', 'Bon')
            ->getQuery()
            ->getSingleScalarResult();

        $correct = $qb->select('COUNT(ii.id)')
            ->andWhere('ii.condition = :condition')
            ->setParameter('condition', 'Correct')
            ->getQuery()
            ->getSingleScalarResult();

        $mauvais = $qb->select('COUNT(ii.id)')
            ->andWhere('ii.condition IN (:conditions)')
            ->setParameter('conditions', ['Mauvais', 'Très mauvais', 'Endommagé'])
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => $total,
            'excellent' => $excellent,
            'bon' => $bon,
            'correct' => $correct,
            'mauvais' => $mauvais,
            'pourcentage_bon_etat' => $total > 0 ? round((($excellent + $bon) / $total) * 100, 1) : 0
        ];
    }

    /**
     * Valeur totale estimée d'un inventaire
     */
    public function getTotalEstimatedValueByInventory(int $inventoryId): float
    {
        $result = $this->createQueryBuilder('ii')
            ->select('SUM(ii.estimatedValue * ii.quantity)')
            ->where('ii.inventory = :inventoryId')
            ->andWhere('ii.estimatedValue IS NOT NULL')
            ->setParameter('inventoryId', $inventoryId)
            ->getQuery()
            ->getSingleScalarResult();

        return (float)($result ?? 0);
    }

    /**
     * Liste des pièces distinctes d'un inventaire
     */
    public function getDistinctRoomsByInventory(int $inventoryId): array
    {
        $result = $this->createQueryBuilder('ii')
            ->select('DISTINCT ii.room')
            ->where('ii.inventory = :inventoryId')
            ->setParameter('inventoryId', $inventoryId)
            ->orderBy('ii.room', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_column($result, 'room');
    }

    /**
     * Liste des catégories distinctes d'un inventaire
     */
    public function getDistinctCategoriesByInventory(int $inventoryId): array
    {
        $result = $this->createQueryBuilder('ii')
            ->select('DISTINCT ii.category')
            ->where('ii.inventory = :inventoryId')
            ->setParameter('inventoryId', $inventoryId)
            ->orderBy('ii.category', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_column($result, 'category');
    }
}
