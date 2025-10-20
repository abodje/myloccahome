<?php

namespace App\Command;

use App\Entity\Document;
use App\Entity\Company;
use App\Entity\Lease;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-document-cascade',
    description: 'Diagnostique et corrige les problèmes de cascade persist dans les documents.',
)]
class FixDocumentCascadeCommand extends Command
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
        $io->title('Diagnostic et correction des problèmes de cascade persist');

        // 1. Diagnostiquer les documents avec des relations non persistées
        $io->section('1. Diagnostic des documents problématiques');

        $documents = $this->entityManager->getRepository(Document::class)->findAll();
        $problematicDocuments = [];

        foreach ($documents as $document) {
            $issues = [];

            // Vérifier la company
            if ($document->getCompany() && !$this->entityManager->contains($document->getCompany())) {
                $issues[] = 'Company non persistée: ' . $document->getCompany()->getName();
            }

            // Vérifier le lease
            if ($document->getLease() && !$this->entityManager->contains($document->getLease())) {
                $issues[] = 'Lease non persistée: ' . (string)$document->getLease();
            }

            if (!empty($issues)) {
                $problematicDocuments[] = [
                    'id' => $document->getId(),
                    'name' => $document->getName(),
                    'issues' => $issues
                ];
            }
        }

        if (empty($problematicDocuments)) {
            $io->success('✅ Aucun document problématique trouvé !');
        } else {
            $io->warning(sprintf('⚠️ %d documents problématiques trouvés:', count($problematicDocuments)));

            foreach ($problematicDocuments as $doc) {
                $io->writeln(sprintf('  - Document #%d: %s', $doc['id'], $doc['name']));
                foreach ($doc['issues'] as $issue) {
                    $io->writeln(sprintf('    * %s', $issue));
                }
            }
        }

        // 2. Proposer des solutions
        $io->section('2. Solutions proposées');

        $io->writeln('Pour résoudre ce problème, nous devons :');
        $io->writeln('1. Persister les entités Company et Lease AVANT de les associer aux documents');
        $io->writeln('2. Ou configurer la cascade persist dans l\'entité Document');

        if ($io->confirm('Voulez-vous appliquer la solution de cascade persist ?')) {
            return $this->applyCascadePersistSolution($io);
        }

        if ($io->confirm('Voulez-vous corriger l\'ordre de persistance dans le code ?')) {
            return $this->fixPersistenceOrder($io);
        }

        $io->info('Aucune correction appliquée. Vous pouvez corriger manuellement le code.');
        return Command::SUCCESS;
    }

    private function applyCascadePersistSolution(SymfonyStyle $io): int
    {
        $io->section('Application de la cascade persist');

        // Modifier l'entité Document pour ajouter cascade persist
        $documentEntityPath = 'src/Entity/Document.php';

        if (!file_exists($documentEntityPath)) {
            $io->error('Fichier Document.php non trouvé');
            return Command::FAILURE;
        }

        $content = file_get_contents($documentEntityPath);

        // Ajouter cascade persist aux relations Company et Lease
        $content = str_replace(
            "#[ORM\ManyToOne(targetEntity: Company::class)]\n    #[ORM\JoinColumn(nullable: true)]",
            "#[ORM\ManyToOne(targetEntity: Company::class, cascade: ['persist'])]\n    #[ORM\JoinColumn(nullable: true)]",
            $content
        );

        $content = str_replace(
            "#[ORM\ManyToOne]\n    private ?Lease \$lease = null;",
            "#[ORM\ManyToOne(cascade: ['persist'])]\n    private ?Lease \$lease = null;",
            $content
        );

        if (file_put_contents($documentEntityPath, $content)) {
            $io->success('✅ Cascade persist ajoutée à l\'entité Document');
            $io->writeln('Les relations Company et Lease ont maintenant la cascade persist configurée.');
        } else {
            $io->error('❌ Erreur lors de la modification du fichier');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function fixPersistenceOrder(SymfonyStyle $io): int
    {
        $io->section('Correction de l\'ordre de persistance');

        // Modifier le DemoEnvironmentService pour persister dans le bon ordre
        $demoServicePath = 'src/Service/DemoEnvironmentService.php';

        if (!file_exists($demoServicePath)) {
            $io->error('Fichier DemoEnvironmentService.php non trouvé');
            return Command::FAILURE;
        }

        $content = file_get_contents($demoServicePath);

        // Trouver la méthode createDemoDocuments et corriger l'ordre
        $pattern = '/private function createDemoDocuments\(array \$payments, Organization \$organization, Company \$company\): array\s*\{([^}]+)\}/s';

        if (preg_match($pattern, $content, $matches)) {
            $io->writeln('Méthode createDemoDocuments trouvée. Correction nécessaire dans le code.');
            $io->writeln('Il faut s\'assurer que les entités Company et Lease sont persistées avant d\'être utilisées.');
        }

        $io->info('Pour corriger manuellement :');
        $io->writeln('1. Dans createDemoDocuments, vérifiez que $company et $lease sont déjà persistés');
        $io->writeln('2. Ou persister ces entités avant de les associer aux documents');

        return Command::SUCCESS;
    }
}
