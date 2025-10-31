<?php

namespace App\Repository;

use App\Entity\Owner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Owner>
 */
class OwnerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Owner::class);
    }

    /**
     * Trouve les propriétaires avec leurs propriétés actives
     */
    public function findWithActiveProperties(): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.properties', 'p')
            ->leftJoin('p.leases', 'l')
            ->where('l.status = :status')
            ->setParameter('status', 'Actif')
            ->groupBy('o.id')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche par nom ou email
     */
    public function findByNameOrEmail(string $search): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.firstName LIKE :search')
            ->orWhere('o.lastName LIKE :search')
            ->orWhere('o.email LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('o.lastName', 'ASC')
            ->addOrderBy('o.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les propriétaires par type
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.ownerType = :type')
            ->setParameter('type', $type)
            ->orderBy('o.lastName', 'ASC')
            ->addOrderBy('o.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les propriétaires avec filtres
     */
    public function findAllFiltered($organization = null, $company = null, ?string $search = null, ?string $type = null): array
    {
        $qb = $this->createQueryBuilder('o');

        // Filtrer par société en priorité, puis par organisation
        if ($company) {
            $qb->where('o.company = :company')
               ->setParameter('company', $company);
        } elseif ($organization) {
            $qb->where('o.organization = :organization')
               ->setParameter('organization', $organization);
        }

        if ($search) {
            $qb->andWhere('o.firstName LIKE :search OR o.lastName LIKE :search OR o.email LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($type) {
            $qb->andWhere('o.ownerType = :type')
               ->setParameter('type', $type);
        }

        return $qb->orderBy('o.lastName', 'ASC')
                  ->addOrderBy('o.firstName', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Statistiques des propriétaires
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('o');

        return [
            'total' => $qb->select('COUNT(o.id)')->getQuery()->getSingleScalarResult(),
            'particuliers' => $qb->select('COUNT(o.id)')
                ->where('o.ownerType = :type')
                ->setParameter('type', 'Particulier')
                ->getQuery()
                ->getSingleScalarResult(),
            'societes' => $qb->select('COUNT(o.id)')
                ->where('o.ownerType != :type')
                ->setParameter('type', 'Particulier')
                ->getQuery()
                ->getSingleScalarResult()
        ];
    }
}
