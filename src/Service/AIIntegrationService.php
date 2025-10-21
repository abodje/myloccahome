<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service d'Intelligence Artificielle pour LOKAPRO
 *
 * Ce service intègre diverses fonctionnalités d'IA :
 * - Analyse prédictive des loyers
 * - Évaluation des risques locataires
 * - Génération automatique de contrats
 * - Analyse d'images de propriétés
 * - Recommandations personnalisées
 */
class AIIntegrationService
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private string $openaiApiKey;
    private string $googleAiApiKey;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        string $openaiApiKey = '',
        string $googleAiApiKey = ''
    ) {
        $this->httpClient = HttpClient::create();
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->openaiApiKey = $openaiApiKey;
        $this->googleAiApiKey = $googleAiApiKey;
    }

    /**
     * Prédit le prix de loyer optimal pour une propriété
     */
    public function predictOptimalRent(\App\Entity\Property $property): array
    {
        try {
            $features = $this->extractPropertyFeatures($property);

            $prompt = $this->buildRentPredictionPrompt($features);
            $response = $this->callOpenAI($prompt);

            return [
                'predicted_rent' => $response['predicted_rent'],
                'confidence' => $response['confidence'],
                'factors' => $response['key_factors'],
                'recommendations' => $response['recommendations']
            ];

        } catch (\Exception $e) {
            $this->logger->error('Erreur prédiction loyer IA: ' . $e->getMessage());
            return ['error' => 'Impossible de prédire le loyer optimal'];
        }
    }

    /**
     * Évalue le risque d'un locataire potentiel
     */
    public function assessTenantRisk(\App\Entity\Tenant $tenant): array
    {
        try {
            $riskFactors = $this->extractTenantRiskFactors($tenant);

            $prompt = $this->buildRiskAssessmentPrompt($riskFactors);
            $response = $this->callOpenAI($prompt);

            return [
                'risk_score' => $response['risk_score'],
                'risk_level' => $response['risk_level'],
                'risk_factors' => $response['risk_factors'],
                'recommendations' => $response['recommendations']
            ];

        } catch (\Exception $e) {
            $this->logger->error('Erreur évaluation risque IA: ' . $e->getMessage());
            return ['error' => 'Impossible d\'évaluer le risque locataire'];
        }
    }

    /**
     * Génère automatiquement un contrat de bail
     */
    public function generateLeaseContract(\App\Entity\Lease $lease): string
    {
        try {
            $contractData = $this->extractLeaseData($lease);

            $prompt = $this->buildContractGenerationPrompt($contractData);
            $response = $this->callOpenAI($prompt);

            return $response['contract_text'];

        } catch (\Exception $e) {
            $this->logger->error('Erreur génération contrat IA: ' . $e->getMessage());
            return 'Erreur lors de la génération du contrat. Veuillez contacter le support.';
        }
    }

    /**
     * Analyse les images d'une propriété
     */
    public function analyzePropertyImages(array $imageUrls): array
    {
        try {
            $analysis = [];

            foreach ($imageUrls as $imageUrl) {
                $result = $this->callGoogleVisionAI($imageUrl);
                $analysis[] = $result;
            }

            return $this->aggregateImageAnalysis($analysis);

        } catch (\Exception $e) {
            $this->logger->error('Erreur analyse images IA: ' . $e->getMessage());
            return ['error' => 'Impossible d\'analyser les images'];
        }
    }

    /**
     * Recommande des propriétés adaptées à un locataire
     */
    public function recommendProperties(\App\Entity\Tenant $tenant): array
    {
        try {
            $preferences = $this->extractTenantPreferences($tenant);
            $availableProperties = $this->getAvailableProperties($tenant->getCompany());

            $recommendations = [];
            foreach ($availableProperties as $property) {
                $matchScore = $this->calculatePropertyMatch($preferences, $property);
                if ($matchScore > 0.6) {
                    $recommendations[] = [
                        'property' => $property,
                        'match_score' => $matchScore,
                        'match_reasons' => $this->getMatchReasons($preferences, $property)
                    ];
                }
            }

            return $this->sortRecommendations($recommendations);

        } catch (\Exception $e) {
            $this->logger->error('Erreur recommandations IA: ' . $e->getMessage());
            return ['error' => 'Impossible de générer des recommandations'];
        }
    }

    /**
     * Génère des réponses automatiques aux demandes clients
     */
    public function generateCustomerResponse(string $inquiry, \App\Entity\Tenant $tenant): string
    {
        try {
            $context = $this->extractCustomerContext($tenant);

            $prompt = $this->buildCustomerResponsePrompt($inquiry, $context);
            $response = $this->callOpenAI($prompt);

            return $response['response_text'];

        } catch (\Exception $e) {
            $this->logger->error('Erreur réponse client IA: ' . $e->getMessage());
            return 'Merci pour votre message. Notre équipe vous répondra dans les plus brefs délais.';
        }
    }

    /**
     * Analyse les tendances du marché immobilier
     */
    public function analyzeMarketTrends(string $city): array
    {
        try {
            $marketData = $this->getMarketData($city);

            $prompt = $this->buildMarketAnalysisPrompt($marketData);
            $response = $this->callOpenAI($prompt);

            return [
                'trend_direction' => $response['trend_direction'],
                'price_forecast' => $response['price_forecast'],
                'market_insights' => $response['market_insights'],
                'recommendations' => $response['recommendations']
            ];

        } catch (\Exception $e) {
            $this->logger->error('Erreur analyse marché IA: ' . $e->getMessage());
            return ['error' => 'Impossible d\'analyser les tendances du marché'];
        }
    }

    // === MÉTHODES PRIVÉES ===

    private function extractPropertyFeatures(\App\Entity\Property $property): array
    {
        return [
            'surface' => $property->getSurface(),
            'rooms' => $property->getRooms(),
            'bedrooms' => $property->getBedrooms(),
            'bathrooms' => $property->getBathrooms(),
            'property_type' => $property->getPropertyType(),
            'city' => $property->getCity(),
            'district' => $property->getDistrict(),
            'amenities' => $property->getEquipmentList(),
            'construction_year' => $property->getConstructionYear(),
            'energy_class' => $property->getEnergyClass(),
            'has_elevator' => $property->isElevator(),
            'has_parking' => $property->isHasParking(),
            'has_balcony' => $property->isHasBalcony(),
            'furnished' => $property->isFurnished()
        ];
    }

    private function extractTenantRiskFactors(\App\Entity\Tenant $tenant): array
    {
        return [
            'age' => $this->calculateAge($tenant->getBirthDate()),
            'employment_status' => $tenant->getEmploymentStatus(),
            'income_level' => $tenant->getIncomeLevel(),
            'previous_leases' => $this->getPreviousLeases($tenant),
            'payment_history' => $this->getPaymentHistory($tenant),
            'credit_references' => $this->getCreditReferences($tenant)
        ];
    }

    private function extractLeaseData(\App\Entity\Lease $lease): array
    {
        return [
            'property' => $this->extractPropertyFeatures($lease->getProperty()),
            'tenant' => $this->extractTenantRiskFactors($lease->getTenant()),
            'lease_terms' => [
                'monthly_rent' => $lease->getMonthlyRent(),
                'security_deposit' => $lease->getSecurityDeposit(),
                'start_date' => $lease->getStartDate()->format('Y-m-d'),
                'end_date' => $lease->getEndDate()->format('Y-m-d'),
                'rent_due_day' => $lease->getRentDueDay()
            ],
            'company_info' => $this->extractCompanyInfo($lease->getCompany())
        ];
    }

    private function extractTenantPreferences(\App\Entity\Tenant $tenant): array
    {
        return [
            'budget_range' => $this->getBudgetRange($tenant),
            'preferred_locations' => $this->getPreferredLocations($tenant),
            'property_type_preference' => $this->getPropertyTypePreference($tenant),
            'amenities_preference' => $this->getAmenitiesPreference($tenant),
            'size_preference' => $this->getSizePreference($tenant)
        ];
    }

    private function extractCustomerContext(\App\Entity\Tenant $tenant): array
    {
        return [
            'tenant_info' => [
                'name' => $tenant->getFullName(),
                'current_property' => $tenant->getCurrentLease() ? $tenant->getCurrentLease()->getProperty()->getFullAddress() : null,
                'lease_status' => $tenant->getCurrentLease() ? $tenant->getCurrentLease()->getStatus() : null
            ],
            'company_policies' => $this->getCompanyPolicies($tenant->getCompany()),
            'common_issues' => $this->getCommonIssues($tenant->getCompany())
        ];
    }

    private function buildRentPredictionPrompt(array $features): string
    {
        return "En tant qu'expert en immobilier, analysez cette propriété et prédisez le loyer optimal :

Propriété :
- Surface : {$features['surface']} m²
- Pièces : {$features['rooms']}
- Chambres : {$features['bedrooms']}
- Salles de bain : {$features['bathrooms']}
- Type : {$features['property_type']}
- Ville : {$features['city']}
- Quartier : {$features['district']}
- Équipements : " . implode(', ', $features['amenities']) . "
- Année de construction : {$features['construction_year']}
- Classe énergétique : {$features['energy_class']}
- Ascenseur : " . ($features['has_elevator'] ? 'Oui' : 'Non') . "
- Parking : " . ($features['has_parking'] ? 'Oui' : 'Non') . "
- Balcon : " . ($features['has_balcony'] ? 'Oui' : 'Non') . "
- Meublé : " . ($features['furnished'] ? 'Oui' : 'Non') . "

Fournissez :
1. Loyer optimal prédit (en euros)
2. Niveau de confiance (0-100%)
3. Facteurs clés influençant le prix
4. Recommandations pour optimiser le loyer

Répondez au format JSON.";
    }

    private function buildRiskAssessmentPrompt(array $riskFactors): string
    {
        return "En tant qu'expert en évaluation de risque locataire, analysez ce profil :

Locataire :
- Âge : {$riskFactors['age']} ans
- Statut d'emploi : {$riskFactors['employment_status']}
- Niveau de revenus : {$riskFactors['income_level']}
- Historique des baux : " . count($riskFactors['previous_leases']) . " baux précédents
- Historique de paiement : {$riskFactors['payment_history']}
- Références crédit : {$riskFactors['credit_references']}

Fournissez :
1. Score de risque (0-100, 0 = très sûr, 100 = très risqué)
2. Niveau de risque (Faible/Moyen/Élevé)
3. Facteurs de risque identifiés
4. Recommandations pour réduire le risque

Répondez au format JSON.";
    }

    private function buildContractGenerationPrompt(array $contractData): string
    {
        return "Générez un contrat de bail professionnel en français pour :

Propriété : {$contractData['property']['city']}, {$contractData['property']['district']}
- Surface : {$contractData['property']['surface']} m²
- Type : {$contractData['property']['property_type']}

Locataire : {$contractData['tenant']['name']}
- Revenus : {$contractData['tenant']['income_level']}

Conditions du bail :
- Loyer mensuel : {$contractData['lease_terms']['monthly_rent']} €
- Caution : {$contractData['lease_terms']['security_deposit']} €
- Durée : du {$contractData['lease_terms']['start_date']} au {$contractData['lease_terms']['end_date']}
- Échéance : le {$contractData['lease_terms']['rent_due_day']} de chaque mois

Générez un contrat complet, professionnel et conforme à la législation française.";
    }

    private function buildCustomerResponsePrompt(string $inquiry, array $context): string
    {
        return "En tant qu'assistant client professionnel pour une agence immobilière, répondez à cette demande :

Demande du client : \"$inquiry\"

Contexte client :
- Nom : {$context['tenant_info']['name']}
- Propriété actuelle : {$context['tenant_info']['current_property']}
- Statut du bail : {$context['tenant_info']['lease_status']}

Politiques de l'entreprise :
" . implode("\n", $context['company_policies']) . "

Problèmes courants :
" . implode("\n", $context['common_issues']) . "

Fournissez une réponse professionnelle, empathique et utile. Maximum 200 mots.";
    }

    private function buildMarketAnalysisPrompt(array $marketData): string
    {
        return "En tant qu'expert en analyse de marché immobilier, analysez ces données :

Données du marché pour {$marketData['city']} :
- Prix moyen au m² : {$marketData['avg_price_per_sqm']} €
- Évolution sur 12 mois : {$marketData['price_evolution_12m']}%
- Volume de transactions : {$marketData['transaction_volume']}
- Taux de vacance : {$marketData['vacancy_rate']}%
- Nouveaux projets : {$marketData['new_projects']}

Fournissez :
1. Direction de la tendance (hausse/baisse/stabilisation)
2. Prévision de prix pour les 6 prochains mois
3. Insights sur le marché
4. Recommandations pour les investisseurs

Répondez au format JSON.";
    }

    private function callOpenAI(string $prompt): array
    {
        if (empty($this->openaiApiKey)) {
            throw new \Exception('Clé API OpenAI non configurée');
        }

        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Vous êtes un expert en immobilier et gestion locative. Répondez de manière professionnelle et précise.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 1000,
                'temperature' => 0.3
            ]
        ]);

        $data = $response->toArray();
        return json_decode($data['choices'][0]['message']['content'], true);
    }

    private function callGoogleVisionAI(string $imageUrl): array
    {
        if (empty($this->googleAiApiKey)) {
            throw new \Exception('Clé API Google AI non configurée');
        }

        $response = $this->httpClient->request('POST', 'https://vision.googleapis.com/v1/images:annotate', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->googleAiApiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'requests' => [
                    [
                        'image' => ['source' => ['imageUri' => $imageUrl]],
                        'features' => [
                            ['type' => 'LABEL_DETECTION'],
                            ['type' => 'OBJECT_LOCALIZATION'],
                            ['type' => 'TEXT_DETECTION']
                        ]
                    ]
                ]
            ]
        ]);

        return $response->toArray();
    }

    // === MÉTHODES UTILITAIRES ===

    private function calculateAge(?\DateTimeInterface $birthDate): ?int
    {
        if (!$birthDate) return null;
        return $birthDate->diff(new \DateTime())->y;
    }

    private function getPreviousLeases(\App\Entity\Tenant $tenant): array
    {
        return $this->entityManager->getRepository(\App\Entity\Lease::class)
            ->findBy(['tenant' => $tenant]);
    }

    private function getPaymentHistory(\App\Entity\Tenant $tenant): string
    {
        // Logique pour analyser l'historique de paiement
        return 'Bon historique de paiement';
    }

    private function getCreditReferences(\App\Entity\Tenant $tenant): string
    {
        // Logique pour obtenir les références crédit
        return 'Références positives';
    }

    private function getBudgetRange(\App\Entity\Tenant $tenant): array
    {
        // Logique pour déterminer la fourchette de budget
        return ['min' => 800, 'max' => 1500];
    }

    private function getPreferredLocations(\App\Entity\Tenant $tenant): array
    {
        // Logique pour déterminer les localisations préférées
        return ['Paris 15ème', 'Paris 16ème'];
    }

    private function getPropertyTypePreference(\App\Entity\Tenant $tenant): string
    {
        // Logique pour déterminer la préférence de type de propriété
        return 'Appartement';
    }

    private function getAmenitiesPreference(\App\Entity\Tenant $tenant): array
    {
        // Logique pour déterminer les équipements préférés
        return ['Ascenseur', 'Parking', 'Balcon'];
    }

    private function getSizePreference(\App\Entity\Tenant $tenant): array
    {
        // Logique pour déterminer la préférence de taille
        return ['min_surface' => 50, 'min_rooms' => 2];
    }

    private function getCompanyPolicies(\App\Entity\Company $company): array
    {
        // Logique pour obtenir les politiques de l'entreprise
        return [
            'Politique de paiement : Virement bancaire uniquement',
            'Délai de réponse : 48h maximum',
            'Procédure de maintenance : Signalement via l\'application'
        ];
    }

    private function getCommonIssues(\App\Entity\Company $company): array
    {
        // Logique pour obtenir les problèmes courants
        return [
            'Problèmes de chauffage',
            'Questions sur les charges',
            'Demandes de maintenance'
        ];
    }

    private function getAvailableProperties(\App\Entity\Company $company): array
    {
        return $this->entityManager->getRepository(\App\Entity\Property::class)
            ->findBy(['company' => $company, 'status' => 'Libre']);
    }

    private function calculatePropertyMatch(array $preferences, \App\Entity\Property $property): float
    {
        $score = 0;
        $totalFactors = 0;

        // Vérification du budget
        if ($property->getMonthlyRent() >= $preferences['budget_range']['min'] &&
            $property->getMonthlyRent() <= $preferences['budget_range']['max']) {
            $score += 0.3;
        }
        $totalFactors += 0.3;

        // Vérification de la localisation
        if (in_array($property->getDistrict(), $preferences['preferred_locations'])) {
            $score += 0.2;
        }
        $totalFactors += 0.2;

        // Vérification du type de propriété
        if ($property->getPropertyType() === $preferences['property_type_preference']) {
            $score += 0.2;
        }
        $totalFactors += 0.2;

        // Vérification de la taille
        if ($property->getSurface() >= $preferences['size_preference']['min_surface'] &&
            $property->getRooms() >= $preferences['size_preference']['min_rooms']) {
            $score += 0.2;
        }
        $totalFactors += 0.2;

        // Vérification des équipements
        $amenitiesMatch = 0;
        foreach ($preferences['amenities_preference'] as $amenity) {
            if (in_array($amenity, $property->getEquipmentList())) {
                $amenitiesMatch++;
            }
        }
        if (count($preferences['amenities_preference']) > 0) {
            $score += 0.1 * ($amenitiesMatch / count($preferences['amenities_preference']));
        }
        $totalFactors += 0.1;

        return $totalFactors > 0 ? $score / $totalFactors : 0;
    }

    private function getMatchReasons(array $preferences, \App\Entity\Property $property): array
    {
        $reasons = [];

        if ($property->getMonthlyRent() >= $preferences['budget_range']['min'] &&
            $property->getMonthlyRent() <= $preferences['budget_range']['max']) {
            $reasons[] = 'Dans votre budget';
        }

        if (in_array($property->getDistrict(), $preferences['preferred_locations'])) {
            $reasons[] = 'Localisation préférée';
        }

        if ($property->getPropertyType() === $preferences['property_type_preference']) {
            $reasons[] = 'Type de propriété souhaité';
        }

        return $reasons;
    }

    private function sortRecommendations(array $recommendations): array
    {
        usort($recommendations, function($a, $b) {
            return $b['match_score'] <=> $a['match_score'];
        });

        return $recommendations;
    }

    private function aggregateImageAnalysis(array $analysis): array
    {
        $aggregated = [
            'objects_detected' => [],
            'condition_assessment' => 'Bon état',
            'amenities_identified' => [],
            'value_estimate' => 0
        ];

        foreach ($analysis as $result) {
            if (isset($result['labelAnnotations'])) {
                foreach ($result['labelAnnotations'] as $label) {
                    $aggregated['objects_detected'][] = $label['description'];
                }
            }
        }

        return $aggregated;
    }

    private function getMarketData(string $city): array
    {
        // Simulation de données de marché - à remplacer par des données réelles
        return [
            'city' => $city,
            'avg_price_per_sqm' => 8000,
            'price_evolution_12m' => 5.2,
            'transaction_volume' => 1250,
            'vacancy_rate' => 3.5,
            'new_projects' => 45
        ];
    }

    private function extractCompanyInfo(\App\Entity\Company $company): array
    {
        return [
            'name' => $company->getName(),
            'legal_name' => $company->getLegalName(),
            'address' => $company->getAddress(),
            'phone' => $company->getPhone(),
            'email' => $company->getEmail()
        ];
    }
}
