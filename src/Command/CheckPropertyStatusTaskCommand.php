<?php

namespace App\Command;

use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-property-status-task',
    description: 'Vérifie la tâche UPDATE_PROPERTY_STATUS dans la base de données',
)]
class CheckPropertyStatusTaskCommand extends Command
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

        $io->title('Vérification de la tâche UPDATE_PROPERTY_STATUS');

        // Chercher la tâche
        $task = $this->entityManager->getRepository(Task::class)
            ->findOneBy(['type' => 'UPDATE_PROPERTY_STATUS']);

        if (!$task) {
            $io->error('La tâche UPDATE_PROPERTY_STATUS n\'a pas été trouvée dans la base de données.');
            return Command::FAILURE;
        }

        $io->writeln('<fg=green>✅ Tâche trouvée !</>');
        $io->writeln(sprintf('ID: %d', $task->getId()));
        $io->writeln(sprintf('Nom: %s', $task->getName()));
        $io->writeln(sprintf('Type: %s', $task->getType()));
        $io->writeln(sprintf('Description: %s', $task->getDescription()));
        $io->writeln(sprintf('Fréquence: %s', $task->getFrequency()));
        $io->writeln(sprintf('Statut: %s', $task->getStatus()));
        $io->writeln(sprintf('Dernière exécution: %s', $task->getLastRunAt() ? $task->getLastRunAt()->format('d/m/Y H:i:s') : 'Jamais'));
        $io->writeln(sprintf('Prochaine exécution: %s', $task->getNextRunAt() ? $task->getNextRunAt()->format('d/m/Y H:i:s') : 'Non programmée'));
        $io->writeln(sprintf('Nombre d\'exécutions: %d', $task->getRunCount() ?? 0));
        $io->writeln(sprintf('Succès: %d', $task->getSuccessCount() ?? 0));
        $io->writeln(sprintf('Échecs: %d', $task->getFailureCount() ?? 0));

        if ($task->getResult()) {
            $io->writeln(sprintf('Dernier résultat: %s', $task->getResult()));
        }

        if ($task->getLastError()) {
            $io->writeln(sprintf('<fg=red>Dernière erreur: %s</>', $task->getLastError()));
        }

        $io->newLine();
        $io->writeln('<fg=cyan>Paramètres:</>');
        $parameters = $task->getParameters() ?? [];
        foreach ($parameters as $key => $value) {
            $io->writeln(sprintf('  %s: %s', $key, is_bool($value) ? ($value ? 'true' : 'false') : $value));
        }

        return Command::SUCCESS;
    }
}
