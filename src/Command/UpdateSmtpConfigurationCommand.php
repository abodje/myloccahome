<?php

namespace App\Command;

use App\Service\SmtpConfigurationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-smtp-configuration',
    description: 'Met à jour la configuration SMTP avec les paramètres app.lokapro.tech',
)]
class UpdateSmtpConfigurationCommand extends Command
{
    private SmtpConfigurationService $smtpConfigurationService;

    public function __construct(SmtpConfigurationService $smtpConfigurationService)
    {
        $this->smtpConfigurationService = $smtpConfigurationService;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Mise à jour de la configuration SMTP');

        // Configuration SMTP pour app.lokapro.tech
        $config = [
            'host' => 'app.lokapro.tech',
            'port' => 465,
            'username' => 'info@app.lokapro.tech',
            'password' => 'q+Dy-riz8EBi;oL]',
            'encryption' => 'ssl',
            'auth_mode' => 'login',
        ];

        $io->section('Configuration à appliquer');
        $io->definitionList(
            ['Host' => $config['host']],
            ['Port' => $config['port']],
            ['Username' => $config['username']],
            ['Password' => str_repeat('*', strlen($config['password']))],
            ['Encryption' => $config['encryption']],
            ['Auth Mode' => $config['auth_mode']]
        );

        // Mettre à jour la configuration
        $success = $this->smtpConfigurationService->updateSmtpConfiguration($config);

        if ($success) {
            $io->success('✅ Configuration SMTP mise à jour avec succès !');

            // Afficher la nouvelle configuration
            $io->section('Nouvelle configuration');
            $newConfig = $this->smtpConfigurationService->getSmtpConfiguration();
            $io->definitionList(
                ['Host' => $newConfig['host']],
                ['Port' => $newConfig['port']],
                ['Username' => $newConfig['username']],
                ['Password' => str_repeat('*', strlen($newConfig['password']))],
                ['Encryption' => $newConfig['encryption']],
                ['Auth Mode' => $newConfig['auth_mode']]
            );

            $io->writeln('DSN généré: ' . $this->smtpConfigurationService->getSmtpDsn());

            return Command::SUCCESS;
        } else {
            $io->error('❌ Erreur lors de la mise à jour de la configuration SMTP');
            return Command::FAILURE;
        }
    }
}
