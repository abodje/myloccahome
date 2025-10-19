<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\Organization;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:fix-demo-user',
    description: 'Crée ou corrige l\'utilisateur pour une organisation de démo.',
)]
class FixDemoUserCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('demoCode', InputArgument::REQUIRED, 'Code de la démo (subdomain)')
            ->setHelp('Cette commande crée ou corrige l\'utilisateur pour une organisation de démo spécifiée.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $demoCode = $input->getArgument('demoCode');

        try {
            // Trouver l'organisation de démo
            $organization = $this->entityManager->getRepository(Organization::class)
                ->findOneBy(['subdomain' => $demoCode, 'isDemo' => true]);

            if (!$organization) {
                $io->error("Organisation de démo avec le code '{$demoCode}' introuvable.");
                return Command::FAILURE;
            }

            $io->info("Organisation trouvée : {$organization->getName()}");

            // Vérifier si un utilisateur existe déjà pour cette organisation
            $existingUser = $this->entityManager->getRepository(User::class)
                ->findOneBy(['organization' => $organization]);

            if ($existingUser) {
                $io->info("Utilisateur existant trouvé : {$existingUser->getEmail()}");

                // Vérifier que l'utilisateur a les bonnes informations
                if (!$existingUser->getCompany()) {
                    $existingUser->setCompany($organization->getCompanies()->first());
                    $this->entityManager->flush();
                    $io->success("Société assignée à l'utilisateur existant.");
                }

                return Command::SUCCESS;
            }

            // Créer un nouvel utilisateur pour cette organisation
            $user = new User();

            // Extraire le nom depuis le nom de l'organisation
            $orgName = $organization->getName();
            if (preg_match('/Organisation Démo - (.+)/', $orgName, $matches)) {
                $fullName = trim($matches[1]);
                $nameParts = explode(' ', $fullName);
                $user->setFirstName($nameParts[0] ?? 'Demo');
                $user->setLastName(implode(' ', array_slice($nameParts, 1)) ?: 'User');
            } else {
                $user->setFirstName('Demo');
                $user->setLastName('User');
            }

            $user->setEmail("demo-{$demoCode}@mylocca.com");
            $user->setRoles(['ROLE_ADMIN']);
            $user->setOrganization($organization);

            // Assigner la première société de l'organisation
            $company = $organization->getCompanies()->first();
            if ($company) {
                $user->setCompany($company);
            }

            // Définir un mot de passe par défaut
            $plainPassword = 'demo123';
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            // $user->setIsVerified(true); // Cette méthode n'existe pas dans l'entité User
            $user->setCreatedAt(new \DateTime());

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $io->success("Utilisateur de démo créé avec succès !");
            $io->table(
                ['Propriété', 'Valeur'],
                [
                    ['Email', $user->getEmail()],
                    ['Nom complet', $user->getFullName()],
                    ['Rôles', implode(', ', $user->getRoles())],
                    ['Organisation', $organization->getName()],
                    ['Mot de passe', $plainPassword],
                ]
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error("Erreur lors de la création de l'utilisateur : " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
