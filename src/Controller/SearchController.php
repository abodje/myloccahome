<?php

namespace App\Controller;

use App\Service\GlobalSearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/recherche')]
class SearchController extends AbstractController
{
    #[Route('/', name: 'app_search', methods: ['GET'])]
    public function index(Request $request, GlobalSearchService $searchService): Response
    {
        $query = $request->query->get('q', '');
        $results = [];
        $stats = ['total' => 0, 'by_type' => []];

        if ($query && strlen($query) >= 2) {
            $results = $searchService->search($query, 50);
            $stats = $searchService->getSearchStats($results);
        }

        return $this->render('search/index.html.twig', [
            'query' => $query,
            'results' => $results,
            'stats' => $stats,
        ]);
    }

    /**
     * API pour l'autocomplete (suggestions en temps réel)
     */
    #[Route('/api/suggestions', name: 'app_search_api', methods: ['GET'])]
    public function suggestions(Request $request, GlobalSearchService $searchService): JsonResponse
    {
        $query = $request->query->get('q', '');

        if (strlen($query) < 2) {
            return new JsonResponse([]);
        }

        try {
            $suggestions = $searchService->quickSearch($query, 10);
            return new JsonResponse($suggestions);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

