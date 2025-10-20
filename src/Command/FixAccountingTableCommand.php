<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-accounting-table',
    description: 'Corrige la table accounting_configuration',
)]
class FixAccountingTableCommand extends Command
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Correction de la table accounting_configuration');

        try {
            // Supprimer la table si elle existe
            $io->writeln('Suppression de la table existante...');
            $this->connection->executeStatement('DROP TABLE IF EXISTS accounting_configuration');

            // Recréer la table correctement
            $io->writeln('Création de la table corrigée...');
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

            $io->success('✅ Table accounting_configuration corrigée avec succès !');

        } catch (\Exception $e) {
            $io->error(sprintf('Erreur: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
