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
    name: 'app:test-feature-access',
    description: 'Test the feature access system',
)]
class TestFeatureAccessCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private FeatureAccessService $featureAccessService;

    public function __construct(
        EntityManagerInterface $entityManager,
        FeatureAccessService $featureAccessService
    ) {
        $this->entityManager = $entityManager;
        $this->featureAccessService = $featureAccessService;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test du système de restrictions de fonctionnalités');

        // Récupérer tous les plans
        $plans = $this->entityManager->getRepository(\App\Entity\Plan::class)->findAll();

        $io->section('Plans disponibles :');
        foreach ($plans as $plan) {
            $features = $plan->getFeatures();
            $io->writeln(sprintf(
                '<info>%s</info> (%s): %d fonctionnalités',
                $plan->getName(),
                $plan->getSlug(),
                is_array($features) ? count($features) : 0
            ));

            if (is_array($features)) {
                foreach ($features as $feature) {
                    $io->writeln(sprintf('  - %s', $feature));
                }
            }
            $io->writeln('');
        }

        // Tester avec un plan Starter
        $starterPlan = $this->entityManager->getRepository(\App\Entity\Plan::class)
            ->findOneBy(['slug' => 'starter']);

        if ($starterPlan) {
            $io->section('Test avec le plan Starter :');

            // Simuler une organisation avec le plan Starter
            $organization = new \App\Entity\Organization();
            $organization->setName('Test Organization');
            $organization->setSlug('test-org');

            // Créer un abonnement de test
            $subscription = new \App\Entity\Subscription();
            $subscription->setOrganization($organization);
            $subscription->setPlan($starterPlan);
            $subscription->setStatus('ACTIVE');
            $subscription->setBillingCycle('monthly');
            $subscription->setStartDate(new \DateTime());

            $organization->setActiveSubscription($subscription);

            // Tester l'accès aux fonctionnalités
            $featuresToTest = [
                'properties_management',
                'tenants_management',
                'lease_management',
                'payment_tracking',
                'maintenance_requests',
                'accounting',
                'environment_management'
            ];

            foreach ($featuresToTest as $feature) {
                $hasAccess = $this->featureAccessService->hasAccess($organization, $feature);
                $status = $hasAccess ? '<fg=green>✓</>' : '<fg=red>✗</>';
                $io->writeln(sprintf('%s %s', $status, $feature));
            }
        }

        $io->success('Test terminé !');

        return Command::SUCCESS;
    }
}
