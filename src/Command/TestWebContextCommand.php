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
use Symfony\Component\HttpFoundation\RequestStack;

#[AsCommand(
    name: 'app:test-web-context',
    description: 'Teste le contexte web pour le téléchargement',
)]
class TestWebContextCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SecureFileService $secureFileService,
        private RequestStack $requestStack
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('document-id', 'd', InputOption::VALUE_REQUIRED, 'ID du document à tester', 3);
        $this->addOption('user-id', 'u', InputOption::VALUE_REQUIRED, 'ID de l\'utilisateur', 1);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test du contexte web pour le téléchargement');

        $documentId = $input->getOption('document-id');
        $userId = $input->getOption('user-id');

        try {
            // 1. Simuler un contexte web
            $io->section('1. Simulation du contexte web');

            // Créer une requête simulée
            $request = Request::create('/secure-documents/' . $documentId . '/download', 'GET');
            $request->server->set('HTTP_HOST', 'localhost');
            $request->server->set('REQUEST_URI', '/secure-documents/' . $documentId . '/download');

            // Définir la requête dans le RequestStack
            $this->requestStack->push($request);

            $io->writeln("✅ Contexte web simulé");
            $io->writeln("   URL: " . $request->getUri());
            $io->writeln("   Méthode: " . $request->getMethod());

            // 2. Récupérer le document
            $io->section('2. Récupération du document');
            $document = $this->entityManager->getRepository(Document::class)->find($documentId);
            if (!$document) {
                $io->error("Document avec l'ID $documentId non trouvé");
                return Command::FAILURE;
            }

            $io->writeln("✅ Document trouvé: " . $document->getName());
            $io->writeln("   Fichier: " . $document->getFileName());
            $io->writeln("   Organisation: " . ($document->getOrganization() ? $document->getOrganization()->getName() : 'Aucune'));

            // 3. Récupérer l'utilisateur
            $io->section('3. Récupération de l\'utilisateur');
            $user = $this->entityManager->getRepository(User::class)->find($userId);
            if (!$user) {
                $io->error("Utilisateur avec l'ID $userId non trouvé");
                return Command::FAILURE;
            }

            $io->writeln("✅ Utilisateur trouvé: " . $user->getEmail());
            $io->writeln("   Rôles: " . implode(', ', $user->getRoles()));
            $io->writeln("   Organisation: " . ($user->getOrganization() ? $user->getOrganization()->getName() : 'Aucune'));

            // 4. Test de déchiffrement dans le contexte web
            $io->section('4. Test de déchiffrement dans le contexte web');
            try {
                $decryptedContent = $this->secureFileService->testDecryptFile($document);
                $io->writeln("✅ Déchiffrement réussi");
                $io->writeln("   Taille: " . strlen($decryptedContent) . " bytes");
                $io->writeln("   Début: " . substr($decryptedContent, 0, 50) . "...");
            } catch (\Exception $e) {
                $io->error("❌ Erreur de déchiffrement: " . $e->getMessage());
                $io->writeln("   Trace: " . $e->getTraceAsString());
                return Command::FAILURE;
            }

            // 5. Test de téléchargement sécurisé dans le contexte web
            $io->section('5. Test de téléchargement sécurisé dans le contexte web');
            try {
                $response = $this->secureFileService->downloadSecureFile($document, $user);
                $io->writeln("✅ Téléchargement sécurisé réussi");
                $io->writeln("   Type: " . $response->headers->get('Content-Type'));
                $io->writeln("   Taille: " . strlen($response->getContent()) . " bytes");
                $io->writeln("   Headers: " . json_encode($response->headers->all()));
            } catch (\Exception $e) {
                $io->error("❌ Erreur de téléchargement sécurisé: " . $e->getMessage());
                $io->writeln("   Trace: " . $e->getTraceAsString());
                return Command::FAILURE;
            }

            // 6. Vérification des variables d'environnement dans le contexte web
            $io->section('6. Vérification des variables d\'environnement');
            $envKey = $_ENV['APP_ENCRYPTION_KEY'] ?? null;
            $serverKey = $_SERVER['APP_ENCRYPTION_KEY'] ?? null;

            if ($envKey) {
                $io->writeln("✅ APP_ENCRYPTION_KEY disponible dans \$_ENV");
            } else {
                $io->error("❌ APP_ENCRYPTION_KEY manquante dans \$_ENV");
            }

            if ($serverKey) {
                $io->writeln("✅ APP_ENCRYPTION_KEY disponible dans \$_SERVER");
            } else {
                $io->error("❌ APP_ENCRYPTION_KEY manquante dans \$_SERVER");
            }

            $io->success('Tous les tests du contexte web sont passés avec succès !');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors du test: ' . $e->getMessage());
            $io->writeln('Trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        } finally {
            // Nettoyer le RequestStack
            $this->requestStack->pop();
        }
    }
}
