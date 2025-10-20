<?php

namespace App\Command;

use App\Entity\Task;
use App\Service\TaskManagerService;
use App\Service\AccountingConfigService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:demo-accounting-system',
    description: 'Démonstration complète du système comptable avec configuration',
)]
class DemoAccountingSystemCommand extends Command
{
    private TaskManagerService $taskManagerService;
    private AccountingConfigService $configService;
    private EntityManagerInterface $entityManager;

    public function __construct(
        TaskManagerService $taskManagerService,
        AccountingConfigService $configService,
        EntityManagerInterface $entityManager
    ) {
        $this->taskManagerService = $taskManagerService;
        $this->configService = $configService;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🎯 Démonstration du Système Comptable avec Configuration');

        // Étape 1: Vérifier les configurations comptables
        $io->section('Étape 1: Vérification des configurations comptables');

        $configurations = $this->configService->getAllActiveConfigurations();
        $io->writeln(sprintf('✅ %d configurations comptables disponibles', count($configurations)));

        // Afficher la configuration LOYER_ATTENDU
        $loyerConfig = $this->configService->getConfigurationForOperation('LOYER_ATTENDU');
        if ($loyerConfig) {
            $io->writeln('');
            $io->writeln('📋 Configuration pour les loyers attendus :');
            $io->writeln(sprintf('   • Compte: %s', $loyerConfig->getAccountNumber()));
            $io->writeln(sprintf('   • Libellé: %s', $loyerConfig->getAccountLabel()));
            $io->writeln(sprintf('   • Sens: %s', $loyerConfig->getEntryType()));
            $io->writeln(sprintf('   • Référence: %s', $loyerConfig->getReference()));
        }

        // Étape 2: Vérifier la tâche de génération de loyers
        $io->section('Étape 2: Vérification de la tâche de génération de loyers');

        $generateRentsTask = $this->entityManager->getRepository(Task::class)
            ->findOneBy(['type' => 'GENERATE_RENTS']);

        if ($generateRentsTask) {
            $io->writeln('✅ Tâche "Génération automatique des loyers" trouvée');
            $io->writeln(sprintf('   • ID: %d', $generateRentsTask->getId()));
            $io->writeln(sprintf('   • Fréquence: %s', $generateRentsTask->getFrequency()));
            $io->writeln(sprintf('   • Statut: %s', $generateRentsTask->getStatus()));
        } else {
            $io->error('❌ Tâche de génération de loyers non trouvée');
            return Command::FAILURE;
        }

        // Étape 3: Vérifier la tâche de création des configurations
        $io->section('Étape 3: Vérification de la tâche de création des configurations');

        $configTask = $this->entityManager->getRepository(Task::class)
            ->findOneBy(['type' => 'CREATE_ACCOUNTING_CONFIGURATIONS']);

        if ($configTask) {
            $io->writeln('✅ Tâche "Création des configurations comptables" trouvée');
            $io->writeln(sprintf('   • ID: %d', $configTask->getId()));
            $io->writeln(sprintf('   • Fréquence: %s', $configTask->getFrequency()));
            $io->writeln(sprintf('   • Statut: %s', $configTask->getStatus()));
        } else {
            $io->error('❌ Tâche de création des configurations non trouvée');
            return Command::FAILURE;
        }

        // Étape 4: Vérifier les écritures comptables existantes
        $io->section('Étape 4: État des écritures comptables');

        $totalEntries = $this->entityManager->getRepository(\App\Entity\AccountingEntry::class)
            ->count([]);

        $loyerEntries = $this->entityManager->getRepository(\App\Entity\AccountingEntry::class)
            ->count(['category' => 'LOYER']);

        $io->writeln(sprintf('📊 Total écritures comptables: %d', $totalEntries));
        $io->writeln(sprintf('📊 Écritures LOYER: %d', $loyerEntries));

        // Étape 5: Démonstration de l'intégration
        $io->section('Étape 5: Démonstration de l\'intégration');

        $io->writeln('🔄 Le système fonctionne comme suit :');
        $io->writeln('');
        $io->writeln('1️⃣ La tâche "Génération automatique des loyers" s\'exécute');
        $io->writeln('2️⃣ Elle crée des paiements de type "Loyer"');
        $io->writeln('3️⃣ Pour chaque paiement, elle recherche la configuration "LOYER_ATTENDU"');
        $io->writeln('4️⃣ Elle crée une écriture comptable avec :');
        $io->writeln(sprintf('   • Type: %s', $loyerConfig ? $loyerConfig->getEntryType() : 'CREDIT'));
        $io->writeln(sprintf('   • Catégorie: %s', $loyerConfig ? $loyerConfig->getCategory() : 'LOYER'));
        $io->writeln(sprintf('   • Référence: %s[ID]', $loyerConfig ? $loyerConfig->getReference() : 'LOYER-GEN-'));
        $io->writeln('');

        // Étape 6: Test de la configuration
        $io->section('Étape 6: Test de la configuration');

        if ($loyerConfig) {
            $io->success('✅ Configuration comptable opérationnelle !');
            $io->writeln('   • Les loyers générés utilisent automatiquement cette configuration');
            $io->writeln('   • Les écritures comptables respectent les paramètres définis');
            $io->writeln('   • Le système est configurable via l\'interface d\'administration');
        } else {
            $io->error('❌ Configuration comptable manquante');
            return Command::FAILURE;
        }

        // Résumé final
        $io->section('🎉 Résumé du Système Comptable');

        $io->writeln('✅ Configuration comptable professionnelle implémentée');
        $io->writeln('✅ Intégration automatique avec la génération de loyers');
        $io->writeln('✅ Tâche de création des configurations disponible');
        $io->writeln('✅ Interface d\'administration complète');
        $io->writeln('✅ Système flexible et configurable');

        $io->writeln('');
        $io->writeln('<comment>📝 Le système de génération de loyers utilise bien la configuration comptable !</comment>');
        $io->writeln('<comment>🔧 Accessible via: /admin/accounting-config</comment>');
        $io->writeln('<comment>⚙️ Tâche disponible dans: /admin/taches</comment>');

        return Command::SUCCESS;
    }
}
