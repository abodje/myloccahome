<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
	name: 'app:test:contract-config-web',
	description: 'Commande de test: exécution web de configuration de contrat (stub minimal).',
)]
class TestContractConfigWebCommand extends Command
{
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$io = new SymfonyStyle($input, $output);
		$io->success('TestContractConfigWebCommand exécutée (implémentation minimale).');

		return Command::SUCCESS;
	}
}
