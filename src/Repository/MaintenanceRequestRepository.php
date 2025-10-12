<?php

namespace App\Repository;

use App\Entity\MaintenanceRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MaintenanceRequest>
 */
class MaintenanceRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MaintenanceRequest::class);
    }

    /**
     * Trouve les demandes par statut
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('mr')
            ->where('mr.status = :status')
            ->setParameter('status', $status)
            ->orderBy('mr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les demandes par priorité
     */
    public function findByPriority(string $priority): array
    {
        return $this->createQueryBuilder('mr')
            ->where('mr.priority = :priority')
            ->setParameter('priority', $priority)
            ->orderBy('mr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les demandes urgentes non terminées
     */
    public function findUrgentPending(): array
    {
        return $this->createQueryBuilder('mr')
            ->where('mr.priority = :priority')
            ->andWhere('mr.status != :status')
            ->setParameter('priority', 'Urgente')
            ->setParameter('status', 'Terminée')
            ->orderBy('mr.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les demandes en retard
     */
    public function findOverdue(): array
    {
        return $this->createQueryBuilder('mr')
            ->where('mr.scheduledDate IS NOT NULL')
            ->andWhere('mr.scheduledDate < :now')
            ->andWhere('mr.status != :status')
            ->setParameter('now', new \DateTime())
            ->setParameter('status', 'Terminée')
            ->orderBy('mr.scheduledDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les demandes d'une propriété
     */
    public function findByProperty(int $propertyId): array
    {
        return $this->createQueryBuilder('mr')
            ->where('mr.property = :propertyId')
            ->setParameter('propertyId', $propertyId)
            ->orderBy('mr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les demandes d'un locataire
     */
    public function findByTenant(int $tenantId): array
    {
        return $this->createQueryBuilder('mr')
            ->where('mr.tenant = :tenantId')
            ->setParameter('tenantId', $tenantId)
            ->orderBy('mr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les demandes assignées à un prestataire
     */
    public function findByAssignedTo(string $assignedTo): array
    {
        return $this->createQueryBuilder('mr')
            ->where('mr.assignedTo LIKE :assignedTo')
            ->setParameter('assignedTo', '%' . $assignedTo . '%')
            ->orderBy('mr.scheduledDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les demandes par catégorie
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('mr')
            ->where('mr.category = :category')
            ->setParameter('category', $category)
            ->orderBy('mr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche dans les demandes
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('mr')
            ->where('mr.title LIKE :query')
            ->orWhere('mr.description LIKE :query')
            ->orWhere('mr.assignedTo LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('mr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des demandes de maintenance
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('mr');

        return [
            'total' => $qb->select('COUNT(mr.id)')->getQuery()->getSingleScalarResult(),
            'pending' => $qb->select('COUNT(mr.id)')
                ->where('mr.status = :status')
                ->setParameter('status', 'Nouvelle')
                ->getQuery()
                ->getSingleScalarResult(),
            'nouvelles' => $qb->select('COUNT(mr.id)')
                ->where('mr.status = :status')
                ->setParameter('status', 'Nouvelle')
                ->getQuery()
                ->getSingleScalarResult(),
            'en_cours' => $qb->select('COUNT(mr.id)')
                ->where('mr.status = :status')
                ->setParameter('status', 'En cours')
                ->getQuery()
                ->getSingleScalarResult(),
            'terminees' => $qb->select('COUNT(mr.id)')
                ->where('mr.status = :status')
                ->setParameter('status', 'Terminée')
                ->getQuery()
                ->getSingleScalarResult(),
            'urgent' => count($this->findUrgentPending()),
            'urgentes' => count($this->findUrgentPending()),
            'overdue' => count($this->findOverdue()),
            'en_retard' => count($this->findOverdue())
        ];
    }

    /**
     * Coût total des maintenances par période
     */
    public function getTotalCostByPeriod(\DateTime $startDate, \DateTime $endDate): float
    {
        $result = $this->createQueryBuilder('mr')
            ->select('SUM(mr.actualCost)')
            ->where('mr.completedDate BETWEEN :start AND :end')
            ->andWhere('mr.actualCost IS NOT NULL')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->getQuery()
            ->getSingleScalarResult();

        return (float)($result ?? 0);
    }

    /**
     * Trouve les demandes avec filtres (méthode générique)
     */
    public function findWithFilters(?string $status = null, ?string $priority = null, ?string $category = null): array
    {
        $qb = $this->createQueryBuilder('mr');

        if ($status) {
            $qb->andWhere('mr.status = :status')
               ->setParameter('status', $status);
        }

        if ($priority) {
            $qb->andWhere('mr.priority = :priority')
               ->setParameter('priority', $priority);
        }

        if ($category) {
            $qb->andWhere('mr.category = :category')
               ->setParameter('category', $category);
        }

        return $qb->orderBy('mr.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Trouve les demandes d'un locataire avec filtres
     */
    public function findByTenantWithFilters(int $tenantId, ?string $status = null, ?string $priority = null, ?string $category = null): array
    {
        $qb = $this->createQueryBuilder('mr')
            ->join('mr.tenant', 't')
            ->where('t.id = :tenantId')
            ->setParameter('tenantId', $tenantId);

        if ($status) {
            $qb->andWhere('mr.status = :status')
               ->setParameter('status', $status);
        }

        if ($priority) {
            $qb->andWhere('mr.priority = :priority')
               ->setParameter('priority', $priority);
        }

        if ($category) {
            $qb->andWhere('mr.category = :category')
               ->setParameter('category', $category);
        }

        return $qb->orderBy('mr.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Trouve les demandes des propriétés d'un gestionnaire avec filtres
     */
    public function findByManagerWithFilters(int $ownerId, ?string $status = null, ?string $priority = null, ?string $category = null): array
    {
        $qb = $this->createQueryBuilder('mr')
            ->join('mr.property', 'p')
            ->where('p.owner = :ownerId')
            ->setParameter('ownerId', $ownerId);

        if ($status) {
            $qb->andWhere('mr.status = :status')
               ->setParameter('status', $status);
        }

        if ($priority) {
            $qb->andWhere('mr.priority = :priority')
               ->setParameter('priority', $priority);
        }

        if ($category) {
            $qb->andWhere('mr.category = :category')
               ->setParameter('category', $category);
        }

        return $qb->orderBy('mr.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Trouve les demandes urgentes en attente pour un gestionnaire
     */
    public function findUrgentPendingByManager(int $ownerId): array
    {
        $qb = $this->createQueryBuilder('mr')
            ->join('mr.property', 'p')
            ->join('p.owner', 'o')
            ->where('o.id = :ownerId')
            ->andWhere('mr.priority = :priority')
            ->andWhere('mr.status IN (:statuses)')
            ->setParameter('ownerId', $ownerId)
            ->setParameter('priority', 'Urgente')
            ->setParameter('statuses', ['Nouvelle', 'En cours']);

        return $qb->orderBy('mr.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Trouve les demandes en retard pour un gestionnaire
     */
    public function findOverdueByManager(int $ownerId): array
    {
        $qb = $this->createQueryBuilder('mr')
            ->join('mr.property', 'p')
            ->join('p.owner', 'o')
            ->where('o.id = :ownerId')
            ->andWhere('mr.status IN (:statuses)')
            ->andWhere('mr.createdAt < :overdueDate')
            ->setParameter('ownerId', $ownerId)
            ->setParameter('statuses', ['Nouvelle', 'En cours'])
            ->setParameter('overdueDate', new \DateTime('-7 days'));

        return $qb->orderBy('mr.createdAt', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Trouve les demandes urgentes en attente pour un locataire
     */
    public function findUrgentPendingByTenant(int $tenantId): array
    {
        $qb = $this->createQueryBuilder('mr')
            ->join('mr.tenant', 't')
            ->where('t.id = :tenantId')
            ->andWhere('mr.priority = :priority')
            ->andWhere('mr.status IN (:statuses)')
            ->setParameter('tenantId', $tenantId)
            ->setParameter('priority', 'Urgente')
            ->setParameter('statuses', ['Nouvelle', 'En cours']);

        return $qb->orderBy('mr.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Trouve les demandes en retard pour un locataire
     */
    public function findOverdueByTenant(int $tenantId): array
    {
        $qb = $this->createQueryBuilder('mr')
            ->join('mr.tenant', 't')
            ->where('t.id = :tenantId')
            ->andWhere('mr.status IN (:statuses)')
            ->andWhere('mr.createdAt < :overdueDate')
            ->setParameter('tenantId', $tenantId)
            ->setParameter('statuses', ['Nouvelle', 'En cours'])
            ->setParameter('overdueDate', new \DateTime('-7 days'));

        return $qb->orderBy('mr.createdAt', 'ASC')
                  ->getQuery()
                  ->getResult();
    }
}
