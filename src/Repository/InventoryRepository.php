<?php

namespace App\Repository;

use App\Entity\Inventory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Inventory>
 */
class InventoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Inventory::class);
    }

    /**
     * Trouve les inventaires par type
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.type = :type')
            ->setParameter('type', $type)
            ->orderBy('i.inventoryDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les inventaires d'une propriété
     */
    public function findByProperty(int $propertyId): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.property = :propertyId')
            ->setParameter('propertyId', $propertyId)
            ->orderBy('i.inventoryDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les inventaires d'un contrat
     */
    public function findByLease(int $leaseId): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.lease = :leaseId')
            ->setParameter('leaseId', $leaseId)
            ->orderBy('i.inventoryDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve le dernier état des lieux entrant d'une propriété
     */
    public function findLastEntryInventoryByProperty(int $propertyId): ?Inventory
    {
        return $this->createQueryBuilder('i')
            ->where('i.property = :propertyId')
            ->andWhere('i.type = :type')
            ->setParameter('propertyId', $propertyId)
            ->setParameter('type', 'État des lieux entrant')
            ->orderBy('i.inventoryDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve le dernier état des lieux sortant d'une propriété
     */
    public function findLastExitInventoryByProperty(int $propertyId): ?Inventory
    {
        return $this->createQueryBuilder('i')
            ->where('i.property = :propertyId')
            ->andWhere('i.type = :type')
            ->setParameter('propertyId', $propertyId)
            ->setParameter('type', 'État des lieux sortant')
            ->orderBy('i.inventoryDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve les inventaires récents
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('i')
            ->orderBy('i.inventoryDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche dans les inventaires
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.property', 'p')
            ->where('i.performedBy LIKE :query')
            ->orWhere('i.generalNotes LIKE :query')
            ->orWhere('p.address LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('i.inventoryDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des inventaires
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('i');

        return [
            'total' => $qb->select('COUNT(i.id)')->getQuery()->getSingleScalarResult(),
            'entrants' => $qb->select('COUNT(i.id)')
                ->where('i.type = :type')
                ->setParameter('type', 'État des lieux entrant')
                ->getQuery()
                ->getSingleScalarResult(),
            'sortants' => $qb->select('COUNT(i.id)')
                ->where('i.type = :type')
                ->setParameter('type', 'État des lieux sortant')
                ->getQuery()
                ->getSingleScalarResult(),
            'periodiques' => $qb->select('COUNT(i.id)')
                ->where('i.type = :type')
                ->setParameter('type', 'Inventaire périodique')
                ->getQuery()
                ->getSingleScalarResult()
        ];
    }

    /**
     * Trouve les inventaires par période
     */
    public function findByDateRange(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.inventoryDate BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('i.inventoryDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
