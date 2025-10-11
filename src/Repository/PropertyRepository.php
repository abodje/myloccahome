<?php

namespace App\Repository;

use App\Entity\Property;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Property>
 */
class PropertyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Property::class);
    }

    /**
     * @return Property[] Returns an array of available properties
     */
    public function findAvailableProperties(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.available = :available')
            ->setParameter('available', true)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Property[] Returns an array of properties by type
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.type = :type')
            ->setParameter('type', $type)
            ->orderBy('p.monthlyRent', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Property[] Returns properties within price range
     */
    public function findByPriceRange(float $minPrice, float $maxPrice): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.monthlyRent >= :minPrice')
            ->andWhere('p.monthlyRent <= :maxPrice')
            ->setParameter('minPrice', $minPrice)
            ->setParameter('maxPrice', $maxPrice)
            ->orderBy('p.monthlyRent', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Property[] Returns properties by city
     */
    public function findByCity(string $city): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.city LIKE :city')
            ->setParameter('city', '%' . $city . '%')
            ->orderBy('p.monthlyRent', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getPropertyStats(): array
    {
        return $this->createQueryBuilder('p')
            ->select([
                'COUNT(p.id) as total',
                'SUM(CASE WHEN p.available = true THEN 1 ELSE 0 END) as available',
                'SUM(CASE WHEN p.available = false THEN 1 ELSE 0 END) as occupied',
                'AVG(p.monthlyRent) as averageRent'
            ])
            ->getQuery()
            ->getSingleResult();
    }
}