<?php

namespace App\Repository;

use App\Entity\EmailTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmailTemplate>
 */
class EmailTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailTemplate::class);
    }

    /**
     * Trouve un template par son code
     */
    public function findByCode(string $code): ?EmailTemplate
    {
        return $this->findOneBy(['code' => $code, 'isActive' => true]);
    }

    /**
     * Trouve tous les templates actifs
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('e.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les templates système
     */
    public function findSystem(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.isSystem = :system')
            ->setParameter('system', true)
            ->orderBy('e.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les templates personnalisés
     */
    public function findCustom(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.isSystem = :system')
            ->setParameter('system', false)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des templates
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('e');

        return [
            'total' => $qb->select('COUNT(e.id)')->getQuery()->getSingleScalarResult(),
            'active' => $qb->select('COUNT(e.id)')
                ->where('e.isActive = :active')
                ->setParameter('active', true)
                ->getQuery()
                ->getSingleScalarResult(),
            'system' => $qb->select('COUNT(e.id)')
                ->where('e.isSystem = :system')
                ->setParameter('system', true)
                ->getQuery()
                ->getSingleScalarResult(),
            'custom' => $qb->select('COUNT(e.id)')
                ->where('e.isSystem = :system')
                ->setParameter('system', false)
                ->getQuery()
                ->getSingleScalarResult(),
        ];
    }

    /**
     * Templates les plus utilisés
     */
    public function findMostUsed(int $limit = 5): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.usageCount > 0')
            ->orderBy('e.usageCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

