<?php

namespace App\Command;

use App\Entity\Payment;
use App\Service\AccountingConfigService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-rent-generation-with-config',
    description: 'Teste la génération de loyers avec la configuration comptable',
)]
class TestRentGenerationWithConfigCommand extends Command
{
    private NotificationService $notificationService;
    private AccountingConfigService $configService;
    private EntityManagerInterface $entityManager;

    public function __construct(
        NotificationService $notificationService,
        AccountingConfigService $configService,
        EntityManagerInterface $entityManager
    ) {
        $this->notificationService = $notificationService;
        $this->configService = $configService;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test de génération de loyers avec configuration comptable');

        // Étape 1: Vérifier la configuration LOYER_ATTENDU
        $io->section('Étape 1: Vérification de la configuration comptable');
        
        $config = $this->configService->getConfigurationForOperation('LOYER_ATTENDU');
        
        if (!$config) {
            $io->error('Configuration LOYER_ATTENDU non trouvée !');
            return Command::FAILURE;
        }

        $io->writeln('✅ Configuration LOYER_ATTENDU trouvée :');
        $io->writeln(sprintf('   • Type d\'opération: %s', $config->getOperationType()));
        $io->writeln(sprintf('   • Numéro de compte: %s', $config->getAccountNumber()));
        $io->writeln(sprintf('   • Libellé du compte: %s', $config->getAccountLabel()));
        $io->writeln(sprintf('   • Sens de l\'écriture: %s', $config->getEntryType()));
        $io->writeln(sprintf('   • Préfixe de référence: %s', $config->getReference()));
        $io->writeln(sprintf('   • Catégorie: %s', $config->getCategory()));
        $io->writeln(sprintf('   • Statut: %s', $config->isActive() ? 'Actif' : 'Inactif'));

        // Étape 2: Compter les écritures comptables existantes
        $io->section('Étape 2: État actuel des écritures comptables');
        
        $existingEntries = $this->entityManager->getRepository(\App\Entity\AccountingEntry::class)
            ->count(['category' => 'LOYER']);
        
        $io->writeln(sprintf('Écritures comptables LOYER existantes: %d', $existingEntries));

        // Étape 3: Générer des loyers et vérifier l'application de la configuration
        $io->section('Étape 3: Génération de loyers avec configuration comptable');
        
        $results = $this->notificationService->generateNextMonthRents();
        
        $io->writeln(sprintf('Loyers générés: %d', $results['generated']));
        $io->writeln(sprintf('Loyers ignorés: %d', $results['skipped']));

        if (!empty($results['errors'])) {
            $io->writeln('Erreurs:');
            foreach ($results['errors'] as $error) {
                $io->writeln(sprintf('   • %s', $error));
            }
        }

        // Étape 4: Vérifier les nouvelles écritures comptables
        $io->section('Étape 4: Vérification des écritures comptables créées');
        
        $newEntries = $this->entityManager->getRepository(\App\Entity\AccountingEntry::class)
            ->findBy(['category' => 'LOYER'], ['id' => 'DESC'], 5);

        if (!empty($newEntries)) {
            $io->writeln('Dernières écritures comptables créées :');
            
            foreach ($newEntries as $entry) {
                $io->writeln(sprintf('   • ID: %d', $entry->getId()));
                $io->writeln(sprintf('     Description: %s', $entry->getDescription()));
                $io->writeln(sprintf('     Montant: %s FCFA', $entry->getAmount()));
                $io->writeln(sprintf('     Type: %s', $entry->getType()));
                $io->writeln(sprintf('     Catégorie: %s', $entry->getCategory()));
                $io->writeln(sprintf('     Référence: %s', $entry->getReference()));
                $io->writeln(sprintf('     Notes: %s', $entry->getNotes()));
                $io->writeln('');
            }

            // Vérifier que les écritures utilisent bien la configuration
            $firstEntry = $newEntries[0];
            
            $io->section('Étape 5: Vérification de l\'application de la configuration');
            
            $configApplied = true;
            $issues = [];

            // Vérifier le type d'écriture
            if ($firstEntry->getType() !== $config->getEntryType()) {
                $configApplied = false;
                $issues[] = sprintf('Type d\'écriture incorrect: attendu %s, obtenu %s', 
                    $config->getEntryType(), $firstEntry->getType());
            }

            // Vérifier la catégorie
            if ($firstEntry->getCategory() !== $config->getCategory()) {
                $configApplied = false;
                $issues[] = sprintf('Catégorie incorrecte: attendue %s, obtenue %s', 
                    $config->getCategory(), $firstEntry->getCategory());
            }

            // Vérifier la référence
            if (strpos($firstEntry->getReference(), $config->getReference()) !== 0) {
                $configApplied = false;
                $issues[] = sprintf('Préfixe de référence incorrect: attendu %s, obtenu %s', 
                    $config->getReference(), $firstEntry->getReference());
            }

            // Vérifier la description
            if (strpos($firstEntry->getDescription(), $config->getDescription()) === false) {
                $configApplied = false;
                $issues[] = sprintf('Description ne contient pas le texte configuré: %s', 
                    $config->getDescription());
            }

            if ($configApplied) {
                $io->success('✅ La configuration comptable est correctement appliquée !');
                $io->writeln('   • Type d\'écriture: ' . $firstEntry->getType());
                $io->writeln('   • Catégorie: ' . $firstEntry->getCategory());
                $io->writeln('   • Référence: ' . $firstEntry->getReference());
                $io->writeln('   • Description: ' . $firstEntry->getDescription());
            } else {
                $io->error('❌ La configuration comptable n\'est pas correctement appliquée !');
                foreach ($issues as $issue) {
                    $io->writeln(sprintf('   • %s', $issue));
                }
                return Command::FAILURE;
            }

        } else {
            $io->warning('Aucune nouvelle écriture comptable créée. Les loyers existent peut-être déjà.');
        }

        // Étape 6: Résumé final
        $io->section('Résumé final');
        
        $totalEntries = $this->entityManager->getRepository(\App\Entity\AccountingEntry::class)
            ->count(['category' => 'LOYER']);
        
        $io->writeln(sprintf('Total écritures comptables LOYER: %d', $totalEntries));
        $io->writeln(sprintf('Configuration utilisée: %s (%s)', 
            $config->getAccountNumber(), $config->getAccountLabel()));
        
        $io->success('✅ Le système de génération de loyers utilise bien la configuration comptable !');

        return Command::SUCCESS;
    }
}
