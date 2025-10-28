<?php

namespace App\Repository;

use App\Entity\VisitSlot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VisitSlot>
 */
class VisitSlotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VisitSlot::class);
    }

    /**
     * Trouve les créneaux disponibles pour une propriété
     */
    public function findAvailableForProperty(int $propertyId, ?\DateTimeInterface $fromDate = null): array
    {
        $qb = $this->createQueryBuilder('vs')
            ->where('vs.property = :propertyId')
            ->andWhere('vs.status = :status')
            ->andWhere('vs.currentVisitors < vs.maxVisitors')
            ->setParameter('propertyId', $propertyId)
            ->setParameter('status', 'available')
            ->orderBy('vs.startTime', 'ASC');

        if ($fromDate) {
            $qb->andWhere('vs.startTime >= :fromDate')
                ->setParameter('fromDate', $fromDate);
        } else {
            $qb->andWhere('vs.startTime >= :now')
                ->setParameter('now', new \DateTime());
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les créneaux à venir pour une organisation
     */
    public function findUpcomingForOrganization(int $organizationId, int $limit = 10): array
    {
        return $this->createQueryBuilder('vs')
            ->where('vs.organization = :organizationId')
            ->andWhere('vs.startTime >= :now')
            ->setParameter('organizationId', $organizationId)
            ->setParameter('now', new \DateTime())
            ->orderBy('vs.startTime', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
