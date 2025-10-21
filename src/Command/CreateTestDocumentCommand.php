<?php

namespace App\Command;

use App\Entity\Document;
use App\Entity\Organization;
use App\Entity\User;
use App\Service\SecureFileService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[AsCommand(
    name: 'app:create-test-document',
    description: 'Crée un document de test pour le téléchargement',
)]
class CreateTestDocumentCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SecureFileService $secureFileService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Création d\'un document de test');

        try {
            // 1. Créer un fichier de test
            $testContent = "Document de test pour téléchargement\n";
            $testContent .= "Date: " . date('Y-m-d H:i:s') . "\n";
            $testContent .= "Ce fichier peut être téléchargé via l'interface web.\n";
            $testContent .= "Si vous voyez ce contenu, le téléchargement fonctionne !\n";

            $tempFile = tempnam(sys_get_temp_dir(), 'test_download');
            file_put_contents($tempFile, $testContent);

            $io->writeln("✅ Fichier de test créé: $tempFile");

            // 2. Créer un UploadedFile simulé
            $uploadedFile = new UploadedFile(
                $tempFile,
                'test_download.txt',
                'text/plain',
                null,
                true
            );

            // 3. Récupérer ou créer une organisation
            $organization = $this->entityManager->getRepository(Organization::class)->findOneBy([]);
            if (!$organization) {
                $organization = new Organization();
                $organization->setName('Organisation de test');
                $organization->setSlug('test-org-' . time());
                $organization->setCreatedAt(new \DateTime());
                $this->entityManager->persist($organization);
                $this->entityManager->flush();
            }

            // 4. Créer le document
            $document = new Document();
            $document->setName('Document de test - Téléchargement');
            $document->setType('Test');
            $document->setDescription('Document créé pour tester le téléchargement via l\'interface web');
            $document->setMimeType('text/plain');
            $document->setOriginalFileName('test_download.txt');
            $document->setFileSize($uploadedFile->getSize());
            $document->setCreatedAt(new \DateTime());
            $document->setOrganization($organization);

            // 5. Uploader le fichier
            $secureFileName = $this->secureFileService->uploadSecureFile($uploadedFile, $document);
            $document->setFileName($secureFileName);

            $this->entityManager->persist($document);
            $this->entityManager->flush();

            $io->writeln("✅ Document créé avec l'ID: " . $document->getId());
            $io->writeln("✅ Fichier sécurisé: $secureFileName");

            // 6. Test de téléchargement avec un utilisateur super admin
            $io->section('Test de téléchargement');
            $superAdmin = $this->entityManager->getRepository(User::class)->findOneBy(['roles' => ['ROLE_SUPER_ADMIN']]);
            if (!$superAdmin) {
                $io->warning('Aucun super admin trouvé pour le test');
            } else {
                try {
                    $response = $this->secureFileService->downloadSecureFile($document, $superAdmin);
                    $io->writeln("✅ Téléchargement test réussi");
                    $io->writeln("   Type: " . $response->headers->get('Content-Type'));
                    $io->writeln("   Taille: " . strlen($response->getContent()) . " bytes");
                } catch (\Exception $e) {
                    $io->error("❌ Erreur de téléchargement: " . $e->getMessage());
                    return Command::FAILURE;
                }
            }

            // 7. Instructions
            $io->section('Instructions');
            $io->writeln([
                'Document de test créé avec succès !',
                '',
                'Pour tester le téléchargement via l\'interface web:',
                '1. Connectez-vous à l\'interface web',
                '2. Allez dans "Mes documents"',
                '3. Cherchez "Document de test - Téléchargement"',
                '4. Cliquez sur le bouton de téléchargement',
                '5. Si ça fonctionne, vous devriez télécharger un fichier .txt',
                '',
                'Si vous avez encore une erreur:',
                '1. Surveillez les logs: php bin/console app:monitor-download-errors --follow',
                '2. Notez l\'erreur exacte',
                '3. Vérifiez que vous êtes bien connecté'
            ]);

            // Nettoyage
            unlink($tempFile);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors de la création: ' . $e->getMessage());
            $io->writeln('Trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
