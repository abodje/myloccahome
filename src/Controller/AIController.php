<?php

namespace App\Controller;

use App\Service\AIIntegrationService;
use App\Entity\Property;
use App\Entity\Tenant;
use App\Entity\Lease;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/ai')]
#[IsGranted('ROLE_USER')]
class AIController extends AbstractController
{
    public function __construct(
        private AIIntegrationService $aiService
    ) {
    }

    /**
     * Prédit le loyer optimal pour une propriété
     */
    #[Route('/predict-rent/{id}', name: 'app_ai_predict_rent', methods: ['GET'])]
    public function predictRent(Property $property): JsonResponse
    {
        try {
            $prediction = $this->aiService->predictOptimalRent($property);

            return $this->json([
                'success' => true,
                'data' => $prediction
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Évalue le risque d'un locataire
     */
    #[Route('/assess-risk/{id}', name: 'app_ai_assess_risk', methods: ['GET'])]
    public function assessRisk(Tenant $tenant): JsonResponse
    {
        try {
            $assessment = $this->aiService->assessTenantRisk($tenant);

            return $this->json([
                'success' => true,
                'data' => $assessment
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Génère un contrat de bail automatiquement
     */
    #[Route('/generate-contract/{id}', name: 'app_ai_generate_contract', methods: ['GET'])]
    public function generateContract(Lease $lease): JsonResponse
    {
        try {
            $contract = $this->aiService->generateLeaseContract($lease);

            return $this->json([
                'success' => true,
                'data' => [
                    'contract_text' => $contract
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Analyse les images d'une propriété
     */
    #[Route('/analyze-images', name: 'app_ai_analyze_images', methods: ['POST'])]
    public function analyzeImages(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $imageUrls = $data['image_urls'] ?? [];

            if (empty($imageUrls)) {
                return $this->json([
                    'success' => false,
                    'error' => 'Aucune image fournie'
                ], 400);
            }

            $analysis = $this->aiService->analyzePropertyImages($imageUrls);

            return $this->json([
                'success' => true,
                'data' => $analysis
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recommande des propriétés pour un locataire
     */
    #[Route('/recommend-properties/{id}', name: 'app_ai_recommend_properties', methods: ['GET'])]
    public function recommendProperties(Tenant $tenant): JsonResponse
    {
        try {
            $recommendations = $this->aiService->recommendProperties($tenant);

            return $this->json([
                'success' => true,
                'data' => $recommendations
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Génère une réponse automatique à une demande client
     */
    #[Route('/generate-response', name: 'app_ai_generate_response', methods: ['POST'])]
    public function generateResponse(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $inquiry = $data['inquiry'] ?? '';
            $tenantId = $data['tenant_id'] ?? null;

            if (empty($inquiry) || !$tenantId) {
                return $this->json([
                    'success' => false,
                    'error' => 'Demande et ID locataire requis'
                ], 400);
            }

            $tenant = $this->getDoctrine()->getRepository(Tenant::class)->find($tenantId);
            if (!$tenant) {
                return $this->json([
                    'success' => false,
                    'error' => 'Locataire non trouvé'
                ], 404);
            }

            $response = $this->aiService->generateCustomerResponse($inquiry, $tenant);

            return $this->json([
                'success' => true,
                'data' => [
                    'response_text' => $response
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Analyse les tendances du marché
     */
    #[Route('/analyze-market/{city}', name: 'app_ai_analyze_market', methods: ['GET'])]
    public function analyzeMarket(string $city): JsonResponse
    {
        try {
            $analysis = $this->aiService->analyzeMarketTrends($city);

            return $this->json([
                'success' => true,
                'data' => $analysis
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Interface d'administration pour l'IA
     */
    #[Route('/admin', name: 'app_ai_admin', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function admin(): Response
    {
        return $this->render('ai/admin.html.twig', [
            'title' => 'Administration IA'
        ]);
    }
}
