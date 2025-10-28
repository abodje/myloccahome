<?php

namespace App\Repository;

use App\Entity\TenantApplication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TenantApplication>
 */
class TenantApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TenantApplication::class);
    }

    /**
     * Trouve les candidatures pour une propriété, triées par score
     */
    public function findByPropertyOrderedByScore(int $propertyId): array
    {
        return $this->createQueryBuilder('ta')
            ->where('ta.property = :propertyId')
            ->setParameter('propertyId', $propertyId)
            ->orderBy('ta.score', 'DESC')
            ->addOrderBy('ta.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les candidatures en attente pour une organisation
     */
    public function findPendingForOrganization(int $organizationId): array
    {
        return $this->createQueryBuilder('ta')
            ->where('ta.organization = :organizationId')
            ->andWhere('ta.status = :status')
            ->setParameter('organizationId', $organizationId)
            ->setParameter('status', 'pending')
            ->orderBy('ta.score', 'DESC')
            ->addOrderBy('ta.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des candidatures par statut
     */
    public function getStatsByStatus(int $organizationId): array
    {
        $results = $this->createQueryBuilder('ta')
            ->select('ta.status, COUNT(ta.id) as count')
            ->where('ta.organization = :organizationId')
            ->setParameter('organizationId', $organizationId)
            ->groupBy('ta.status')
            ->getQuery()
            ->getResult();

        $stats = [];
        foreach ($results as $result) {
            $stats[$result['status']] = (int)$result['count'];
        }

        return $stats;
    }
}
