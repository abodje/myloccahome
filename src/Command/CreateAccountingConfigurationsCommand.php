<?php

namespace App\Command;

use App\Service\AccountingConfigService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-accounting-configurations',
    description: 'Crée les configurations comptables par défaut',
)]
class CreateAccountingConfigurationsCommand extends Command
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

        $io->title('Création des configurations comptables par défaut');

        try {
            $this->configService->createDefaultConfigurations();

            $io->success('Configurations comptables par défaut créées avec succès !');

            // Afficher les configurations créées
            $configurations = $this->configService->getAllActiveConfigurations();

            $io->section('Configurations créées :');
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

            $io->writeln('');
            $io->writeln('<comment>Les configurations sont maintenant disponibles dans l\'interface d\'administration.</comment>');
            $io->writeln('<comment>Route: /admin/accounting-config</comment>');

        } catch (\Exception $e) {
            $io->error(sprintf('Erreur lors de la création des configurations: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
