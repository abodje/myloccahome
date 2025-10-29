<?php

namespace App\Event;

use App\Entity\Organization;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event déclenché avant la création d'une ressource pour vérifier les quotas
 */
class ResourceQuotaCheckEvent extends Event
{
    private bool $allowed = true;
    private ?string $redirectRoute = null;
    private array $flashMessages = [];

    public function __construct(
        private Organization $organization,
        private string $resourceType,
        private string $redirectRouteName
    ) {
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function getResourceType(): string
    {
        return $this->resourceType;
    }

    public function getRedirectRouteName(): string
    {
        return $this->redirectRouteName;
    }

    public function isAllowed(): bool
    {
        return $this->allowed;
    }

    public function setAllowed(bool $allowed): self
    {
        $this->allowed = $allowed;
        return $this;
    }

    public function getRedirectRoute(): ?string
    {
        return $this->redirectRoute;
    }

    public function setRedirectRoute(?string $redirectRoute): self
    {
        $this->redirectRoute = $redirectRoute;
        return $this;
    }

    public function getFlashMessages(): array
    {
        return $this->flashMessages;
    }

    public function addFlashMessage(string $type, string $message): self
    {
        $this->flashMessages[] = ['type' => $type, 'message' => $message];
        return $this;
    }
}

