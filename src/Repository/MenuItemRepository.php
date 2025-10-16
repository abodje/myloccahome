<?php

namespace App\Repository;

use App\Entity\MenuItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MenuItem>
 */
class MenuItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MenuItem::class);
    }

    /**
     * Retourne tous les menus actifs, triés par ordre
     */
    public function findActiveMenus(): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.isActive = :active')
            ->andWhere('m.parent IS NULL')
            ->setParameter('active', true)
            ->orderBy('m.displayOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne un menu par sa clé
     */
    public function findByKey(string $key): ?MenuItem
    {
        return $this->findOneBy(['menuKey' => $key]);
    }

    /**
     * Retourne les menus principaux (sans parent)
     */
    public function findRootMenus(): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.parent IS NULL')
            ->orderBy('m.displayOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

