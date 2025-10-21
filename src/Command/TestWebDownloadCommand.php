<?php

namespace App\Command;

use App\Entity\Document;
use App\Entity\User;
use App\Service\SecureFileService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsCommand(
    name: 'app:test-web-download',
    description: 'Simule le téléchargement via l\'interface web',
)]
class TestWebDownloadCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SecureFileService $secureFileService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('document-id', 'd', InputOption::VALUE_REQUIRED, 'ID du document à tester');
        $this->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'ID de l\'utilisateur pour le test');
        $this->addOption('route', 'r', InputOption::VALUE_OPTIONAL, 'Route à tester (old|new)', 'old');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $documentId = $input->getOption('document-id');
        $userId = $input->getOption('user-id');
        $route = $input->getOption('route');

        if (!$documentId) {
            $io->error('Veuillez spécifier l\'ID du document avec --document-id');
            return Command::FAILURE;
        }

        $io->title('Test de téléchargement via interface web');

        try {
            // Récupérer le document
            $document = $this->entityManager->getRepository(Document::class)->find($documentId);
            if (!$document) {
                $io->error("Document avec l'ID $documentId non trouvé");
                return Command::FAILURE;
            }

            $io->writeln(sprintf("📄 Document: %s (ID: %d)", $document->getName(), $document->getId()));

            // Récupérer l'utilisateur
            $user = null;
            if ($userId) {
                $user = $this->entityManager->getRepository(User::class)->find($userId);
                if (!$user) {
                    $io->error("Utilisateur avec l'ID $userId non trouvé");
                    return Command::FAILURE;
                }
                $io->writeln(sprintf("👤 Utilisateur: %s (ID: %d)", $user->getEmail(), $user->getId()));
                $io->writeln(sprintf("   Rôles: %s", implode(', ', $user->getRoles())));
            }

            // Simuler la requête HTTP
            $io->section('Simulation de la requête HTTP');

            // Test de l'ancienne route (DocumentController)
            if ($route === 'old') {
                $io->writeln("🔄 Test de l'ancienne route: /mes-documents/{id}/telecharger");

                try {
                    // Simuler exactement ce que fait DocumentController::download()
                    $response = $this->secureFileService->downloadSecureFile($document, $user);

                    $io->writeln("✅ Téléchargement réussi via DocumentController");
                    $io->writeln(sprintf("   Type: %s", $response->headers->get('Content-Type')));
                    $io->writeln(sprintf("   Taille: %d bytes", strlen($response->getContent())));
                    $io->writeln(sprintf("   Disposition: %s", $response->headers->get('Content-Disposition')));

                } catch (\Exception $e) {
                    $io->error("❌ Erreur via DocumentController: " . $e->getMessage());
                    $io->writeln("   Trace: " . $e->getTraceAsString());
                    return Command::FAILURE;
                }
            }

            // Test de la nouvelle route (SecureDocumentController)
            if ($route === 'new') {
                $io->writeln("🔄 Test de la nouvelle route: /secure-documents/{id}/download");

                try {
                    // Simuler SecureDocumentController::download()
                    if (!$user) {
                        throw new \Exception('Utilisateur non authentifié');
                    }

                    // Vérification des permissions (comme dans SecureDocumentController)
                    if (!$this->hasAccessToDocument($document, $user)) {
                        throw new \Exception('Accès non autorisé à ce document');
                    }

                    $response = $this->secureFileService->downloadSecureFile($document, $user);

                    $io->writeln("✅ Téléchargement réussi via SecureDocumentController");
                    $io->writeln(sprintf("   Type: %s", $response->headers->get('Content-Type')));
                    $io->writeln(sprintf("   Taille: %d bytes", strlen($response->getContent())));

                } catch (\Exception $e) {
                    $io->error("❌ Erreur via SecureDocumentController: " . $e->getMessage());
                    $io->writeln("   Trace: " . $e->getTraceAsString());
                    return Command::FAILURE;
                }
            }

            // Test des deux routes
            if ($route === 'both') {
                $io->section('Test des deux routes');

                // Ancienne route
                try {
                    $response1 = $this->secureFileService->downloadSecureFile($document, $user);
                    $io->writeln("✅ Ancienne route: OK");
                } catch (\Exception $e) {
                    $io->writeln("❌ Ancienne route: " . $e->getMessage());
                }

                // Nouvelle route
                try {
                    if ($user && !$this->hasAccessToDocument($document, $user)) {
                        throw new \Exception('Accès non autorisé');
                    }
                    $response2 = $this->secureFileService->downloadSecureFile($document, $user);
                    $io->writeln("✅ Nouvelle route: OK");
                } catch (\Exception $e) {
                    $io->writeln("❌ Nouvelle route: " . $e->getMessage());
                }
            }

            $io->success('Test terminé avec succès !');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors du test: ' . $e->getMessage());
            $io->writeln('Trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    /**
     * Vérification des permissions d'accès à un document (copie de SecureDocumentController)
     */
    private function hasAccessToDocument(Document $document, $user): bool
    {
        if (!$user) {
            return false;
        }

        $userRoles = $user->getRoles();

        // Super admin a accès à tout
        if (in_array('ROLE_SUPER_ADMIN', $userRoles)) {
            return true;
        }

        // Admin peut voir les documents de son organisation
        if (in_array('ROLE_ADMIN', $userRoles)) {
            return $document->getOrganization() &&
                   $document->getOrganization() === $user->getOrganization();
        }

        // Manager peut voir les documents de sa société
        if (in_array('ROLE_MANAGER', $userRoles)) {
            return $document->getCompany() &&
                   $document->getCompany() === $user->getCompany();
        }

        // Tenant peut voir ses propres documents
        if (in_array('ROLE_TENANT', $userRoles)) {
            return $document->getTenant() &&
                   $document->getTenant() === $user->getTenant();
        }

        // Owner peut voir les documents de ses propriétés
        if (in_array('ROLE_OWNER', $userRoles)) {
            return $document->getOwner() &&
                   $document->getOwner() === $user->getOwner();
        }

        return false;
    }
}
