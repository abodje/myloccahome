<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Crée un nouvel utilisateur',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email de l\'utilisateur')
            ->addArgument('password', InputArgument::REQUIRED, 'Mot de passe')
            ->addArgument('firstName', InputArgument::REQUIRED, 'Prénom')
            ->addArgument('lastName', InputArgument::REQUIRED, 'Nom')
            ->addOption('role', 'r', InputOption::VALUE_REQUIRED, 'Rôle (admin, manager, tenant)', 'tenant')
            ->addOption('phone', 'p', InputOption::VALUE_REQUIRED, 'Téléphone')
            ->setHelp('Cette commande permet de créer un nouvel utilisateur avec un rôle spécifique.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $firstName = $input->getArgument('firstName');
        $lastName = $input->getArgument('lastName');
        $roleOption = $input->getOption('role');
        $phone = $input->getOption('phone');

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $io->error("Un utilisateur avec l'email {$email} existe déjà !");
            return Command::FAILURE;
        }

        // Déterminer le rôle
        $role = match(strtolower($roleOption)) {
            'admin' => 'ROLE_ADMIN',
            'manager', 'gestionnaire' => 'ROLE_MANAGER',
            'tenant', 'locataire' => 'ROLE_TENANT',
            default => 'ROLE_USER'
        };

        // Créer l'utilisateur
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setRoles([$role]);

        if ($phone) {
            $user->setPhone($phone);
        }

        // Hash du mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Sauvegarder
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success([
            '✅ Utilisateur créé avec succès !',
            '',
            "Email: {$email}",
            "Nom: {$firstName} {$lastName}",
            "Rôle: {$role}",
            $phone ? "Téléphone: {$phone}" : '',
            '',
            'Vous pouvez maintenant vous connecter sur /login'
        ]);

        return Command::SUCCESS;
    }
}

