<?php

namespace App\Repository;

use App\Entity\Company;
use App\Entity\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Company>
 */
class CompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Company::class);
    }

    /**
     * Trouve toutes les sociétés d'une organization
     */
    public function findByOrganization(Organization $organization): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.organization = :organization')
            ->setParameter('organization', $organization)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les sociétés actives d'une organization
     */
    public function findActiveByOrganization(Organization $organization): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.organization = :organization')
            ->andWhere('c.status = :status')
            ->setParameter('organization', $organization)
            ->setParameter('status', 'ACTIVE')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les sociétés d'une organization
     */
    public function countByOrganization(Organization $organization): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.organization = :organization')
            ->setParameter('organization', $organization)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve le siège social d'une organization
     */
    public function findHeadquarter(Organization $organization): ?Company
    {
        return $this->createQueryBuilder('c')
            ->where('c.organization = :organization')
            ->andWhere('c.isHeadquarter = :isHeadquarter')
            ->setParameter('organization', $organization)
            ->setParameter('isHeadquarter', true)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

