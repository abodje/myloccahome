<?php

namespace App\Command;

use App\Entity\Document;
use App\Entity\Organization;
use App\Entity\Company;
use App\Entity\Property;
use App\Entity\Tenant;
use App\Entity\Lease;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-document-cascade',
    description: 'Teste la cascade persist dans l\'entité Document.',
)]
class TestDocumentCascadeCommand extends Command
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
        $io->title('Test de la cascade persist dans Document');

        try {
            // 1. Créer des entités de test
            $io->section('1. Création des entités de test');

            $organization = new Organization();
            $organization->setName('Test Organization');
            $organization->setIsDemo(true);

            $company = new Company();
            $company->setName('Test Company');

            $property = new Property();
            $property->setAddress('123 Test Street');
            $property->setCity('Test City');
            $property->setZipCode('12345');

            $tenant = new Tenant();
            $tenant->setFirstName('Test');
            $tenant->setLastName('Tenant');
            $tenant->setEmail('test@tenant.com');

            $lease = new Lease();
            $lease->setStartDate(new \DateTime());
            $lease->setEndDate(new \DateTime('+1 year'));
            $lease->setMonthlyRent(100000);

            $io->writeln('✅ Entités créées en mémoire');

            // 2. Créer un document avec toutes les relations
            $io->section('2. Création du document avec relations');

            $document = new Document();
            $document->setName('Test Document');
            $document->setType('Test Type');
            $document->setFileName('test.pdf');
            $document->setOriginalFileName('test.pdf');
            $document->setOrganization($organization);
            $document->setCompany($company);
            $document->setProperty($property);
            $document->setTenant($tenant);
            $document->setLease($lease);

            $io->writeln('✅ Document créé avec toutes les relations');

            // 3. Tester la persistance avec cascade
            $io->section('3. Test de persistance avec cascade persist');

            $this->entityManager->persist($document);
            $io->writeln('✅ Document persisté (cascade devrait persister les relations)');

            $this->entityManager->flush();
            $io->writeln('✅ Flush réussi - toutes les entités sauvegardées');

            // 4. Vérifier que toutes les entités ont un ID
            $io->section('4. Vérification des IDs générés');

            $io->table(['Entité', 'ID'], [
                ['Organization', $organization->getId() ?? 'NULL'],
                ['Company', $company->getId() ?? 'NULL'],
                ['Property', $property->getId() ?? 'NULL'],
                ['Tenant', $tenant->getId() ?? 'NULL'],
                ['Lease', $lease->getId() ?? 'NULL'],
                ['Document', $document->getId() ?? 'NULL'],
            ]);

            // 5. Nettoyer les données de test
            $io->section('5. Nettoyage des données de test');

            $this->entityManager->remove($document);
            $this->entityManager->flush();
            $io->writeln('✅ Données de test supprimées');

            $io->success('🎉 Test de cascade persist réussi !');
            $io->writeln('La cascade persist fonctionne correctement dans l\'entité Document.');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('❌ Erreur lors du test : ' . $e->getMessage());
            $io->writeln('Trace : ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
