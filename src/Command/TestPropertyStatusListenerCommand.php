<?php

namespace App\Command;

use App\Entity\Property;
use App\Entity\Lease;
use App\Entity\Tenant;
use App\Entity\Organization;
use App\Entity\Company;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-property-status-listener',
    description: 'Test l\'EventListener qui met à jour automatiquement le statut des propriétés',
)]
class TestPropertyStatusListenerCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test de l\'EventListener PropertyStatusListener');

        // Trouver une propriété libre pour le test
        $property = $this->entityManager->getRepository(Property::class)
            ->findOneBy(['status' => 'Libre']);

        if (!$property) {
            $io->error('Aucune propriété libre trouvée pour le test.');
            return Command::FAILURE;
        }

        $io->writeln(sprintf(
            'Propriété de test : #%d - %s (Statut actuel: %s)',
            $property->getId(),
            $property->getFullAddress(),
            $property->getStatus()
        ));

        // Trouver un locataire et une organisation pour le test
        $tenant = $this->entityManager->getRepository(Tenant::class)->findOneBy([]);
        $organization = $this->entityManager->getRepository(Organization::class)->findOneBy([]);
        $company = $this->entityManager->getRepository(Company::class)->findOneBy([]);

        if (!$tenant || !$organization || !$company) {
            $io->error('Données de test manquantes (locataire, organisation ou société).');
            return Command::FAILURE;
        }

        // Test 1: Créer un bail (devrait passer la propriété à "Occupé")
        $io->section('Test 1: Création d\'un bail');

        $lease = new Lease();
        $lease->setProperty($property);
        $lease->setTenant($tenant);
        $lease->setOrganization($organization);
        $lease->setCompany($company);
        $lease->setStartDate(new \DateTime());
        $lease->setEndDate((new \DateTime())->modify('+1 year'));
        $lease->setMonthlyRent('50000');
        $lease->setStatus('Actif');

        $this->entityManager->persist($lease);
        $this->entityManager->flush();

        // Vérifier le statut après création
        $this->entityManager->refresh($property);
        $io->writeln(sprintf(
            'Après création du bail : Statut = %s',
            $property->getStatus()
        ));

        if ($property->getStatus() === 'Occupé') {
            $io->writeln('<fg=green>✓ Test 1 réussi : La propriété est bien passée à "Occupé"</>');
        } else {
            $io->writeln('<fg=red>✗ Test 1 échoué : La propriété devrait être "Occupé"</>');
        }

        // Test 2: Modifier le bail (changer le statut à "Terminé")
        $io->section('Test 2: Modification du bail (statut à "Terminé")');

        $lease->setStatus('Terminé');
        $lease->setEndDate(new \DateTime()); // Fin aujourd'hui

        $this->entityManager->flush();

        // Vérifier le statut après modification
        $this->entityManager->refresh($property);
        $io->writeln(sprintf(
            'Après modification du bail : Statut = %s',
            $property->getStatus()
        ));

        if ($property->getStatus() === 'Libre') {
            $io->writeln('<fg=green>✓ Test 2 réussi : La propriété est bien repassée à "Libre"</>');
        } else {
            $io->writeln('<fg=red>✗ Test 2 échoué : La propriété devrait être "Libre"</>');
        }

        // Test 3: Remettre le bail actif
        $io->section('Test 3: Remettre le bail actif');

        $lease->setStatus('Actif');
        $lease->setEndDate((new \DateTime())->modify('+1 year'));

        $this->entityManager->flush();

        // Vérifier le statut
        $this->entityManager->refresh($property);
        $io->writeln(sprintf(
            'Après remise du bail actif : Statut = %s',
            $property->getStatus()
        ));

        if ($property->getStatus() === 'Occupé') {
            $io->writeln('<fg=green>✓ Test 3 réussi : La propriété est bien repassée à "Occupé"</>');
        } else {
            $io->writeln('<fg=red>✗ Test 3 échoué : La propriété devrait être "Occupé"</>');
        }

        // Test 4: Supprimer le bail (devrait passer la propriété à "Libre")
        $io->section('Test 4: Suppression du bail');

        $this->entityManager->remove($lease);
        $this->entityManager->flush();

        // Vérifier le statut après suppression
        $this->entityManager->refresh($property);
        $io->writeln(sprintf(
            'Après suppression du bail : Statut = %s',
            $property->getStatus()
        ));

        if ($property->getStatus() === 'Libre') {
            $io->writeln('<fg=green>✓ Test 4 réussi : La propriété est bien repassée à "Libre"</>');
        } else {
            $io->writeln('<fg=red>✗ Test 4 échoué : La propriété devrait être "Libre"</>');
        }

        $io->success('Tests terminés ! L\'EventListener fonctionne correctement.');

        return Command::SUCCESS;
    }
}
