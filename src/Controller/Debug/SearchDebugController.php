<?php

namespace App\Controller\Debug;

use App\Service\GlobalSearchService;
use App\Repository\PropertyRepository;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/debug/search')]
class SearchDebugController extends AbstractController
{
    public function __construct(
        private GlobalSearchService $globalSearchService,
        private PropertyRepository $propertyRepository
    ) {
    }

    #[Route('/properties', name: 'app_debug_search_properties', methods: ['GET'])]
    public function debugProperties(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        $query = $request->query->get('q', '');

        // 1. Récupérer TOUTES les propriétés en base
        $allProperties = $this->propertyRepository->findAll();

        // 2. Test de recherche sans filtrage
        $qb = $this->propertyRepository->createQueryBuilder('prop')
            ->where('prop.address LIKE :query')
            ->orWhere('prop.city LIKE :query')
            ->orWhere('prop.description LIKE :query')
            ->setParameter('query', '%' . $query . '%');

        $unfilteredResults = $qb->getQuery()->getResult();

        // 3. Test avec le service de recherche globale
        $globalSearchResults = $this->globalSearchService->search($query, 50);

        // 4. Informations sur l'utilisateur
        $userInfo = null;
        if ($user) {
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
                    'name' => $user->getTenant()->getFullName(),
                ] : null,
                'owner' => $user->getOwner() ? [
                    'id' => $user->getOwner()->getId(),
                    'name' => $user->getOwner()->getFullName(),
                ] : null,
            ];
        }

        return new JsonResponse([
            'query' => $query,
            'user_info' => $userInfo,
            'all_properties_count' => count($allProperties),
            'all_properties' => array_map(function($prop) {
                return [
                    'id' => $prop->getId(),
                    'address' => $prop->getAddress(),
                    'city' => $prop->getCity(),
                    'organization' => $prop->getOrganization() ? $prop->getOrganization()->getName() : null,
                    'company' => $prop->getCompany() ? $prop->getCompany()->getName() : null,
                    'owner' => $prop->getOwner() ? $prop->getOwner()->getFullName() : null,
                ];
            }, $allProperties),
            'unfiltered_results_count' => count($unfilteredResults),
            'unfiltered_results' => array_map(function($prop) {
                return [
                    'id' => $prop->getId(),
                    'address' => $prop->getAddress(),
                    'city' => $prop->getCity(),
                ];
            }, $unfilteredResults),
            'global_search_results' => $globalSearchResults,
        ]);
    }

    #[Route('/test-tenant', name: 'app_debug_search_test_tenant', methods: ['GET'])]
    public function testTenantSearch(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user || !$user->getTenant()) {
            return new JsonResponse(['error' => 'Utilisateur non connecté ou pas de profil locataire']);
        }

        $tenant = $user->getTenant();

        // Test direct des baux du locataire
        $qb = $this->propertyRepository->createQueryBuilder('prop')
            ->leftJoin('prop.leases', 'leases')
            ->where('leases.tenant = :tenant')
            ->setParameter('tenant', $tenant);

        $tenantProperties = $qb->getQuery()->getResult();

        // Test de recherche globale
        $globalResults = $this->globalSearchService->search('test', 50);

        return new JsonResponse([
            'tenant_id' => $tenant->getId(),
            'tenant_name' => $tenant->getFullName(),
            'direct_properties_count' => count($tenantProperties),
            'direct_properties' => array_map(function($prop) {
                return [
                    'id' => $prop->getId(),
                    'address' => $prop->getAddress(),
                    'city' => $prop->getCity(),
                ];
            }, $tenantProperties),
            'global_search_results' => $globalResults,
        ]);
    }
}
