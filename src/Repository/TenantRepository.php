<?php

namespace App\Repository;

use App\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tenant>
 */
class TenantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tenant::class);
    }

    /**
     * Trouve tous les locataires avec leurs contrats actuels
     */
    public function findAllWithCurrentLeases(): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.leases', 'l')
            ->leftJoin('l.property', 'p')
            ->addSelect('l', 'p')
            ->orderBy('t.lastName', 'ASC')
            ->addOrderBy('t.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les locataires actifs (avec un contrat en cours)
     */
    public function findActive(): array
    {
        $qb = $this->createQueryBuilder('t');
        
        return $qb
            ->innerJoin('t.leases', 'l', 'WITH', 
                $qb->expr()->andX(
                    $qb->expr()->lte('l.startDate', ':now'),
                    $qb->expr()->orX(
                        $qb->expr()->isNull('l.endDate'),
                        $qb->expr()->gte('l.endDate', ':now')
                    )
                )
            )
            ->setParameter('now', new \DateTime())
            ->orderBy('t.lastName', 'ASC')
            ->addOrderBy('t.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les anciens locataires (sans contrat actuel)
     */
    public function findFormer(): array
    {
        $qb = $this->createQueryBuilder('t');
        
        return $qb
            ->leftJoin('t.leases', 'l', 'WITH', 
                $qb->expr()->andX(
                    $qb->expr()->lte('l.startDate', ':now'),
                    $qb->expr()->orX(
                        $qb->expr()->isNull('l.endDate'),
                        $qb->expr()->gte('l.endDate', ':now')
                    )
                )
            )
            ->where($qb->expr()->isNull('l.id'))
            ->setParameter('now', new \DateTime())
            ->orderBy('t.lastName', 'ASC')
            ->addOrderBy('t.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche par nom ou email
     */
    public function findByNameOrEmail(string $search): array
    {
        $qb = $this->createQueryBuilder('t');
        
        return $qb
            ->where($qb->expr()->orX(
                $qb->expr()->like('t.firstName', ':search'),
                $qb->expr()->like('t.lastName', ':search'),
                $qb->expr()->like('t.email', ':search')
            ))
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('t.lastName', 'ASC')
            ->addOrderBy('t.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un email est déjà utilisé
     */
    public function isEmailExists(string $email, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.email = :email')
            ->setParameter('email', $email);

        if ($excludeId) {
            $qb->andWhere('t.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Statistiques des locataires
     */
    public function getStatistics(): array
    {
        $total = $this->count([]);
        $active = count($this->findActive());
        $former = count($this->findFormer());

        return [
            'total' => $total,
            'active' => $active,
            'former' => $former
        ];
    }
}