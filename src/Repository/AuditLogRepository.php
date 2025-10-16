<?php

namespace App\Repository;

use App\Entity\AuditLog;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditLog>
 */
class AuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }

    /**
     * Recherche avec filtres
     */
    public function findWithFilters(
        ?User $user = null,
        ?string $action = null,
        ?string $entityType = null,
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null,
        ?int $limit = 100
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC');

        if ($user) {
            $qb->andWhere('a.user = :user')
               ->setParameter('user', $user);
        }

        if ($action) {
            $qb->andWhere('a.action = :action')
               ->setParameter('action', $action);
        }

        if ($entityType) {
            $qb->andWhere('a.entityType = :entityType')
               ->setParameter('entityType', $entityType);
        }

        if ($startDate) {
            $qb->andWhere('a.createdAt >= :startDate')
               ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('a.createdAt <= :endDate')
               ->setParameter('endDate', $endDate);
        }

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Logs pour une entité spécifique
     */
    public function findByEntity(string $entityType, int $entityId, ?int $limit = 50): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.entityType = :entityType')
            ->andWhere('a.entityId = :entityId')
            ->setParameter('entityType', $entityType)
            ->setParameter('entityId', $entityId)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques d'activité
     */
    public function getActivityStats(\DateTime $startDate, \DateTime $endDate): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('a.action, COUNT(a.id) as count')
            ->where('a.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('a.action');

        return $qb->getQuery()->getResult();
    }

    /**
     * Activité récente d'un utilisateur
     */
    public function findRecentByUser(User $user, int $limit = 20): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.user = :user')
            ->setParameter('user', $user)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Actions par type d'entité
     */
    public function countByEntityType(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.entityType, COUNT(a.id) as count')
            ->where('a.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('a.entityType')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Utilisateurs les plus actifs
     */
    public function getMostActiveUsers(int $limit = 10): array
    {
        return $this->createQueryBuilder('a')
            ->select('IDENTITY(a.user) as userId, COUNT(a.id) as actionCount')
            ->where('a.user IS NOT NULL')
            ->groupBy('a.user')
            ->orderBy('actionCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Nettoyage des vieux logs
     */
    public function deleteOlderThan(\DateTime $date): int
    {
        return $this->createQueryBuilder('a')
            ->delete()
            ->where('a.createdAt < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }

    /**
     * Compte le nombre total de logs
     */
    public function countAll(): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Logs d'aujourd'hui
     */
    public function findToday(): array
    {
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');

        return $this->createQueryBuilder('a')
            ->where('a.createdAt >= :today')
            ->andWhere('a.createdAt < :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

