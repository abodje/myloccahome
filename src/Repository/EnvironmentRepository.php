<?php

namespace App\Repository;

use App\Entity\Environment;
use App\Entity\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Environment>
 */
class EnvironmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Environment::class);
    }

    /**
     * Trouve les environnements d'une organisation
     */
    public function findByOrganization(Organization $organization): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.organization = :organization')
            ->setParameter('organization', $organization)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les environnements actifs d'une organisation
     */
    public function findActiveByOrganization(Organization $organization): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.organization = :organization')
            ->andWhere('e.status = :status')
            ->setParameter('organization', $organization)
            ->setParameter('status', 'ACTIVE')
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un environnement par son sous-domaine
     */
    public function findBySubdomain(string $subdomain): ?Environment
    {
        return $this->createQueryBuilder('e')
            ->where('e.subdomain = :subdomain')
            ->setParameter('subdomain', $subdomain)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve les environnements de production d'une organisation
     */
    public function findProductionByOrganization(Organization $organization): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.organization = :organization')
            ->andWhere('e.type = :type')
            ->setParameter('organization', $organization)
            ->setParameter('type', 'PRODUCTION')
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un sous-domaine est disponible
     */
    public function isSubdomainAvailable(string $subdomain): bool
    {
        $existing = $this->createQueryBuilder('e')
            ->where('e.subdomain = :subdomain')
            ->setParameter('subdomain', $subdomain)
            ->getQuery()
            ->getOneOrNullResult();

        return $existing === null;
    }

    /**
     * Trouve les environnements par statut
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.status = :status')
            ->setParameter('status', $status)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des environnements
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(e.id) as total')
            ->addSelect('SUM(CASE WHEN e.status = \'ACTIVE\' THEN 1 ELSE 0 END) as active')
            ->addSelect('SUM(CASE WHEN e.status = \'INACTIVE\' THEN 1 ELSE 0 END) as inactive')
            ->addSelect('SUM(CASE WHEN e.status = \'SUSPENDED\' THEN 1 ELSE 0 END) as suspended')
            ->addSelect('SUM(CASE WHEN e.type = \'PRODUCTION\' THEN 1 ELSE 0 END) as production')
            ->addSelect('SUM(CASE WHEN e.type = \'STAGING\' THEN 1 ELSE 0 END) as staging')
            ->addSelect('SUM(CASE WHEN e.type = \'DEVELOPMENT\' THEN 1 ELSE 0 END) as development');

        $result = $qb->getQuery()->getSingleResult();

        return [
            'total' => (int) $result['total'],
            'active' => (int) $result['active'],
            'inactive' => (int) $result['inactive'],
            'suspended' => (int) $result['suspended'],
            'production' => (int) $result['production'],
            'staging' => (int) $result['staging'],
            'development' => (int) $result['development'],
        ];
    }
}
