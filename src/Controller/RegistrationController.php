<?php

namespace App\Controller;

use App\Entity\Organization;
use App\Entity\User;
use App\Entity\Plan;
use App\Repository\PlanRepository;
use App\Service\SubscriptionService;
use App\Service\DemoEnvironmentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/inscription')]
class RegistrationController extends AbstractController
{
    /**
     * Page de choix de plan
     */
    #[Route('/plans', name: 'app_registration_plans', methods: ['GET'])]
    public function plans(PlanRepository $planRepository): Response
    {
        $plans = $planRepository->findActivePlans();

        return $this->render('registration/plans.html.twig', [
            'plans' => $plans,
        ]);
    }

    /**
     * Formulaire d'inscription
     */
    #[Route('/inscription/{planSlug}', name: 'app_registration_register', methods: ['GET', 'POST'])]
    public function register(
        string $planSlug,
        Request $request,
        PlanRepository $planRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger,
        SubscriptionService $subscriptionService,
        DemoEnvironmentService $demoEnvironmentService
    ): Response {
        $plan = $planRepository->findBySlug($planSlug);

        if (!$plan) {
            $this->addFlash('error', 'Plan d\'abonnement introuvable.');
            return $this->redirectToRoute('app_registration_plans');
        }

        if ($request->isMethod('POST')) {
            try {
                // Données de l'organisation
                $orgName = $request->request->get('organization_name');
                $orgEmail = $request->request->get('organization_email');
                $orgPhone = $request->request->get('organization_phone');

                // Données de l'utilisateur
                $userFirstName = $request->request->get('first_name');
                $userLastName = $request->request->get('last_name');
                $userEmail = $request->request->get('email');
                $userPassword = $request->request->get('password');

                // Cycle de facturation
                $billingCycle = $request->request->get('billing_cycle', 'MONTHLY');

                // Validation des données
                if (empty($orgName) || empty($userEmail) || empty($userPassword)) {
                    $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires.');
                    return $this->render('registration/register.html.twig', ['plan' => $plan]);
                }

                // Vérifier si l'email existe déjà
                $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $userEmail]);
                if ($existingUser) {
                    $this->addFlash('error', 'Cet email est déjà utilisé. Si vous avez déjà un compte, veuillez vous connecter. Si votre inscription précédente a échoué, veuillez contacter le support ou utiliser un autre email.');
                    return $this->render('registration/register.html.twig', ['plan' => $plan]);
                }

                // Vérifier aussi si l'email existe dans la table Tenant pour éviter les conflits futurs
                $existingTenant = $entityManager->getRepository(\App\Entity\Tenant::class)->findOneBy(['email' => $userEmail]);
                if ($existingTenant) {
                    $this->addFlash('warning', 'Cet email existe déjà dans notre système. Veuillez utiliser un autre email.');
                    return $this->render('registration/register.html.twig', ['plan' => $plan]);
                }

                // Créer l'organisation
                $organization = new Organization();
                $organization->setName($orgName);
                $organization->setSlug($slugger->slug($orgName)->lower());
                $organization->setEmail($orgEmail);
                $organization->setPhone($orgPhone);
                $organization->setStatus('TRIAL'); // Commence en période d'essai
                $organization->setCreatedAt(new \DateTime());
                $organization->setIsActive(true);

                // Copier les fonctionnalités du plan vers l'organisation
                $organization->setFeatures($plan->getFeatures());

                // Définir les limites basées sur le plan
                $organization->setSetting('max_properties', $plan->getMaxProperties());
                $organization->setSetting('max_tenants', $plan->getMaxTenants());
                $organization->setSetting('max_users', $plan->getMaxUsers());
                $organization->setSetting('max_documents', $plan->getMaxDocuments());

                $entityManager->persist($organization);

                // Créer la société par défaut (siège social)
                $company = new \App\Entity\Company();
                $company->setName($orgName);
                $company->setLegalName($orgName);
                $company->setOrganization($organization);
                $company->setEmail($orgEmail);
                $company->setPhone($orgPhone);
                $company->setStatus('ACTIVE');
                $company->setIsHeadquarter(true); // C'est le siège social
                $company->setCreatedAt(new \DateTime());

                $entityManager->persist($company);

                // Créer l'utilisateur administrateur
                $user = new User();
                $user->setEmail($userEmail);
                $user->setFirstName($userFirstName ?? 'Admin');
                $user->setLastName($userLastName ?? 'Admin');
                $user->setRoles(['ROLE_ADMIN']);
                $user->setOrganization($organization);

                $hashedPassword = $passwordHasher->hashPassword($user, $userPassword);
                $user->setPassword($hashedPassword);

                $entityManager->persist($user);

                // Créer l'abonnement
                $subscription = $subscriptionService->createSubscription(
                    $organization,
                    $plan,
                    $billingCycle
                );

                $entityManager->flush();

                // Créer l'environnement de démo automatiquement (dans un try-catch séparé)
                try {
                    $demoResult = $demoEnvironmentService->createDemoEnvironment($user);

                    if ($demoResult['success']) {
                        $this->addFlash('success', '🎉 Votre compte et environnement de démo ont été créés avec succès !');
                        $this->addFlash('info', "🌐 Votre environnement de démo : {$demoResult['demo_url']}");
                        $this->addFlash('info', "📊 Données de démo créées : {$demoResult['demo_data']['properties']} propriétés, {$demoResult['demo_data']['tenants']} locataires, {$demoResult['demo_data']['leases']} baux, {$demoResult['demo_data']['payments']} paiements");
                    } else {
                        $this->addFlash('warning', '⚠️ Compte créé mais erreur lors de la création de l\'environnement de démo : ' . $demoResult['error']);
                    }
                } catch (\Exception $demoException) {
                    // Log l'erreur mais ne pas faire échouer l'inscription
                    error_log('Erreur création environnement démo: ' . $demoException->getMessage());
                    $this->addFlash('warning', '⚠️ Compte créé avec succès, mais erreur lors de la création de l\'environnement de démo. Vous pourrez le créer manuellement plus tard.');
                }

                // Si plan gratuit (Freemium), activer directement
                if ($plan->getSlug() === 'freemium' || (float)$plan->getMonthlyPrice() == 0) {
                    $subscriptionService->activateSubscription($subscription);
                    $entityManager->flush();

                    $this->addFlash('success', '🎉 Votre compte a été créé avec succès ! Connectez-vous pour commencer.');
                    return $this->redirectToRoute('app_login');
                }

                // Pour les plans payants, rediriger vers la page de paiement
                return $this->redirectToRoute('app_registration_payment', [
                    'subscriptionId' => $subscription->getId()
                ]);

            } catch (\Exception $e) {
                // Log l'erreur complète pour le debug
                error_log('Erreur inscription: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());

                $this->addFlash('error', 'Erreur lors de l\'inscription : ' . $e->getMessage());

                return $this->render('registration/register.html.twig', [
                    'plan' => $plan,
                ]);
            }
        }

        return $this->render('registration/register.html.twig', [
            'plan' => $plan,
        ]);
    }

    /**
     * Page de paiement de l'abonnement
     */
    #[Route('/paiement/{subscriptionId}', name: 'app_registration_payment', methods: ['GET', 'POST'])]
    public function payment(
        int $subscriptionId,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $subscription = $entityManager->getRepository(\App\Entity\Subscription::class)->find($subscriptionId);

        if (!$subscription) {
            $this->addFlash('error', 'Abonnement introuvable.');
            return $this->redirectToRoute('app_registration_plans');
        }

        if ($request->isMethod('POST')) {
            // TODO: Intégrer le paiement CinetPay pour l'abonnement
            // Pour l'instant, on active directement (mode développement)

            $subscription->activate();
            $subscription->getOrganization()->setActiveSubscription($subscription);
            $subscription->getOrganization()->setStatus('ACTIVE');

            $entityManager->flush();

            $this->addFlash('success', 'Votre abonnement a été activé avec succès ! Bienvenue sur MYLOCCA.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/payment.html.twig', [
            'subscription' => $subscription,
            'plan' => $subscription->getPlan(),
            'organization' => $subscription->getOrganization(),
        ]);
    }
}

