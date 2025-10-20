<?php

namespace App\EventListener;

use App\Entity\Lease;
use App\Entity\Property;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityManagerInterface;

/**
 * EventListener pour mettre à jour automatiquement le statut des propriétés
 * selon leur occupation (présence d'un bail actif)
 */
class PropertyStatusListener
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Appelé après la création d'un bail
     */
    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof Lease) {
            $this->updatePropertyStatus($entity->getProperty());
        }
    }

    /**
     * Appelé après la modification d'un bail
     */
    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof Lease) {
            $this->updatePropertyStatus($entity->getProperty());
        }
    }

    /**
     * Appelé après la suppression d'un bail
     */
    public function postRemove(PostRemoveEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof Lease) {
            $this->updatePropertyStatus($entity->getProperty());
        }
    }

    /**
     * Met à jour le statut d'une propriété selon son occupation
     */
    private function updatePropertyStatus(?Property $property): void
    {
        if (!$property) {
            return;
        }

        // Vérifier s'il y a un bail actif pour cette propriété
        $hasActiveLease = $this->hasActiveLease($property);

        // Mettre à jour le statut de la propriété
        if ($hasActiveLease) {
            $property->setStatus('Occupé');
        } else {
            $property->setStatus('Libre');
        }

        // Sauvegarder les changements
        $this->entityManager->persist($property);
        $this->entityManager->flush();
    }

    /**
     * Vérifie si une propriété a un bail actif
     */
    private function hasActiveLease(Property $property): bool
    {
        $now = new \DateTime();

        foreach ($property->getLeases() as $lease) {
            // Un bail est actif s'il a commencé et n'a pas encore fini
            if ($lease->getStartDate() <= $now &&
                ($lease->getEndDate() === null || $lease->getEndDate() >= $now) &&
                $lease->getStatus() === 'Actif') {
                return true;
            }
        }

        return false;
    }
}
