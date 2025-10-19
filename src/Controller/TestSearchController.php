<?php

namespace App\Controller;

use App\Service\GlobalSearchService;
use App\Repository\PropertyRepository;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TestSearchController extends AbstractController
{
    public function __construct(
        private GlobalSearchService $globalSearchService,
        private PropertyRepository $propertyRepository
    ) {
    }

    #[Route('/test-search', name: 'app_test_search', methods: ['GET'])]
    public function testSearch(): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        // Test avec différents termes de recherche
        $queries = ['test', 'danga', 'pisam', 'koumassi'];

        $results = [];
        foreach ($queries as $query) {
            $results[$query] = $this->globalSearchService->search($query, 50);
        }

        // 1. Toutes les propriétés
        $allProperties = $this->propertyRepository->findAll();

        // 3. Informations utilisateur
        $userInfo = [];
        if ($user) {
            $userInfo = [
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'organization' => $user->getOrganization() ? $user->getOrganization()->getName() : null,
                'company' => $user->getCompany() ? $user->getCompany()->getName() : null,
                'tenant' => $user->getTenant() ? $user->getTenant()->getFullName() : null,
            ];
        }

        return $this->render('test_search.html.twig', [
            'queries' => $queries,
            'results' => $results,
            'user_info' => $userInfo,
            'all_properties' => $allProperties,
        ]);
    }
}
