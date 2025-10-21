<?php

namespace App\Repository;

use App\Entity\ContractConfig;
use App\Entity\Organization;
use App\Entity\Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContractConfig>
 */
class ContractConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContractConfig::class);
    }

    /**
     * Trouve la configuration de contrat pour une organisation et société donnée
     */
    public function findByOrganizationAndCompany(?Organization $organization, ?Company $company = null): ?ContractConfig
    {
        $qb = $this->createQueryBuilder('cc')
            ->where('cc.organization = :organization')
            ->setParameter('organization', $organization);

        if ($company) {
            $qb->andWhere('cc.company = :company')
               ->setParameter('company', $company);
        } else {
            $qb->andWhere('cc.company IS NULL');
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Trouve toutes les configurations pour une organisation
     */
    public function findByOrganization(Organization $organization): array
    {
        return $this->createQueryBuilder('cc')
            ->where('cc.organization = :organization')
            ->setParameter('organization', $organization)
            ->orderBy('cc.company', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve toutes les configurations pour une société
     */
    public function findByCompany(Company $company): array
    {
        return $this->createQueryBuilder('cc')
            ->where('cc.company = :company')
            ->setParameter('company', $company)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve la configuration par défaut d'une organisation (sans société spécifique)
     */
    public function findDefaultByOrganization(Organization $organization): ?ContractConfig
    {
        return $this->createQueryBuilder('cc')
            ->where('cc.organization = :organization')
            ->andWhere('cc.company IS NULL')
            ->setParameter('organization', $organization)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Crée une configuration par défaut pour une organisation
     */
    public function createDefaultForOrganization(Organization $organization): ContractConfig
    {
        $config = new ContractConfig();
        $config->setOrganization($organization);
        $config->setCompany(null); // Configuration par défaut de l'organisation
        
        // Utiliser les informations de l'organisation si disponibles
        if ($organization->getName()) {
            $config->setContractCompanyName($organization->getName());
        }
        if ($organization->getAddress()) {
            $config->setContractCompanyAddress($organization->getAddress());
        }

        return $config;
    }

    /**
     * Crée une configuration pour une société spécifique
     */
    public function createForCompany(Organization $organization, Company $company): ContractConfig
    {
        $config = new ContractConfig();
        $config->setOrganization($organization);
        $config->setCompany($company);
        
        // Utiliser les informations de la société si disponibles
        if ($company->getName()) {
            $config->setContractCompanyName($company->getName());
        }
        if ($company->getAddress()) {
            $config->setContractCompanyAddress($company->getAddress());
        }

        return $config;
    }

    /**
     * Trouve ou crée la configuration appropriée pour une organisation/société
     */
    public function findOrCreateForOrganizationAndCompany(?Organization $organization, ?Company $company = null): ContractConfig
    {
        if (!$organization) {
            throw new \InvalidArgumentException('L\'organisation est requise');
        }

        // Chercher d'abord une configuration existante
        $existing = $this->findByOrganizationAndCompany($organization, $company);
        if ($existing) {
            return $existing;
        }

        // Si pas trouvé, créer une nouvelle configuration
        if ($company) {
            return $this->createForCompany($organization, $company);
        } else {
            return $this->createDefaultForOrganization($organization);
        }
    }
}
