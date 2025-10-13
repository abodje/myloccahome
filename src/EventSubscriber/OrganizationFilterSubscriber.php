<?php

namespace App\EventSubscriber;

use App\Entity\Organization;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Doctrine\ORM\Query\Filter\SQLFilter;

/**
 * Filtre automatique des données par organisation (Multi-tenant)
 */
class OrganizationFilterSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();
        
        if (!$user || !method_exists($user, 'getOrganization')) {
            return;
        }

        $organization = $user->getOrganization();
        
        if (!$organization) {
            return;
        }

        // Activer le filtre Doctrine pour cette organisation
        $filters = $this->entityManager->getFilters();
        
        if (!$filters->isEnabled('organization_filter')) {
            $filter = $filters->enable('organization_filter');
            
            if ($filter instanceof OrganizationFilter) {
                $filter->setOrganizationId($organization->getId());
            }
        }
    }
}

/**
 * Filtre SQL Doctrine pour le multi-tenant
 */
class OrganizationFilter extends SQLFilter
{
    private ?int $organizationId = null;

    public function setOrganizationId(int $organizationId): void
    {
        $this->organizationId = $organizationId;
    }

    public function addFilterConstraint($targetEntity, $targetTableAlias): string
    {
        // Liste des entités à filtrer
        $filteredEntities = [
            'App\Entity\Property',
            'App\Entity\Tenant',
            'App\Entity\Lease',
            'App\Entity\Payment',
            'App\Entity\Document',
            'App\Entity\MaintenanceRequest',
            'App\Entity\Expense',
            'App\Entity\AccountingEntry',
        ];

        if (!in_array($targetEntity->getName(), $filteredEntities)) {
            return '';
        }

        if ($this->organizationId === null) {
            return '';
        }

        return sprintf('%s.organization_id = %d', $targetTableAlias, $this->organizationId);
    }
}

