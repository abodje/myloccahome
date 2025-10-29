<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Crée un utilisateur administrateur dans le système.',
)]
class CreateAdminUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_OPTIONAL, 'Email de l\'administrateur')
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Mot de passe de l\'administrateur')
            ->addOption('firstName', null, InputOption::VALUE_OPTIONAL, 'Prénom de l\'administrateur')
            ->addOption('lastName', null, InputOption::VALUE_OPTIONAL, 'Nom de l\'administrateur')
            ->addOption('super-admin', null, InputOption::VALUE_NONE, 'Créer un super administrateur (ROLE_SUPER_ADMIN)')
            ->setHelp(
                <<<'HELP'
La <info>%command.name%</info> crée un nouvel utilisateur administrateur dans le système.

<info>php %command.full_name%</info>

Vous pouvez aussi fournir les informations en ligne de commande :

  <info>php %command.full_name% --email=admin@example.com --password=secret --firstName=Admin --lastName=User</info>

Pour créer un super administrateur :

  <info>php %command.full_name% --super-admin --email=admin@example.com</info>
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        $io->title('🔐 Création d\'un utilisateur administrateur');

        // Récupération ou demande de l'email
        $email = $input->getOption('email');
        if (!$email) {
            $question = new Question('Email de l\'administrateur : ');
            $question->setValidator(function ($answer) {
                if (!filter_var($answer, FILTER_VALIDATE_EMAIL)) {
                    throw new \RuntimeException('Veuillez entrer un email valide.');
                }
                return $answer;
            });
            $email = $helper->ask($input, $output, $question);
        }

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if ($existingUser) {
            $io->warning("Un utilisateur avec l'email {$email} existe déjà.");

            if (!$io->confirm('Voulez-vous mettre à jour cet utilisateur et lui donner les droits administrateur ?', false)) {
                $io->info('Opération annulée.');
                return Command::FAILURE;
            }

            // Mettre à jour l'utilisateur existant
            $existingUser->setRoles(['ROLE_SUPER_ADMIN', 'ROLE_ADMIN', 'ROLE_MANAGER']);
            $existingUser->setIsActive(true);

            // Demander si on veut changer le mot de passe
            if ($io->confirm('Voulez-vous changer le mot de passe de cet utilisateur ?', false)) {
                $password = $this->getPassword($input, $output, $helper);
                $hashedPassword = $this->passwordHasher->hashPassword($existingUser, $password);
                $existingUser->setPassword($hashedPassword);
            }

            $this->entityManager->flush();

            $io->success('Utilisateur mis à jour avec succès !');
            $this->displayUserInfo($io, $existingUser);

            return Command::SUCCESS;
        }

        // Récupération ou demande du prénom
        $firstName = $input->getOption('firstName');
        if (!$firstName) {
            $question = new Question('Prénom : ');
            $question->setValidator(function ($answer) {
                if (empty(trim($answer))) {
                    throw new \RuntimeException('Le prénom ne peut pas être vide.');
                }
                return trim($answer);
            });
            $firstName = $helper->ask($input, $output, $question);
        }

        // Récupération ou demande du nom
        $lastName = $input->getOption('lastName');
        if (!$lastName) {
            $question = new Question('Nom : ');
            $question->setValidator(function ($answer) {
                if (empty(trim($answer))) {
                    throw new \RuntimeException('Le nom ne peut pas être vide.');
                }
                return trim($answer);
            });
            $lastName = $helper->ask($input, $output, $question);
        }

        // Récupération ou demande du mot de passe
        $password = $this->getPassword($input, $output, $helper);

        // Déterminer les rôles
        $isSuperAdmin = $input->getOption('super-admin');
        $roles = $isSuperAdmin
            ? ['ROLE_SUPER_ADMIN', 'ROLE_ADMIN', 'ROLE_MANAGER', 'ROLE_USER']
            : ['ROLE_ADMIN', 'ROLE_MANAGER', 'ROLE_USER'];

        try {
            // Créer le nouvel utilisateur
            $user = new User();
            $user->setEmail($email);
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setRoles($roles);
          //  $user->setIsActive(true);
            $user->setCreatedAt(new \DateTime());

            // Hasher le mot de passe
            $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            // Valider l'entité
            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
                }
                $io->error('Erreurs de validation :');
                foreach ($errorMessages as $msg) {
                    $io->error($msg);
                }
                return Command::FAILURE;
            }

            // Sauvegarder dans la base de données
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $io->success('✅ Utilisateur administrateur créé avec succès !');
            $this->displayUserInfo($io, $user, $password);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors de la création de l\'administrateur : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Demande le mot de passe de manière sécurisée
     */
    private function getPassword(InputInterface $input, OutputInterface $output, $helper): string
    {
        $password = $input->getOption('password');

        if (!$password) {
            $question = new Question('Mot de passe : ');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $question->setValidator(function ($answer) {
                if (strlen($answer) < 6) {
                    throw new \RuntimeException('Le mot de passe doit contenir au moins 6 caractères.');
                }
                return $answer;
            });

            $password = $helper->ask($input, $output, $question);

            // Confirmation du mot de passe
            $question = new Question('Confirmer le mot de passe : ');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $confirmPassword = $helper->ask($input, $output, $question);

            if ($password !== $confirmPassword) {
                throw new \RuntimeException('Les mots de passe ne correspondent pas.');
            }
        }

        return $password;
    }

    /**
     * Affiche les informations de l'utilisateur créé
     */
    private function displayUserInfo(SymfonyStyle $io, User $user, ?string $plainPassword = null): void
    {
        $data = [
            ['Email', $user->getEmail()],
            ['Nom complet', $user->getFullName()],
            ['Rôles', implode(', ', $user->getRoles())],
            ['Statut', $user->isActive() ? 'Actif ✅' : 'Inactif ❌'],
            ['Date de création', $user->getCreatedAt()?->format('d/m/Y H:i:s')],
        ];

        if ($plainPassword) {
            $data[] = ['Mot de passe', $plainPassword . ' (à changer après la première connexion)'];
        }

        $io->table(
            ['Propriété', 'Valeur'],
            $data
        );

        $io->note([
            'Gardez ces informations en sécurité !',
            'Il est recommandé de changer le mot de passe après la première connexion.',
        ]);
    }
}

