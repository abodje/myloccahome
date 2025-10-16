<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cleanup-incomplete-accounts',
    description: 'Nettoie les comptes utilisateurs incomplets (sans organisation ou environnement de démo incomplet)',
)]
class CleanupIncompleteAccountsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche les comptes qui seraient supprimés sans les supprimer réellement')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Nettoyer uniquement un compte spécifique par email')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force la suppression sans confirmation')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $emailFilter = $input->getOption('email');
        $force = $input->getOption('force');

        $io->title('🧹 Nettoyage des comptes incomplets');

        // Trouver les utilisateurs sans organisation
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('u')
           ->from(User::class, 'u')
           ->where('u.organization IS NULL');

        if ($emailFilter) {
            $qb->andWhere('u.email = :email')
               ->setParameter('email', $emailFilter);
        }

        $incompleteUsers = $qb->getQuery()->getResult();

        if (empty($incompleteUsers)) {
            $io->success('Aucun compte incomplet trouvé.');
            return Command::SUCCESS;
        }

        $io->section('Comptes incomplets trouvés :');
        $io->table(
            ['ID', 'Email', 'Nom', 'Date création', 'Rôles'],
            array_map(function (User $user) {
                return [
                    $user->getId(),
                    $user->getEmail(),
                    $user->getFirstName() . ' ' . $user->getLastName(),
                    $user->getCreatedAt() ? $user->getCreatedAt()->format('Y-m-d H:i:s') : 'N/A',
                    implode(', ', $user->getRoles())
                ];
            }, $incompleteUsers)
        );

        $io->note(sprintf('%d compte(s) incomplet(s) trouvé(s).', count($incompleteUsers)));

        if ($dryRun) {
            $io->warning('Mode DRY-RUN : Aucune suppression réelle effectuée.');
            return Command::SUCCESS;
        }

        // Demander confirmation
        if (!$force) {
            if (!$io->confirm('Voulez-vous vraiment supprimer ces comptes ?', false)) {
                $io->info('Opération annulée.');
                return Command::SUCCESS;
            }
        }

        // Supprimer les comptes
        $deletedCount = 0;
        foreach ($incompleteUsers as $user) {
            try {
                $email = $user->getEmail();
                $this->entityManager->remove($user);
                $this->entityManager->flush();
                $io->writeln("✅ Compte supprimé : {$email}");
                $deletedCount++;
            } catch (\Exception $e) {
                $io->error("Erreur lors de la suppression du compte {$user->getEmail()} : " . $e->getMessage());
            }
        }

        $io->success(sprintf('✅ %d compte(s) incomplet(s) supprimé(s) avec succès.', $deletedCount));

        return Command::SUCCESS;
    }
}

