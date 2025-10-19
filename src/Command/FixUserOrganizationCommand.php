<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Tenant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-user-organization',
    description: 'Corrige les utilisateurs qui n\'ont pas d\'organization_id ou company_id définis',
)]
class FixUserOrganizationCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Correction des utilisateurs sans organisation/société');

        // Récupérer tous les utilisateurs sans organisation
        $usersWithoutOrg = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.organization IS NULL')
            ->getQuery()
            ->getResult();

        $io->info(sprintf('Trouvé %d utilisateurs sans organisation', count($usersWithoutOrg)));

        $fixed = 0;
        $skipped = 0;

        foreach ($usersWithoutOrg as $user) {
            $io->text(sprintf('Traitement de l\'utilisateur: %s (%s)', $user->getEmail(), implode(', ', $user->getRoles())));

            // Essayer de récupérer l'organisation via le tenant
            if (in_array('ROLE_TENANT', $user->getRoles())) {
                $tenant = $user->getTenant();
                if ($tenant && $tenant->getOrganization()) {
                    $user->setOrganization($tenant->getOrganization());
                    if ($tenant->getCompany()) {
                        $user->setCompany($tenant->getCompany());
                    }
                    $fixed++;
                    $io->text(sprintf('  ✓ Organisation définie via tenant: %s', $tenant->getOrganization()->getName()));
                } else {
                    $skipped++;
                    $io->text('  ✗ Aucun tenant trouvé ou tenant sans organisation');
                }
            } else {
                $skipped++;
                $io->text('  ✗ Utilisateur non-locataire, impossible de déterminer l\'organisation automatiquement');
            }
        }

        // Sauvegarder les modifications
        if ($fixed > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('%d utilisateurs corrigés, %d ignorés', $fixed, $skipped));
        } else {
            $io->info('Aucun utilisateur à corriger');
        }

        // Statistiques finales
        $totalUsers = $this->entityManager->getRepository(User::class)->count([]);
        $usersWithOrg = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.organization IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();

        $io->section('Statistiques finales');
        $io->table(
            ['Métrique', 'Valeur'],
            [
                ['Total utilisateurs', $totalUsers],
                ['Utilisateurs avec organisation', $usersWithOrg],
                ['Utilisateurs sans organisation', $totalUsers - $usersWithOrg],
            ]
        );

        return Command::SUCCESS;
    }
}
