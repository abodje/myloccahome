<?php

namespace App\Repository;

use App\Entity\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Organization>
 */
class OrganizationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Organization::class);
    }

    /**
     * Trouve une organisation par son slug
     */
    public function findBySlug(string $slug): ?Organization
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /**
     * Trouve toutes les organisations actives
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.isActive = :active')
            ->andWhere('o.status = :status')
            ->setParameter('active', true)
            ->setParameter('status', 'ACTIVE')
            ->orderBy('o.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les organisations en période d'essai expiré
     */
    public function findExpiredTrials(): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.status = :status')
            ->andWhere('o.trialEndsAt <= :now')
            ->setParameter('status', 'TRIAL')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des organisations
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('o');

        return [
            'total' => $qb->select('COUNT(o.id)')->getQuery()->getSingleScalarResult(),
            'active' => $qb->select('COUNT(o.id)')
                ->where('o.status = :status')
                ->setParameter('status', 'ACTIVE')
                ->getQuery()
                ->getSingleScalarResult(),
            'trial' => $qb->select('COUNT(o.id)')
                ->where('o.status = :status')
                ->setParameter('status', 'TRIAL')
                ->getQuery()
                ->getSingleScalarResult(),
            'suspended' => $qb->select('COUNT(o.id)')
                ->where('o.status = :status')
                ->setParameter('status', 'SUSPENDED')
                ->getQuery()
                ->getSingleScalarResult(),
        ];
    }
}

