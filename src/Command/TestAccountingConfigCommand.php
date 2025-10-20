<?php

namespace App\Command;

use App\Service\AccountingConfigService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-accounting-config',
    description: 'Test le système de configuration comptable',
)]
class TestAccountingConfigCommand extends Command
{
    private AccountingConfigService $configService;

    public function __construct(AccountingConfigService $configService)
    {
        $this->configService = $configService;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test du système de configuration comptable');

        // Test 1: Récupérer toutes les configurations
        $io->section('Test 1: Récupération des configurations');

        $configurations = $this->configService->getAllActiveConfigurations();
        $io->writeln(sprintf('Nombre de configurations actives: %d', count($configurations)));

        if (empty($configurations)) {
            $io->warning('Aucune configuration trouvée. Exécutez d\'abord: php bin/console app:create-accounting-configurations');
            return Command::SUCCESS;
        }

        // Test 2: Recherche par type d'opération
        $io->section('Test 2: Recherche par type d\'opération');

        $testOperations = ['LOYER_ATTENDU', 'CHARGE', 'TRAVAUX'];

        foreach ($testOperations as $operation) {
            $config = $this->configService->getConfigurationForOperation($operation);

            if ($config) {
                $io->writeln(sprintf('✅ %s: %s (%s) - %s',
                    $operation,
                    $config->getAccountNumber(),
                    $config->getAccountLabel(),
                    $config->getEntryType()
                ));
            } else {
                $io->writeln(sprintf('❌ %s: Configuration non trouvée', $operation));
            }
        }

        // Test 3: Recherche par catégorie
        $io->section('Test 3: Recherche par catégorie');

        $testCategories = ['LOYER', 'CHARGE', 'TRAVAUX'];

        foreach ($testCategories as $category) {
            $configs = $this->configService->getConfigurationsByCategory($category);

            if (!empty($configs)) {
                $io->writeln(sprintf('✅ %s: %d configuration(s) trouvée(s)', $category, count($configs)));
                foreach ($configs as $config) {
                    $io->writeln(sprintf('   - %s: %s (%s)',
                        $config->getOperationType(),
                        $config->getAccountNumber(),
                        $config->getEntryType()
                    ));
                }
            } else {
                $io->writeln(sprintf('❌ %s: Aucune configuration trouvée', $category));
            }
        }

        // Test 4: Validation des configurations
        $io->section('Test 4: Validation des configurations');

        $validConfigs = 0;
        $invalidConfigs = 0;

        foreach ($configurations as $config) {
            $errors = $this->configService->validateConfiguration($config);

            if (empty($errors)) {
                $validConfigs++;
            } else {
                $invalidConfigs++;
                $io->writeln(sprintf('❌ %s: %s', $config->getOperationType(), implode(', ', $errors)));
            }
        }

        $io->writeln(sprintf('Configurations valides: %d', $validConfigs));
        $io->writeln(sprintf('Configurations invalides: %d', $invalidConfigs));

        // Test 5: Affichage des types disponibles
        $io->section('Test 5: Types d\'opérations disponibles');

        $operationTypes = $this->configService->getAvailableOperationTypes();
        foreach ($operationTypes as $key => $label) {
            $config = $this->configService->getConfigurationForOperation($key);
            $status = $config ? '✅' : '❌';
            $io->writeln(sprintf('%s %s (%s)', $status, $key, $label));
        }

        // Résumé
        $io->section('Résumé du test');

        if ($validConfigs > 0 && $invalidConfigs === 0) {
            $io->success('✅ Toutes les configurations sont valides et fonctionnelles !');
        } elseif ($validConfigs > 0) {
            $io->warning(sprintf('⚠️ %d configurations valides, %d invalides', $validConfigs, $invalidConfigs));
        } else {
            $io->error('❌ Aucune configuration valide trouvée');
            return Command::FAILURE;
        }

        $io->writeln('');
        $io->writeln('<comment>Le système de configuration comptable est opérationnel !</comment>');
        $io->writeln('<comment>Les écritures comptables utiliseront ces configurations automatiquement.</comment>');

        return Command::SUCCESS;
    }
}
