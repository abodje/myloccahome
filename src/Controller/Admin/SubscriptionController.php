<?php

namespace App\Controller\Admin;

use App\Entity\Subscription;
use App\Form\SubscriptionType;
use App\Repository\SubscriptionRepository;
use App\Repository\PlanRepository;
use App\Repository\OrganizationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/subscriptions', name: 'app_admin_subscription_')]
#[IsGranted('ROLE_ADMIN')]
class SubscriptionController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SubscriptionRepository $subscriptionRepository,
        private PlanRepository $planRepository,
        private OrganizationRepository $organizationRepository
    ) {
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $status = $request->query->get('status');
        $planId = $request->query->get('plan');
        $organizationId = $request->query->get('organization');

        $criteria = [];
        if ($status) {
            $criteria['status'] = $status;
        }
        if ($planId) {
            $criteria['plan'] = $this->planRepository->find($planId);
        }
        if ($organizationId) {
            $criteria['organization'] = $this->organizationRepository->find($organizationId);
        }

        $subscriptions = $this->subscriptionRepository->findBy($criteria, ['createdAt' => 'DESC']);

        $plans = $this->planRepository->findBy([], ['sortOrder' => 'ASC']);
        $organizations = $this->organizationRepository->findBy([], ['name' => 'ASC']);

        // Statistiques
        $stats = [
            'total' => $this->subscriptionRepository->count([]),
            'active' => $this->subscriptionRepository->count(['status' => 'ACTIVE']),
            'cancelled' => $this->subscriptionRepository->count(['status' => 'CANCELLED']),
            'expired' => $this->subscriptionRepository->count(['status' => 'EXPIRED']),
        ];

        return $this->render('admin/subscription/index.html.twig', [
            'subscriptions' => $subscriptions,
            'plans' => $plans,
            'organizations' => $organizations,
            'stats' => $stats,
            'current_filters' => [
                'status' => $status,
                'plan' => $planId,
                'organization' => $organizationId,
            ],
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $subscription = new Subscription();
        $form = $this->createForm(SubscriptionType::class, $subscription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Calculate amount from plan if not manually set
            if (!$subscription->getAmount() && $subscription->getPlan()) {
                $plan = $subscription->getPlan();
                $billingCycle = $subscription->getBillingCycle();

                if ($billingCycle === 'yearly') {
                    $amount = $plan->getYearlyPrice() ?? $plan->getMonthlyPrice() * 12;
                } else {
                    $amount = $plan->getMonthlyPrice();
                }

                $subscription->setAmount($amount);

                // Set currency from plan if not set
                if (!$subscription->getCurrency()) {
                    $subscription->setCurrency($plan->getCurrency());
                }
            }

            // Set default dates if not provided
            if (!$subscription->getStartDate()) {
                $subscription->setStartDate(new \DateTime());
            }

            if (!$subscription->getEndDate()) {
                $startDate = $subscription->getStartDate();
                $billingCycle = $subscription->getBillingCycle();

                if ($billingCycle === 'yearly') {
                    $endDate = new \DateTime($startDate->format('Y-m-d'));
                    $endDate->modify('+1 year');
                } else {
                    $endDate = new \DateTime($startDate->format('Y-m-d'));
                    $endDate->modify('+1 month');
                }

                $subscription->setEndDate($endDate);
                $subscription->setNextBillingDate($endDate);
            }

            // Si c'est le premier abonnement de l'organisation, le marquer comme actif
            $existingActive = $this->subscriptionRepository->findOneBy([
                'organization' => $subscription->getOrganization(),
                'status' => 'ACTIVE'
            ]);

            if (!$existingActive) {
                $subscription->setStatus('ACTIVE');
                $subscription->getOrganization()->setActiveSubscription($subscription);
            }

            $this->entityManager->persist($subscription);
            $this->entityManager->flush();

            $this->addFlash('success', 'Abonnement créé avec succès.');
            return $this->redirectToRoute('app_admin_subscription_index');
        }

        return $this->render('admin/subscription/new.html.twig', [
            'subscription' => $subscription,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Subscription $subscription): Response
    {
        return $this->render('admin/subscription/show.html.twig', [
            'subscription' => $subscription,
            'now' => new \DateTime(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Subscription $subscription): Response
    {
        $form = $this->createForm(SubscriptionType::class, $subscription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Abonnement modifié avec succès.');
            return $this->redirectToRoute('app_admin_subscription_index');
        }

        return $this->render('admin/subscription/edit.html.twig', [
            'subscription' => $subscription,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/activate', name: 'activate', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function activate(Subscription $subscription): Response
    {
        // Désactiver l'abonnement actuel de l'organisation
        $currentActive = $subscription->getOrganization()->getActiveSubscription();
        if ($currentActive && $currentActive !== $subscription) {
            $currentActive->setStatus('CANCELLED');
        }

        // Activer le nouvel abonnement
        $subscription->setStatus('ACTIVE');
        $subscription->getOrganization()->setActiveSubscription($subscription);

        $this->entityManager->flush();

        $this->addFlash('success', 'Abonnement activé avec succès.');
        return $this->redirectToRoute('app_admin_subscription_show', ['id' => $subscription->getId()]);
    }

    #[Route('/{id}/cancel', name: 'cancel', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function cancel(Subscription $subscription): Response
    {
        $subscription->setStatus('CANCELLED');
        $subscription->setCancelledAt(new \DateTime());

        // Si c'était l'abonnement actif, le retirer
        if ($subscription->getOrganization()->getActiveSubscription() === $subscription) {
            $subscription->getOrganization()->setActiveSubscription(null);
        }

        $this->entityManager->flush();

        $this->addFlash('success', 'Abonnement annulé avec succès.');
        return $this->redirectToRoute('app_admin_subscription_show', ['id' => $subscription->getId()]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Subscription $subscription): Response
    {
        if ($this->isCsrfTokenValid('delete' . $subscription->getId(), $request->request->get('_token'))) {
            // Si c'était l'abonnement actif, le retirer
            if ($subscription->getOrganization()->getActiveSubscription() === $subscription) {
                $subscription->getOrganization()->setActiveSubscription(null);
            }

            $this->entityManager->remove($subscription);
            $this->entityManager->flush();

            $this->addFlash('success', 'Abonnement supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin_subscription_index');
    }
}
