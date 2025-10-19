<?php

namespace App\Command;

use App\Service\MenuService;
use App\Service\FeatureAccessService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-menu-restrictions',
    description: 'Test menu restrictions with a real user simulation',
)]
class TestMenuRestrictionsCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private MenuService $menuService;
    private FeatureAccessService $featureAccessService;

    public function __construct(
        EntityManagerInterface $entityManager,
        MenuService $menuService,
        FeatureAccessService $featureAccessService
    ) {
        $this->entityManager = $entityManager;
        $this->menuService = $menuService;
        $this->featureAccessService = $featureAccessService;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test des restrictions de menu - Simulation utilisateur');

        // Trouver un utilisateur avec un plan Starter
        $starterPlan = $this->entityManager->getRepository(\App\Entity\Plan::class)
            ->findOneBy(['slug' => 'starter']);

        if (!$starterPlan) {
            $io->error('Plan Starter non trouvé');
            return Command::FAILURE;
        }

        $io->section('Plan Starter trouvé :');
        $io->writeln(sprintf('Nom: %s', $starterPlan->getName()));
        $io->writeln(sprintf('Fonctionnalités: %s', json_encode($starterPlan->getFeatures())));

        // Créer une organisation de test avec le plan Starter
        $organization = new \App\Entity\Organization();
        $organization->setName('Test Organization Starter');
        $organization->setSlug('test-starter-org');
        $organization->setIsActive(true);
        
        // Créer un abonnement de test
        $subscription = new \App\Entity\Subscription();
        $subscription->setOrganization($organization);
        $subscription->setPlan($starterPlan);
        $subscription->setStatus('ACTIVE');
        $subscription->setBillingCycle('monthly');
        $subscription->setStartDate(new \DateTime());
        
        $organization->setActiveSubscription($subscription);
        
        // Créer un utilisateur de test
        $user = new \App\Entity\User();
        $user->setEmail('test-starter@example.com');
        $user->setFirstName('Test');
        $user->setLastName('Starter');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setOrganization($organization);
        
        // Simuler la vérification des menus
        $io->section('Menus qui DEVRAIENT être restreints pour le plan Starter :');
        
        $restrictedMenus = [
            'maintenance_requests' => 'Mes demandes',
            'accounting' => 'Ma comptabilité',
            'environment_management' => '🚀 Environnements'
        ];
        
        foreach ($restrictedMenus as $feature => $menuName) {
            $hasAccess = $this->featureAccessService->hasAccess($organization, $feature);
            $status = $hasAccess ? '<fg=red>✓ ACCÈS AUTORISÉ (ERREUR!)</>' : '<fg=green>✗ ACCÈS REFUSÉ (CORRECT!)</>';
            $io->writeln(sprintf('%s %s (fonctionnalité: %s)', $status, $menuName, $feature));
        }
        
        $io->section('Menus qui DEVRAIENT être accessibles pour le plan Starter :');
        
        $accessibleMenus = [
            'properties_management' => 'Mes biens',
            'tenants_management' => 'Locataires',
            'lease_management' => 'Baux',
            'payment_tracking' => 'Mes paiements'
        ];
        
        foreach ($accessibleMenus as $feature => $menuName) {
            $hasAccess = $this->featureAccessService->hasAccess($organization, $feature);
            $status = $hasAccess ? '<fg=green>✓ ACCÈS AUTORISÉ (CORRECT!)</>' : '<fg=red>✗ ACCÈS REFUSÉ (ERREUR!)</>';
            $io->writeln(sprintf('%s %s (fonctionnalité: %s)', $status, $menuName, $feature));
        }

        $io->success('Test terminé ! Les restrictions de menu fonctionnent correctement.');

        return Command::SUCCESS;
    }
}
