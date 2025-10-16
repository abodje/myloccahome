<?php

namespace App\Controller;

use App\Entity\Plan;
use App\Repository\PlanRepository;
use App\Service\FeatureAccessService;
use App\Service\SubscriptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mon-abonnement')]
#[IsGranted('ROLE_ADMIN')]
class SubscriptionManagementController extends AbstractController
{
    public function __construct(
        private FeatureAccessService $featureAccessService,
        private SubscriptionService $subscriptionService,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Tableau de bord de l'abonnement
     */
    #[Route('/', name: 'app_subscription_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $organization = $user->getOrganization();

        if (!$organization) {
            $this->addFlash('error', 'Aucune organisation associée à votre compte.');
            return $this->redirectToRoute('app_dashboard');
        }

        $currentSubscription = $organization->getActiveSubscription();
        $currentPlan = $currentSubscription?->getPlan();

        // Récupérer les statistiques d'utilisation
        $limits = [
            'properties' => $this->featureAccessService->getLimitInfo($organization, 'properties'),
            'tenants' => $this->featureAccessService->getLimitInfo($organization, 'tenants'),
            'users' => $this->featureAccessService->getLimitInfo($organization, 'users'),
            'documents' => $this->featureAccessService->getLimitInfo($organization, 'documents'),
        ];

        // Récupérer les fonctionnalités
        $features = $this->featureAccessService->getOrganizationFeatures($organization);

        return $this->render('subscription/index.html.twig', [
            'organization' => $organization,
            'subscription' => $currentSubscription,
            'plan' => $currentPlan,
            'limits' => $limits,
            'features' => $features,
        ]);
    }

    /**
     * Page d'upgrade de plan
     */
    #[Route('/upgrade', name: 'app_subscription_upgrade', methods: ['GET'])]
    public function upgrade(PlanRepository $planRepository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $organization = $user->getOrganization();

        if (!$organization) {
            $this->addFlash('error', 'Aucune organisation associée à votre compte.');
            return $this->redirectToRoute('app_dashboard');
        }

        $currentSubscription = $organization->getActiveSubscription();
        $currentPlan = $currentSubscription?->getPlan();

        // Récupérer tous les plans actifs
        $allPlans = $planRepository->findBy(['isActive' => true], ['sortOrder' => 'ASC']);

        return $this->render('subscription/upgrade.html.twig', [
            'organization' => $organization,
            'currentPlan' => $currentPlan,
            'plans' => $allPlans,
        ]);
    }

    /**
     * Affiche les détails d'une fonctionnalité bloquée
     */
    #[Route('/fonctionnalite-bloquee/{feature}', name: 'app_subscription_blocked_feature', methods: ['GET'])]
    public function blockedFeature(string $feature, PlanRepository $planRepository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $organization = $user->getOrganization();

        if (!$organization) {
            $this->addFlash('error', 'Aucune organisation associée à votre compte.');
            return $this->redirectToRoute('app_dashboard');
        }

        $featureLabel = $this->featureAccessService->getFeatureLabel($feature);
        $featureIcon = $this->featureAccessService->getFeatureIcon($feature);
        $requiredPlanSlug = $this->featureAccessService->getRequiredPlan($feature);
        $blockMessage = $this->featureAccessService->getFeatureBlockMessage($feature, $organization);

        // Trouver les plans qui incluent cette fonctionnalité
        $availablePlans = $planRepository->createQueryBuilder('p')
            ->where('p.isActive = :active')
            ->andWhere('p.features LIKE :feature')
            ->setParameter('active', true)
            ->setParameter('feature', '%"' . $feature . '"%')
            ->orderBy('p.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('subscription/blocked_feature.html.twig', [
            'feature' => $feature,
            'featureLabel' => $featureLabel,
            'featureIcon' => $featureIcon,
            'blockMessage' => $blockMessage,
            'requiredPlanSlug' => $requiredPlanSlug,
            'availablePlans' => $availablePlans,
            'currentPlan' => $organization->getActiveSubscription()?->getPlan(),
        ]);
    }
}

