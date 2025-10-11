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

    public function findAvailable(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', 'available')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOccupied(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', 'occupied')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.type = :type')
            ->setParameter('type', $type)
            ->orderBy('p.rentAmount', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function searchProperties(string $query = null, string $city = null, float $minRent = null, float $maxRent = null): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($query) {
            $qb->andWhere('p.title LIKE :query OR p.description LIKE :query OR p.address LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        if ($city) {
            $qb->andWhere('p.city = :city')
               ->setParameter('city', $city);
        }

        if ($minRent !== null) {
            $qb->andWhere('p.rentAmount >= :minRent')
               ->setParameter('minRent', $minRent);
        }

        if ($maxRent !== null) {
            $qb->andWhere('p.rentAmount <= :maxRent')
               ->setParameter('maxRent', $maxRent);
        }

        return $qb->orderBy('p.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    public function getStatistics(): array
    {
        $totalProperties = $this->count([]);
        
        $statusStats = $this->createQueryBuilder('p')
            ->select('p.status, COUNT(p.id) as count')
            ->groupBy('p.status')
            ->getQuery()
            ->getResult();

        $typeStats = $this->createQueryBuilder('p')
            ->select('p.type, COUNT(p.id) as count')
            ->groupBy('p.type')
            ->getQuery()
            ->getResult();

        $averageRent = $this->createQueryBuilder('p')
            ->select('AVG(p.rentAmount) as avgRent')
            ->getQuery()
            ->getSingleScalarResult();

        $totalRentValue = $this->createQueryBuilder('p')
            ->select('SUM(p.rentAmount) as totalRent')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => $totalProperties,
            'statusStats' => $statusStats,
            'typeStats' => $typeStats,
            'averageRent' => round($averageRent ?? 0, 2),
            'totalRentValue' => $totalRentValue ?? 0,
        ];
    }
}