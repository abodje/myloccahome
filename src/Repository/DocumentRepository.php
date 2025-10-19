<?php

namespace App\Repository;

use App\Entity\Document;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Document>
 */
class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    /**
     * Trouve les documents par type
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.type = :type')
            ->setParameter('type', $type)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les documents d'une propriété
     */
    public function findByProperty(int $propertyId): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.property = :propertyId')
            ->setParameter('propertyId', $propertyId)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les documents d'un locataire
     */
    public function findByTenant(int $tenantId): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.tenant = :tenantId')
            ->setParameter('tenantId', $tenantId)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les documents d'un contrat
     */
    public function findByLease(int $leaseId): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.lease = :leaseId')
            ->setParameter('leaseId', $leaseId)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les documents qui expirent bientôt
     */
    public function findExpiringSoon(int $days = 30): array
    {
        $date = new \DateTime();
        $date->modify("+{$days} days");

        return $this->createQueryBuilder('d')
            ->where('d.expirationDate IS NOT NULL')
            ->andWhere('d.expirationDate <= :date')
            ->andWhere('d.expirationDate > :now')
            ->andWhere('d.isArchived = false')
            ->setParameter('date', $date)
            ->setParameter('now', new \DateTime())
            ->orderBy('d.expirationDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les documents expirés
     */
    public function findExpired(): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.expirationDate IS NOT NULL')
            ->andWhere('d.expirationDate < :now')
            ->andWhere('d.isArchived = false')
            ->setParameter('now', new \DateTime())
            ->orderBy('d.expirationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche dans les documents
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.name LIKE :query')
            ->orWhere('d.originalFileName LIKE :query')
            ->orWhere('d.description LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des documents
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('d');

        return [
            'total' => $qb->select('COUNT(d.id)')->getQuery()->getSingleScalarResult(),
            'archived' => $qb->select('COUNT(d.id)')
                ->where('d.isArchived = true')
                ->getQuery()
                ->getSingleScalarResult(),
            'expiring_soon' => count($this->findExpiringSoon()),
            'expired' => count($this->findExpired())
        ];
    }

    /**
     * Trouve les documents d'un gestionnaire
     */
    public function findByManager(int $ownerId): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.property', 'p')
            ->where('p.owner = :ownerId')
            ->setParameter('ownerId', $ownerId)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les documents d'une société
     */
    public function findByCompany($company): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.property', 'p')
            ->where('p.company = :company')
            ->setParameter('company', $company)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les documents d'une organisation
     */
    public function findByOrganization($organization): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.property', 'p')
            ->where('p.organization = :organization')
            ->setParameter('organization', $organization)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
