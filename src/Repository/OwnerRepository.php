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
