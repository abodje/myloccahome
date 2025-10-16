<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-super-admin',
    description: 'Crée un compte Super Administrateur MYLOCCA',
)]
class CreateSuperAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        $io->title('🔐 Création d\'un Super Administrateur MYLOCCA');
        $io->warning([
            'Ce compte aura un accès COMPLET à TOUTES les organisations.',
            'Ce rôle est réservé aux propriétaires de la plateforme MYLOCCA.',
        ]);

        // Demander les informations
        $question = new Question('Email du Super Admin: ');
        $email = $helper->ask($input, $output, $question);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $io->error('Email invalide.');
            return Command::FAILURE;
        }

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $io->error('Un utilisateur avec cet email existe déjà.');
            return Command::FAILURE;
        }

        $question = new Question('Prénom: ');
        $firstName = $helper->ask($input, $output, $question);

        $question = new Question('Nom: ');
        $lastName = $helper->ask($input, $output, $question);

        $question = new Question('Mot de passe: ');
        $question->setHidden(true);
        $question->setHiddenFallback(false);
        $password = $helper->ask($input, $output, $question);

        if (strlen($password) < 8) {
            $io->error('Le mot de passe doit contenir au moins 8 caractères.');
            return Command::FAILURE;
        }

        // Créer le Super Admin
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setRoles(['ROLE_SUPER_ADMIN']);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success([
            '✅ Super Administrateur créé avec succès !',
            '',
            "Email: {$email}",
            "Nom: {$firstName} {$lastName}",
            'Rôle: ROLE_SUPER_ADMIN',
            '',
            '⚠️  Ce compte a un accès ILLIMITÉ à toutes les organisations.',
        ]);

        return Command::SUCCESS;
    }
}

