<?php

namespace App\EventSubscriber;

use App\Entity\Company;
use App\Entity\Organization;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Filtre automatique des données par Organization et Company
 * selon l'utilisateur connecté
 */
#[AsDoctrineListener(event: Events::onFlush)]
class CompanyFilterSubscriber
{
    private const FILTERED_ENTITIES = [
        'App\Entity\Property',
        'App\Entity\Tenant',
        'App\Entity\Lease',
        'App\Entity\Payment',
        'App\Entity\Expense',
        'App\Entity\MaintenanceRequest',
        'App\Entity\Document',
        'App\Entity\AccountingEntry',
    ];

    public function __construct(
        private Security $security
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        /** @var User|null $user */
        $user = $this->security->getUser();

        // Pas d'utilisateur connecté ou pas de méthode getOrganization
        if (!$user || !method_exists($user, 'getOrganization')) {
            return;
        }

        $organization = $user->getOrganization();
        if (!$organization) {
            return;
        }

        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        // Pour toutes les entités en attente d'insertion
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $className = get_class($entity);

            if (!in_array($className, self::FILTERED_ENTITIES)) {
                continue;
            }

            // Définir automatiquement l'organization si la méthode existe
            if (method_exists($entity, 'setOrganization') && method_exists($entity, 'getOrganization')) {
                if (!$entity->getOrganization()) {
                    $entity->setOrganization($organization);
                }
            }

            // Définir automatiquement la company si l'utilisateur en a une et que l'entité le supporte
            if (method_exists($entity, 'setCompany') &&
                method_exists($entity, 'getCompany') &&
                method_exists($user, 'getCompany')) {

                $userCompany = $user->getCompany();
                if ($userCompany && !$entity->getCompany()) {
                    $entity->setCompany($userCompany);
                }
            }

            // Recalculer le changeset après modification
            $classMetadata = $em->getClassMetadata($className);
            $uow->recomputeSingleEntityChangeSet($classMetadata, $entity);
        }
    }
}

