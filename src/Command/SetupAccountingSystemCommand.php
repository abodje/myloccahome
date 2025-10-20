<?php

namespace App\Command;

use App\Service\AccountingConfigService;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:setup-accounting-system',
    description: 'Configure le système comptable complet (migration + configurations)',
)]
class SetupAccountingSystemCommand extends Command
{
    private AccountingConfigService $configService;
    private Connection $connection;

    public function __construct(AccountingConfigService $configService, Connection $connection)
    {
        $this->configService = $configService;
        $this->connection = $connection;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Configuration du système comptable professionnel');

        try {
            // Étape 1: Vérifier si la table existe
            $io->section('Étape 1: Vérification de la table accounting_configuration');

            $tableExists = $this->connection->createSchemaManager()->tablesExist(['accounting_configuration']);

            if (!$tableExists) {
                $io->writeln('Table accounting_configuration non trouvée. Création...');

                // Créer la table manuellement
                $this->connection->executeStatement('
                    CREATE TABLE accounting_configuration (
                        id INT AUTO_INCREMENT NOT NULL,
                        operation_type VARCHAR(100) NOT NULL,
                        account_number VARCHAR(20) NOT NULL,
                        account_label VARCHAR(255) NOT NULL,
                        entry_type VARCHAR(10) NOT NULL,
                        description VARCHAR(255) NOT NULL,
                        reference VARCHAR(255) DEFAULT NULL,
                        category VARCHAR(100) NOT NULL,
                        is_active TINYINT(1) DEFAULT 1,
                        notes LONGTEXT DEFAULT NULL,
                        created_at DATETIME NOT NULL,
                        updated_at DATETIME DEFAULT NULL,
                        UNIQUE INDEX UNIQ_ACCOUNTING_CONFIG_OPERATION_TYPE (operation_type),
                        PRIMARY KEY(id)
                    ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
                ');

                $io->success('✅ Table accounting_configuration créée avec succès !');
            } else {
                $io->success('✅ Table accounting_configuration existe déjà.');
            }

            // Étape 2: Créer les configurations par défaut
            $io->section('Étape 2: Création des configurations comptables par défaut');

            $this->configService->createDefaultConfigurations();

            $io->success('✅ Configurations comptables par défaut créées avec succès !');

            // Étape 3: Afficher les configurations créées
            $io->section('Étape 3: Vérification des configurations');

            $configurations = $this->configService->getAllActiveConfigurations();

            $io->table(
                ['Type', 'Compte', 'Libellé', 'Sens', 'Catégorie'],
                array_map(function($config) {
                    return [
                        $config->getOperationType(),
                        $config->getAccountNumber(),
                        $config->getAccountLabel(),
                        $config->getEntryType(),
                        $config->getCategory()
                    ];
                }, $configurations)
            );

            $io->section('Résumé');
            $io->writeln(sprintf('✅ %d configurations comptables créées', count($configurations)));
            $io->writeln('✅ Système comptable professionnel configuré');
            $io->writeln('✅ Interface d\'administration disponible: /admin/accounting-config');

        } catch (\Exception $e) {
            $io->error(sprintf('Erreur lors de la configuration: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
