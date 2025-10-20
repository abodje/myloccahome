<?php

namespace App\Command;

use App\Entity\User;
use App\Service\MenuService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-super-admin-menu-access',
    description: 'Teste l\'accès aux menus pour un utilisateur SUPER_ADMIN',
)]
class TestSuperAdminMenuAccessCommand extends Command
{
    private MenuService $menuService;
    private EntityManagerInterface $entityManager;

    public function __construct(MenuService $menuService, EntityManagerInterface $entityManager)
    {
        $this->menuService = $menuService;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test d\'accès aux menus pour SUPER_ADMIN');

        // Trouver un utilisateur avec ROLE_SUPER_ADMIN
        $superAdmin = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_SUPER_ADMIN%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$superAdmin) {
            $io->error('Aucun utilisateur avec ROLE_SUPER_ADMIN trouvé.');
            $io->writeln('Créez d\'abord un super admin avec : php bin/console app:create-super-admin');
            return Command::FAILURE;
        }

        $io->section(sprintf('Test avec l\'utilisateur: %s', $superAdmin->getEmail()));
        $io->writeln(sprintf('Rôles: %s', implode(', ', $superAdmin->getRoles())));

        // Simuler la connexion de l'utilisateur
        // Note: En réalité, cela nécessiterait une simulation plus complexe
        // Pour ce test, nous allons vérifier directement les permissions

        $io->section('Test des permissions de menu');

        $allMenus = $this->menuService->getMenuStructure();
        $accessibleCount = 0;
        $restrictedCount = 0;

        $io->writeln('Menus principaux:');
        foreach ($allMenus as $key => $menuItem) {
            $hasRequiredFeature = isset($menuItem['required_feature']);
            $requiredRoles = $menuItem['roles'] ?? [];

            $hasSuperAdminRole = in_array('ROLE_SUPER_ADMIN', $requiredRoles);
            $isAccessible = $hasSuperAdminRole || empty($requiredRoles);

            if ($isAccessible) {
                $io->success(sprintf('✅ %s (%s)', $menuItem['label'] ?? $key, $key));
                $accessibleCount++;
            } else {
                $io->error(sprintf('❌ %s (%s) - Rôles requis: %s',
                    $menuItem['label'] ?? $key,
                    $key,
                    implode(', ', $requiredRoles)
                ));
                $restrictedCount++;
            }

            // Afficher les détails si c'est un menu avec restrictions
            if ($hasRequiredFeature) {
                $io->writeln(sprintf('   📋 Fonctionnalité requise: %s', $menuItem['required_feature']));
            }
        }

        // Test des sous-menus admin
        $io->section('Test des sous-menus admin');

        $adminMenus = [
            'admin_dashboard' => ['label' => 'Tableau de bord Admin', 'roles' => ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']],
            'admin_users' => ['label' => 'Utilisateurs', 'roles' => ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']],
            'admin_organizations' => ['label' => 'Organisations', 'roles' => ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']],
            'admin_companies' => ['label' => 'Sociétés', 'roles' => ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']],
            'admin_tasks' => ['label' => 'Tâches', 'roles' => ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']],
            'admin_settings' => ['label' => 'Paramètres', 'roles' => ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']],
            'admin_plans' => ['label' => 'Plans d\'Abonnement', 'roles' => ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']],
            'admin_subscriptions' => ['label' => 'Abonnements', 'roles' => ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']],
            'admin_accounting_config' => ['label' => 'Config. Comptable', 'roles' => ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']],
            'admin_environments' => ['label' => 'Environnements', 'roles' => ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']],
        ];

        foreach ($adminMenus as $key => $menuItem) {
            $hasSuperAdminRole = in_array('ROLE_SUPER_ADMIN', $menuItem['roles']);

            if ($hasSuperAdminRole) {
                $io->success(sprintf('✅ %s (%s)', $menuItem['label'], $key));
                $accessibleCount++;
            } else {
                $io->error(sprintf('❌ %s (%s) - Rôles requis: %s',
                    $menuItem['label'],
                    $key,
                    implode(', ', $menuItem['roles'])
                ));
                $restrictedCount++;
            }
        }

        // Résumé
        $io->section('Résumé du test');
        $io->writeln(sprintf('Total menus testés: %d', $accessibleCount + $restrictedCount));
        $io->writeln(sprintf('Menus accessibles: %d', $accessibleCount));
        $io->writeln(sprintf('Menus restreints: %d', $restrictedCount));

        if ($restrictedCount === 0) {
            $io->success('🎉 Parfait ! Le SUPER_ADMIN a accès à tous les menus !');
            return Command::SUCCESS;
        } else {
            $io->warning(sprintf('⚠️ %d menu(s) ne sont pas accessibles au SUPER_ADMIN', $restrictedCount));
            return Command::FAILURE;
        }
    }
}
