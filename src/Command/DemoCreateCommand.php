<?php

namespace App\Command;

use App\Entity\User;
use App\Service\DemoEnvironmentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @AsCommand(name="app:demo:create", description="Crée un environnement de démo pour un utilisateur")
 */
class DemoCreateCommand extends Command
{
    private DemoEnvironmentService $demoService;
    private EntityManagerInterface $entityManager;

    public function __construct(DemoEnvironmentService $demoService, EntityManagerInterface $entityManager)
    {
        $this->demoService = $demoService;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email de l\'utilisateur')
            ->addOption('subdomain', 's', InputOption::VALUE_OPTIONAL, 'Sous-domaine personnalisé')
            ->addOption('days', 'd', InputOption::VALUE_OPTIONAL, 'Durée en jours (défaut: 7)', 7)
            ->setHelp('Cette commande crée un environnement de démo pour un utilisateur spécifique.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        $subdomain = $input->getOption('subdomain');
        $days = (int) $input->getOption('days');

        $io->title('🚀 Création d\'un environnement de démo');

        // Trouver l'utilisateur
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            $io->error("❌ Utilisateur avec l'email '{$email}' non trouvé.");
            return Command::FAILURE;
        }

        $io->section('👤 Informations utilisateur');
        $io->table(
            ['Propriété', 'Valeur'],
            [
                ['Email', $user->getEmail()],
                ['Nom', $user->getFirstName() . ' ' . $user->getLastName()],
                ['Rôle', implode(', ', $user->getRoles())],
            ]
        );

        // Vérifier si une démo existe déjà
        $existingDemos = $this->demoService->listDemoEnvironments();
        $userDemo = null;
        foreach ($existingDemos as $demo) {
            if ($demo['email'] === $email) {
                $userDemo = $demo;
                break;
            }
        }

        if ($userDemo) {
            $io->warning("⚠️ Une démo existe déjà pour cet utilisateur :");
            $io->table(
                ['Propriété', 'Valeur'],
                [
                    ['Sous-domaine', $userDemo['subdomain']],
                    ['URL', $userDemo['demo_url']],
                    ['Statut', $userDemo['is_active'] ? 'Active' : 'Inactive'],
                    ['Expire le', $userDemo['trial_ends_at'] ? $userDemo['trial_ends_at']->format('Y-m-d H:i:s') : 'Jamais'],
                ]
            );

            if (!$io->confirm('Voulez-vous créer une nouvelle démo ?', false)) {
                $io->info('Création annulée.');
                return Command::SUCCESS;
            }
        }

        // Créer la démo
        $io->section('🔨 Création de la démo...');

        $result = $this->demoService->createDemoEnvironment($user);

        if ($result['success']) {
            $io->success('✅ Démo créée avec succès !');

            $io->section('📋 Informations de la démo');
            $io->table(
                ['Propriété', 'Valeur'],
                [
                    ['Sous-domaine', $result['subdomain']],
                    ['URL', $result['demo_url']],
                    ['Organisation', $result['organization']->getName()],
                    ['Société', $result['company']->getName()],
                    ['Durée', "{$days} jours"],
                ]
            );

            // Prolonger si nécessaire
            if ($days > 7) {
                $io->section('⏰ Prolongation de la démo...');
                $extendResult = $this->demoService->extendDemoEnvironment($result['subdomain'], $days - 7);

                if ($extendResult['success']) {
                    $io->success("✅ Démo prolongée jusqu'au {$extendResult['new_trial_end']}");
                } else {
                    $io->warning("⚠️ Impossible de prolonger la démo : {$extendResult['message']}");
                }
            }

            $io->section('🔗 Accès à la démo');
            $io->text("URL : <fg=blue>{$result['demo_url']}</>");
            $io->text("Message : {$result['message']}");

        } else {
            $io->error("❌ Erreur lors de la création : {$result['message']}");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
