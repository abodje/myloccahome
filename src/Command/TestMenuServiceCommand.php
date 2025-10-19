<?php

namespace App\Command;

use App\Service\FeatureAccessService;
use App\Service\MenuService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-menu-service',
    description: 'Test the MenuService directly',
)]
class TestMenuServiceCommand extends Command
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

        $io->title('Test direct du MenuService');

        // Récupérer tous les plans
        $plans = $this->entityManager->getRepository(\App\Entity\Plan::class)->findAll();

        foreach ($plans as $plan) {
            $io->section("Plan : " . $plan->getName() . " (" . $plan->getSlug() . ")");

            // Créer une organisation de test avec ce plan
            $organization = new \App\Entity\Organization();
            $organization->setName('Test Organization');
            $organization->setSlug('test-org');

            // Créer un abonnement de test
            $subscription = new \App\Entity\Subscription();
            $subscription->setOrganization($organization);
            $subscription->setPlan($plan);
            $subscription->setStatus('ACTIVE');
            $subscription->setBillingCycle('monthly');
            $subscription->setStartDate(new \DateTime());

            $organization->setActiveSubscription($subscription);

            // Récupérer le menu complet
            $allMenus = $this->menuService->getMenuStructure();

            $io->writeln('<info>Menus avec restrictions :</info>');
            $accessibleMenus = 0;
            $restrictedMenus = 0;

            foreach ($allMenus as $key => $menu) {
                $hasFeatureRestriction = isset($menu['required_feature']);

                if ($hasFeatureRestriction) {
                    $hasAccess = $this->featureAccessService->hasAccess($organization, $menu['required_feature']);

                    if ($hasAccess) {
                        $accessibleMenus++;
                        $io->writeln(sprintf('  ✓ %s (requiert: %s)', $menu['label'] ?? $key, $menu['required_feature']));
                    } else {
                        $restrictedMenus++;
                        $io->writeln(sprintf('  ✗ %s (requiert: %s)', $menu['label'] ?? $key, $menu['required_feature']));
                    }
                } else {
                    $accessibleMenus++;
                    $io->writeln(sprintf('  ✓ %s (pas de restriction)', $menu['label'] ?? $key));
                }
            }

            $io->writeln(sprintf('<comment>Résumé: %d accessibles, %d restreints</comment>', $accessibleMenus, $restrictedMenus));
            $io->writeln('');
        }

        $io->success('Test terminé !');

        return Command::SUCCESS;
    }
}
