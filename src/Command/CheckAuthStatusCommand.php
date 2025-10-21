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
    name: 'app:check-auth-status',
    description: 'Vérifie l\'état de l\'authentification et des sessions',
)]
class CheckAuthStatusCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('user-id', 'u', InputOption::VALUE_REQUIRED, 'ID de l\'utilisateur à vérifier');
        $this->addOption('check-sessions', 's', InputOption::VALUE_NONE, 'Vérifier les sessions actives');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Vérification de l\'état d\'authentification');

        // 1. Vérifier les utilisateurs en base
        $io->section('1. Utilisateurs en base de données');

        $users = $this->entityManager->getRepository(User::class)->findAll();
        $io->writeln(sprintf("Nombre d'utilisateurs: %d", count($users)));

        foreach ($users as $user) {
            $io->writeln(sprintf("  - ID: %d, Email: %s, Rôles: %s",
                $user->getId(),
                $user->getEmail(),
                implode(', ', $user->getRoles())
            ));
        }

        // 2. Vérifier un utilisateur spécifique
        $userId = $input->getOption('user-id');
        if ($userId) {
            $io->section('2. Vérification utilisateur spécifique');

            $user = $this->entityManager->getRepository(User::class)->find($userId);
            if (!$user) {
                $io->error("Utilisateur avec l'ID $userId non trouvé");
                return Command::FAILURE;
            }

            $io->writeln(sprintf("👤 Utilisateur: %s", $user->getEmail()));
            $io->writeln(sprintf("   ID: %d", $user->getId()));
            $io->writeln(sprintf("   Rôles: %s", implode(', ', $user->getRoles())));
            $io->writeln(sprintf("   Organisation: %s", $user->getOrganization()?->getName() ?? 'Aucune'));
            $io->writeln(sprintf("   Créé le: %s", $user->getCreatedAt()?->format('Y-m-d H:i:s') ?? 'Inconnu'));

            // Vérifier les méthodes d'authentification
            $io->writeln("\n🔐 Méthodes d'authentification:");
            $io->writeln(sprintf("   getPassword(): %s", $user->getPassword() ? 'Défini' : 'Non défini'));
            $io->writeln(sprintf("   getUserIdentifier(): %s", $user->getUserIdentifier()));
            $io->writeln(sprintf("   isAccountNonExpired(): %s", $user->isAccountNonExpired() ? 'Oui' : 'Non'));
            $io->writeln(sprintf("   isAccountNonLocked(): %s", $user->isAccountNonLocked() ? 'Oui' : 'Non'));
            $io->writeln(sprintf("   isCredentialsNonExpired(): %s", $user->isCredentialsNonExpired() ? 'Oui' : 'Non'));
            $io->writeln(sprintf("   isEnabled(): %s", $user->isEnabled() ? 'Oui' : 'Non'));
        }

        // 3. Vérifier les sessions si demandé
        if ($input->getOption('check-sessions')) {
            $io->section('3. Sessions actives');

            $sessionPath = ini_get('session.save_path') ?: sys_get_temp_dir();
            $io->writeln(sprintf("Répertoire des sessions: %s", $sessionPath));

            if (is_dir($sessionPath)) {
                $sessions = glob($sessionPath . '/sess_*');
                $io->writeln(sprintf("Nombre de sessions: %d", count($sessions)));

                foreach (array_slice($sessions, 0, 5) as $session) {
                    $io->writeln(sprintf("  - %s", basename($session)));
                }
            } else {
                $io->writeln("❌ Répertoire des sessions non trouvé");
            }
        }

        // 4. Recommandations
        $io->section('4. Recommandations pour résoudre les problèmes d\'authentification');

        $recommendations = [
            '🔍 Vérifiez que l\'utilisateur est bien connecté dans l\'interface web',
            '🔐 Vérifiez que la session n\'a pas expiré',
            '🔄 Essayez de vous déconnecter et reconnecter',
            '🧹 Videz le cache du navigateur',
            '📝 Vérifiez les logs d\'erreur du serveur web',
            '🔧 Vérifiez la configuration de session dans php.ini'
        ];

        foreach ($recommendations as $recommendation) {
            $io->writeln($recommendation);
        }

        return Command::SUCCESS;
    }
}
