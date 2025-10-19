<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Organization;
use App\Entity\User;

class DemoDataFilterService
{
    private RequestStack $requestStack;
    private EntityManagerInterface $entityManager;
    private ?string $demoCode = null;
    private ?Organization $demoOrganization = null;
    private ?User $demoUser = null;

    public function __construct(RequestStack $requestStack, EntityManagerInterface $entityManager)
    {
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
        $this->initializeDemoContext();
    }

    /**
     * Initialise le contexte de démo
     */
    private function initializeDemoContext(): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return;
        }

        // Vérifier si on est dans un environnement de démo
        if ($request->attributes->get('_demo_environment')) {
            $this->demoCode = $request->attributes->get('_demo_code');

            if ($this->demoCode) {
                // Récupérer l'organisation de démo
                $this->demoOrganization = $this->entityManager->getRepository(Organization::class)
                    ->findOneBy(['subdomain' => $this->demoCode, 'isDemo' => true]);

                if ($this->demoOrganization) {
                    $this->demoUser = $this->entityManager->getRepository(User::class)
                        ->findOneBy(['organization' => $this->demoOrganization]);
                }
            }
        }
    }

    /**
     * Vérifie si on est dans un environnement de démo
     */
    public function isDemoEnvironment(): bool
    {
        return $this->demoCode !== null && $this->demoOrganization !== null;
    }

    /**
     * Retourne le code de démo actuel
     */
    public function getDemoCode(): ?string
    {
        return $this->demoCode;
    }

    /**
     * Retourne l'organisation de démo
     */
    public function getDemoOrganization(): ?Organization
    {
        return $this->demoOrganization;
    }

    /**
     * Retourne l'utilisateur de démo
     */
    public function getDemoUser(): ?User
    {
        return $this->demoUser;
    }

    /**
     * Filtre une requête DQL pour n'inclure que les données de la démo
     */
    public function filterQueryBuilder($queryBuilder, string $alias = 'o'): void
    {
        if ($this->isDemoEnvironment()) {
            $queryBuilder->andWhere("{$alias}.organization = :demo_organization")
                         ->setParameter('demo_organization', $this->demoOrganization);
        }
    }

    /**
     * Filtre une liste d'entités pour n'inclure que celles de la démo
     */
    public function filterEntities(array $entities): array
    {
        if (!$this->isDemoEnvironment()) {
            return $entities;
        }

        return array_filter($entities, function($entity) {
            if (method_exists($entity, 'getOrganization')) {
                return $entity->getOrganization() === $this->demoOrganization;
            }
            return true; // Si pas d'organisation, on garde l'entité
        });
    }

    /**
     * Ajoute les conditions de filtrage de démo à une requête
     */
    public function addDemoFilter($queryBuilder, string $entityAlias = 'e'): void
    {
        if ($this->isDemoEnvironment()) {
            $queryBuilder->andWhere("{$entityAlias}.organization = :demo_organization")
                         ->setParameter('demo_organization', $this->demoOrganization);
        }
    }
}
