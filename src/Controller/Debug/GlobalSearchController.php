<?php

namespace App\Controller\Debug;

use App\Service\GlobalSearchService;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/debug/global-search')]
class GlobalSearchController extends AbstractController
{
    public function __construct(
        private GlobalSearchService $globalSearchService
    ) {
    }

    /**
     * Route de debug pour tester la recherche globale
     */
    #[Route('/test', name: 'app_debug_global_search_test', methods: ['GET'])]
    public function testSearch(Request $request): JsonResponse
    {
        $query = $request->query->get('q', 'test');
        $limit = (int) $request->query->get('limit', 10);

        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated']);
        }

        // Informations sur l'utilisateur
        $userInfo = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'organization' => $user->getOrganization() ? [
                'id' => $user->getOrganization()->getId(),
                'name' => $user->getOrganization()->getName(),
            ] : null,
            'company' => $user->getCompany() ? [
                'id' => $user->getCompany()->getId(),
                'name' => $user->getCompany()->getName(),
            ] : null,
            'tenant' => $user->getTenant() ? [
                'id' => $user->getTenant()->getId(),
                'fullName' => $user->getTenant()->getFullName(),
            ] : null,
            'owner' => $user->getOwner() ? [
                'id' => $user->getOwner()->getId(),
                'fullName' => $user->getOwner()->getFullName(),
            ] : null,
        ];

        // Effectuer la recherche
        $searchResults = $this->globalSearchService->search($query, $limit);

        // Statistiques
        $stats = $this->globalSearchService->getSearchStats($searchResults);

        return new JsonResponse([
            'user_info' => $userInfo,
            'search_query' => $query,
            'search_limit' => $limit,
            'search_results' => $searchResults,
            'stats' => $stats,
            'filter_description' => $this->getFilterDescription($user),
        ]);
    }

    /**
     * Route pour tester la recherche rapide (autocomplete)
     */
    #[Route('/quick', name: 'app_debug_global_search_quick', methods: ['GET'])]
    public function testQuickSearch(Request $request): JsonResponse
    {
        $query = $request->query->get('q', 'test');
        $limit = (int) $request->query->get('limit', 5);

        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated']);
        }

        // Effectuer la recherche rapide
        $quickResults = $this->globalSearchService->quickSearch($query, $limit);

        return new JsonResponse([
            'user_info' => [
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'organization' => $user->getOrganization() ? $user->getOrganization()->getName() : null,
                'company' => $user->getCompany() ? $user->getCompany()->getName() : null,
            ],
            'search_query' => $query,
            'quick_results' => $quickResults,
        ]);
    }

    /**
     * Génère une description du filtrage appliqué
     */
    private function getFilterDescription($user): string
    {
        $roles = $user->getRoles();

        if (in_array('ROLE_TENANT', $roles)) {
            return 'LOCATAIRE : Voit uniquement ses propres données';
        } elseif (in_array('ROLE_MANAGER', $roles)) {
            $company = $user->getCompany();
            $organization = $user->getOrganization();

            if ($company) {
                return 'MANAGER : Voit les données de la société "' . $company->getName() . '"';
            } elseif ($organization) {
                return 'MANAGER : Voit les données de l\'organisation "' . $organization->getName() . '"';
            } else {
                return 'MANAGER : Voit ses propres données (via owner)';
            }
        } elseif (in_array('ROLE_ADMIN', $roles)) {
            $company = $user->getCompany();
            $organization = $user->getOrganization();

            if ($company) {
                return 'ADMIN : Voit les données de la société "' . $company->getName() . '"';
            } elseif ($organization) {
                return 'ADMIN : Voit toutes les données de l\'organisation "' . $organization->getName() . '"';
            } else {
                return 'SUPER ADMIN : Voit toutes les données';
            }
        }

        return 'Utilisateur sans rôle spécifique : Aucun accès';
    }
}
