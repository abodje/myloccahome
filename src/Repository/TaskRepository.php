<?php

namespace App\Repository;

use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    /**
     * Trouve les tâches qui doivent être exécutées
     */
    public function findDueTasks(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->andWhere('t.nextRunAt IS NOT NULL')
            ->andWhere('t.nextRunAt <= :now')
            ->setParameter('status', 'ACTIVE')
            ->setParameter('now', new \DateTime())
            ->orderBy('t.nextRunAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les tâches par type
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.type = :type')
            ->setParameter('type', $type)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les tâches actives
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->setParameter('status', 'ACTIVE')
            ->orderBy('t.nextRunAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les tâches en cours d'exécution
     */
    public function findRunning(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->setParameter('status', 'RUNNING')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les tâches qui ont échoué récemment
     */
    public function findRecentFailures(int $hours = 24): array
    {
        $since = new \DateTime("-{$hours} hours");

        return $this->createQueryBuilder('t')
            ->where('t.failureCount > 0')
            ->andWhere('t.lastRunAt >= :since')
            ->andWhere('t.lastError IS NOT NULL')
            ->setParameter('since', $since)
            ->orderBy('t.lastRunAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des tâches
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('t');

        return [
            'total' => $qb->select('COUNT(t.id)')->getQuery()->getSingleScalarResult(),
            'active' => $qb->select('COUNT(t.id)')
                ->where('t.status = :status')
                ->setParameter('status', 'ACTIVE')
                ->getQuery()
                ->getSingleScalarResult(),
            'inactive' => $qb->select('COUNT(t.id)')
                ->where('t.status = :status')
                ->setParameter('status', 'INACTIVE')
                ->getQuery()
                ->getSingleScalarResult(),
            'running' => $qb->select('COUNT(t.id)')
                ->where('t.status = :status')
                ->setParameter('status', 'RUNNING')
                ->getQuery()
                ->getSingleScalarResult(),
            'due' => count($this->findDueTasks()),
            'recent_failures' => count($this->findRecentFailures()),
        ];
    }

    /**
     * Trouve les tâches avec le plus d'échecs
     */
    public function findMostFailedTasks(int $limit = 5): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.failureCount > 0')
            ->orderBy('t.failureCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les tâches les plus exécutées
     */
    public function findMostExecutedTasks(int $limit = 5): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.runCount > 0')
            ->orderBy('t.runCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Nettoie les anciennes tâches terminées
     */
    public function cleanupOldTasks(int $daysOld = 30): int
    {
        $cutoffDate = new \DateTime("-{$daysOld} days");

        $result = $this->createQueryBuilder('t')
            ->delete()
            ->where('t.status = :status')
            ->andWhere('t.lastRunAt < :cutoff')
            ->setParameter('status', 'COMPLETED')
            ->setParameter('cutoff', $cutoffDate)
            ->getQuery()
            ->execute();

        return $result;
    }
}
