<?php

namespace App\Repository;

use App\Entity\AccountingConfiguration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccountingConfiguration>
 */
class AccountingConfigurationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccountingConfiguration::class);
    }

    /**
     * Trouve une configuration par type d'opération
     */
    public function findByOperationType(string $operationType): ?AccountingConfiguration
    {
        return $this->createQueryBuilder('ac')
            ->andWhere('ac.operationType = :operationType')
            ->andWhere('ac.isActive = :active')
            ->setParameter('operationType', $operationType)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve toutes les configurations actives
     */
    public function findActiveConfigurations(): array
    {
        return $this->createQueryBuilder('ac')
            ->andWhere('ac.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('ac.operationType', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les configurations par catégorie
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('ac')
            ->andWhere('ac.category = :category')
            ->andWhere('ac.isActive = :active')
            ->setParameter('category', $category)
            ->setParameter('active', true)
            ->orderBy('ac.operationType', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve une configuration par numéro de compte
     */
    public function findByAccountNumber(string $accountNumber): ?AccountingConfiguration
    {
        return $this->createQueryBuilder('ac')
            ->andWhere('ac.accountNumber = :accountNumber')
            ->andWhere('ac.isActive = :active')
            ->setParameter('accountNumber', $accountNumber)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve une configuration par type d'opération et organisation
     */
    public function findByOperationTypeAndOrganization(string $operationType, ?int $organizationId = null, ?int $companyId = null): ?AccountingConfiguration
    {
        $qb = $this->createQueryBuilder('ac')
            ->andWhere('ac.operationType = :operationType')
            ->andWhere('ac.isActive = :active')
            ->setParameter('operationType', $operationType)
            ->setParameter('active', true);

        if ($companyId) {
            $qb->andWhere('ac.company = :companyId')
               ->setParameter('companyId', $companyId);
        } elseif ($organizationId) {
            $qb->andWhere('ac.organization = :organizationId')
               ->setParameter('organizationId', $organizationId);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Trouve toutes les configurations actives par organisation
     */
    public function findActiveConfigurationsByOrganization(?int $organizationId = null, ?int $companyId = null): array
    {
        $qb = $this->createQueryBuilder('ac')
            ->andWhere('ac.isActive = :active')
            ->setParameter('active', true);

        if ($companyId) {
            $qb->andWhere('ac.company = :companyId')
               ->setParameter('companyId', $companyId);
        } elseif ($organizationId) {
            $qb->andWhere('ac.organization = :organizationId')
               ->setParameter('organizationId', $organizationId);
        }

        return $qb->orderBy('ac.operationType', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Trouve les configurations par catégorie et organisation
     */
    public function findByCategoryAndOrganization(string $category, ?int $organizationId = null, ?int $companyId = null): array
    {
        $qb = $this->createQueryBuilder('ac')
            ->andWhere('ac.category = :category')
            ->andWhere('ac.isActive = :active')
            ->setParameter('category', $category)
            ->setParameter('active', true);

        if ($companyId) {
            $qb->andWhere('ac.company = :companyId')
               ->setParameter('companyId', $companyId);
        } elseif ($organizationId) {
            $qb->andWhere('ac.organization = :organizationId')
               ->setParameter('organizationId', $organizationId);
        }

        return $qb->orderBy('ac.operationType', 'ASC')
                  ->getQuery()
                  ->getResult();
    }
}
