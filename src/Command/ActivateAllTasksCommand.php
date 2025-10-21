<?php

namespace App\Command;

use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:tasks:activate-all',
    description: 'Active toutes les tâches importantes pour la production.',
)]
class ActivateAllTasksCommand extends Command
{
    public function __construct(
        private TaskRepository $taskRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('🔧 Activation de toutes les tâches importantes');

        $importantTaskTypes = [
            'GENERATE_RENTS' => 'Génération automatique des loyers',
            'GENERATE_RENT_DOCUMENTS' => 'Génération des quittances et avis d\'échéances',
            'RENT_RECEIPT' => 'Envoi des quittances de loyer',
            'PAYMENT_REMINDER' => 'Rappels de paiement',
            'LEASE_EXPIRATION_ALERT' => 'Alertes d\'expiration de contrat',
            'SYNC_ACCOUNTING_ENTRIES' => 'Synchronisation des écritures comptables',
            'UPDATE_PROPERTY_STATUS' => 'Mise à jour du statut des propriétés',
            'DEMO_CLEANUP' => 'Nettoyage des environnements de démo',
        ];

        $activated = 0;
        $alreadyActive = 0;

        foreach ($importantTaskTypes as $type => $name) {
            $task = $this->taskRepository->findOneBy(['type' => $type]);

            if (!$task) {
                $io->warning(sprintf('⚠️ Tâche "%s" (%s) non trouvée', $name, $type));
                continue;
            }

            if ($task->isActive()) {
                $io->writeln(sprintf('✅ %s - Déjà active', $name));
                $alreadyActive++;
            } else {
                $task->setStatus('ACTIVE');
                $io->writeln(sprintf('🔄 %s - Activée', $name));
                $activated++;
            }
        }

        if ($activated > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('✅ %d tâches activées avec succès !', $activated));
        }

        if ($alreadyActive > 0) {
            $io->info(sprintf('ℹ️ %d tâches étaient déjà actives', $alreadyActive));
        }

        $io->section('📋 Commandes Cron recommandées pour votre hébergeur :');
        $io->writeln('');
        $io->writeln('1. Génération automatique des loyers (Tous les jours à 9h00) :');
        $io->writeln('   Commande: /usr/local/bin/php /home/Lokaprot/public_html/bin/console app:tasks:run --task-type=GENERATE_RENTS');
        $io->writeln('   Cron: 0 9 * * *');
        $io->writeln('');
        $io->writeln('2. Génération des quittances (Le 1er de chaque mois à 8h00) :');
        $io->writeln('   Commande: /usr/local/bin/php /home/Lokaprot/public_html/bin/console app:tasks:run --task-type=GENERATE_RENT_DOCUMENTS');
        $io->writeln('   Cron: 0 8 1 * *');
        $io->writeln('');
        $io->writeln('3. Envoi des quittances (Tous les jours à 10h00) :');
        $io->writeln('   Commande: /usr/local/bin/php /home/Lokaprot/public_html/bin/console app:tasks:run --task-type=RENT_RECEIPT');
        $io->writeln('   Cron: 0 10 * * *');
        $io->writeln('');
        $io->writeln('4. Rappels de paiement (Tous les jours à 14h00) :');
        $io->writeln('   Commande: /usr/local/bin/php /home/Lokaprot/public_html/bin/console app:tasks:run --task-type=PAYMENT_REMINDER');
        $io->writeln('   Cron: 0 14 * * *');
        $io->writeln('');
        $io->writeln('5. Synchronisation comptable (Tous les jours à 18h00) :');
        $io->writeln('   Commande: /usr/local/bin/php /home/Lokaprot/public_html/bin/console app:tasks:run --task-type=SYNC_ACCOUNTING_ENTRIES');
        $io->writeln('   Cron: 0 18 * * *');
        $io->writeln('');
        $io->writeln('6. Nettoyage des démos (Tous les dimanches à 2h00) :');
        $io->writeln('   Commande: /usr/local/bin/php /home/Lokaprot/public_html/bin/console app:tasks:run --task-type=DEMO_CLEANUP');
        $io->writeln('   Cron: 0 2 * * 0');

        return Command::SUCCESS;
    }
}
