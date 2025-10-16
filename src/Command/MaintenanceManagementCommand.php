<?php

namespace App\Command;

use App\Service\MaintenanceAssignmentService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:maintenance:manage',
    description: 'Gestion automatique des demandes de maintenance (attribution, notifications urgentes, alertes retard)',
)]
class MaintenanceManagementCommand extends Command
{
    public function __construct(
        private MaintenanceAssignmentService $assignmentService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('assign', 'a', InputOption::VALUE_NONE, 'Attribuer automatiquement les demandes non assignées')
            ->addOption('urgent', 'u', InputOption::VALUE_NONE, 'Envoyer les notifications pour les demandes urgentes')
            ->addOption('overdue', 'o', InputOption::VALUE_NONE, 'Vérifier et notifier les demandes en retard')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Exécuter toutes les actions')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🔧 Gestion automatique des demandes de maintenance');

        $assign = $input->getOption('assign');
        $urgent = $input->getOption('urgent');
        $overdue = $input->getOption('overdue');
        $all = $input->getOption('all');

        // Si aucune option n'est spécifiée, exécuter tout
        if (!$assign && !$urgent && !$overdue) {
            $all = true;
        }

        $results = [];

        // Attribution automatique
        if ($assign || $all) {
            $io->section('📋 Attribution automatique des demandes');
            $assignedCount = $this->assignmentService->processUnassignedRequests();
            $results[] = "✅ {$assignedCount} demande(s) attribuée(s)";
            $io->success("{$assignedCount} demande(s) attribuée(s) automatiquement");
        }

        // Notifications urgentes
        if ($urgent || $all) {
            $io->section('⚠️  Notifications urgentes');
            $urgentCount = $this->assignmentService->notifyUrgentRequests();
            $results[] = "⚠️  {$urgentCount} notification(s) urgente(s) envoyée(s)";
            $io->warning("{$urgentCount} notification(s) urgente(s) envoyée(s)");
        }

        // Vérification retards
        if ($overdue || $all) {
            $io->section('🔴 Vérification des demandes en retard');
            $overdueCount = $this->assignmentService->checkOverdueRequests();
            $results[] = "🔴 {$overdueCount} alerte(s) de retard envoyée(s)";
            $io->error("{$overdueCount} demande(s) en retard détectée(s)");
        }

        // Résumé
        $io->section('📊 Résumé');
        $io->listing($results);

        $io->success('Gestion des demandes de maintenance terminée avec succès !');

        return Command::SUCCESS;
    }
}

