<?php

namespace App\Command;

use App\Entity\Plan;
use App\Entity\Subscription;
use App\Entity\Organization;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-plan-subscription-system',
    description: 'Test the plan and subscription management system',
)]
class TestPlanSubscriptionSystemCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test du système de gestion des plans et abonnements');

        // Vérifier les plans existants
        $plans = $this->entityManager->getRepository(Plan::class)->findAll();
        $io->section('Plans d\'abonnement disponibles :');

        if (empty($plans)) {
            $io->warning('Aucun plan d\'abonnement trouvé.');
        } else {
            foreach ($plans as $plan) {
                $io->writeln(sprintf(
                    '<info>%s</info> (%s) - %s %s/mois - %d fonctionnalités',
                    $plan->getName(),
                    $plan->getSlug(),
                    number_format($plan->getMonthlyPrice(), 0, ',', ' '),
                    $plan->getCurrency(),
                    is_array($plan->getFeatures()) ? count($plan->getFeatures()) : 0
                ));
            }
        }

        // Vérifier les abonnements
        $subscriptions = $this->entityManager->getRepository(Subscription::class)->findAll();
        $io->section('Abonnements existants :');

        if (empty($subscriptions)) {
            $io->warning('Aucun abonnement trouvé.');
        } else {
            $stats = [
                'total' => count($subscriptions),
                'active' => 0,
                'cancelled' => 0,
                'expired' => 0,
                'pending' => 0
            ];

            foreach ($subscriptions as $subscription) {
                $stats[strtolower($subscription->getStatus())]++;

                try {
                    $io->writeln(sprintf(
                        '%s - %s (%s) - %s',
                        $subscription->getOrganization() ? $subscription->getOrganization()->getName() : 'Organisation supprimée',
                        $subscription->getPlan() ? $subscription->getPlan()->getName() : 'Plan supprimé',
                        $subscription->getStatus(),
                        $subscription->getStartDate() ? $subscription->getStartDate()->format('d/m/Y') : 'Date inconnue'
                    ));
                } catch (\Exception $e) {
                    $io->writeln(sprintf(
                        'Abonnement #%d - Erreur: %s',
                        $subscription->getId(),
                        $e->getMessage()
                    ));
                }
            }

            $io->writeln('');
            $io->writeln('<comment>Statistiques :</comment>');
            $io->writeln(sprintf('Total: %d', $stats['total']));
            $io->writeln(sprintf('Actifs: %d', $stats['active']));
            $io->writeln(sprintf('Annulés: %d', $stats['cancelled']));
            $io->writeln(sprintf('Expirés: %d', $stats['expired']));
            $io->writeln(sprintf('En attente: %d', $stats['pending']));
        }

        // Vérifier les organisations sans abonnement actif
        $organizations = $this->entityManager->getRepository(Organization::class)->findAll();
        $organizationsWithoutActiveSubscription = 0;

        foreach ($organizations as $organization) {
            if (!$organization->getActiveSubscription()) {
                $organizationsWithoutActiveSubscription++;
            }
        }

        if ($organizationsWithoutActiveSubscription > 0) {
            $io->warning(sprintf(
                '%d organisation(s) n\'ont pas d\'abonnement actif.',
                $organizationsWithoutActiveSubscription
            ));
        }

        $io->section('Routes disponibles :');
        $io->writeln('<info>Plans :</info>');
        $io->writeln('- Liste: /admin/plans');
        $io->writeln('- Créer: /admin/plans/new');
        $io->writeln('- Voir: /admin/plans/{id}');
        $io->writeln('- Modifier: /admin/plans/{id}/edit');

        $io->writeln('');
        $io->writeln('<info>Abonnements :</info>');
        $io->writeln('- Liste: /admin/subscriptions');
        $io->writeln('- Créer: /admin/subscriptions/new');
        $io->writeln('- Voir: /admin/subscriptions/{id}');
        $io->writeln('- Modifier: /admin/subscriptions/{id}/edit');

        $io->success('Test terminé ! Le système de gestion des plans et abonnements est opérationnel.');

        return Command::SUCCESS;
    }
}
