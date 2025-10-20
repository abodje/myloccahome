<?php

namespace App\Command;

use App\Entity\Property;
use App\Entity\MaintenanceRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-property-maintenance-requests',
    description: 'Teste la relation maintenanceRequests dans l\'entité Property.',
)]
class TestPropertyMaintenanceRequestsCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Test de la relation maintenanceRequests dans Property');

        try {
            // 1. Tester la requête d'export qui causait l'erreur
            $io->section('1. Test de la requête d\'export des biens');

            $properties = $this->entityManager->getRepository(Property::class)
                ->createQueryBuilder('p')
                ->leftJoin('p.leases', 'l')
                ->leftJoin('p.maintenanceRequests', 'mr')
                ->addSelect('l', 'mr')
                ->orderBy('p.address', 'ASC')
                ->getQuery()
                ->getResult();

            $io->success(sprintf('✅ Requête d\'export réussie ! %d propriétés récupérées', count($properties)));

            // 2. Tester les relations sur une propriété
            if (!empty($properties)) {
                $io->section('2. Test des relations sur une propriété');

                $property = $properties[0];
                $io->writeln(sprintf('Propriété testée: %s', $property->getAddress()));

                $leases = $property->getLeases();
                $maintenanceRequests = $property->getMaintenanceRequests();

                $io->writeln(sprintf('  - Nombre de baux: %d', $leases->count()));
                $io->writeln(sprintf('  - Nombre de demandes de maintenance: %d', $maintenanceRequests->count()));

                $io->success('✅ Relations maintenanceRequests fonctionnelles !');
            }

            // 3. Tester l'ajout d'une demande de maintenance
            $io->section('3. Test d\'ajout d\'une demande de maintenance');

            if (!empty($properties)) {
                $property = $properties[0];
                $maintenanceRequest = new MaintenanceRequest();
                $maintenanceRequest->setTitle('Test de maintenance');
                $maintenanceRequest->setDescription('Test de maintenance');
                $maintenanceRequest->setCategory('Test');
                $maintenanceRequest->setPriority('Normale');
                $maintenanceRequest->setStatus('Nouvelle');
                $maintenanceRequest->setCreatedAt(new \DateTime());

                $property->addMaintenanceRequest($maintenanceRequest);

                $this->entityManager->persist($maintenanceRequest);
                $this->entityManager->flush();

                $io->success('✅ Demande de maintenance ajoutée avec succès !');

                // Nettoyer
                $property->removeMaintenanceRequest($maintenanceRequest);
                $this->entityManager->remove($maintenanceRequest);
                $this->entityManager->flush();

                $io->writeln('🧹 Données de test nettoyées');
            }

            $io->success('🎉 Tous les tests de relation maintenanceRequests ont réussi !');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('❌ Erreur lors du test : ' . $e->getMessage());
            $io->writeln('Trace : ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
