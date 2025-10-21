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
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Response;

#[AsCommand(
    name: 'app:test-web-download-simulation',
    description: 'Simule exactement le téléchargement via l\'interface web',
)]
class TestWebDownloadSimulationCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SecureFileService $secureFileService,
        private RequestStack $requestStack,
        private RouterInterface $router,
        private HttpKernelInterface $httpKernel
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

        $io->title('Simulation exacte du téléchargement web');

        $documentId = $input->getOption('document-id');
        $userId = $input->getOption('user-id');

        try {
            // 1. Récupérer le document et l'utilisateur
            $io->section('1. Récupération des données');
            $document = $this->entityManager->getRepository(Document::class)->find($documentId);
            if (!$document) {
                $io->error("Document avec l'ID $documentId non trouvé");
                return Command::FAILURE;
            }

            $user = $this->entityManager->getRepository(User::class)->find($userId);
            if (!$user) {
                $io->error("Utilisateur avec l'ID $userId non trouvé");
                return Command::FAILURE;
            }

            $io->writeln("✅ Document: " . $document->getName());
            $io->writeln("✅ Utilisateur: " . $user->getEmail());

            // 2. Créer une requête HTTP simulée
            $io->section('2. Simulation de la requête HTTP');
            $url = $this->router->generate('app_secure_document_download', ['id' => $documentId]);
            $io->writeln("URL: $url");

            $request = Request::create($url, 'GET');
            $request->server->set('HTTP_HOST', 'localhost');
            $request->server->set('REQUEST_URI', $url);
            $request->server->set('REMOTE_ADDR', '127.0.0.1');
            $request->headers->set('User-Agent', 'Test-Simulation/1.0');

            // Définir l'utilisateur dans la requête (simulation de l'authentification)
            $request->attributes->set('_user', $user);

            $this->requestStack->push($request);
            $io->writeln("✅ Requête HTTP simulée");

            // 3. Simuler l'appel au contrôleur
            $io->section('3. Simulation de l\'appel au contrôleur');
            try {
                $response = $this->httpKernel->handle($request, HttpKernelInterface::SUB_REQUEST);

                if ($response->isSuccessful()) {
                    $io->writeln("✅ Réponse HTTP réussie");
                    $io->writeln("   Status: " . $response->getStatusCode());
                    $io->writeln("   Content-Type: " . $response->headers->get('Content-Type'));
                    $io->writeln("   Content-Length: " . strlen($response->getContent()));
                    $io->writeln("   Content-Disposition: " . $response->headers->get('Content-Disposition'));
                } else {
                    $io->error("❌ Réponse HTTP échouée");
                    $io->writeln("   Status: " . $response->getStatusCode());
                    $io->writeln("   Content: " . $response->getContent());
                }
            } catch (\Exception $e) {
                $io->error("❌ Erreur lors de la simulation: " . $e->getMessage());
                $io->writeln("   Trace: " . $e->getTraceAsString());
                return Command::FAILURE;
            }

            // 4. Test direct du SecureFileService
            $io->section('4. Test direct du SecureFileService');
            try {
                $response = $this->secureFileService->downloadSecureFile($document, $user);
                $io->writeln("✅ SecureFileService fonctionne");
                $io->writeln("   Type: " . $response->headers->get('Content-Type'));
                $io->writeln("   Taille: " . strlen($response->getContent()) . " bytes");
            } catch (\Exception $e) {
                $io->error("❌ Erreur SecureFileService: " . $e->getMessage());
                $io->writeln("   Trace: " . $e->getTraceAsString());
                return Command::FAILURE;
            }

            // 5. Vérification des logs
            $io->section('5. Vérification des logs récents');
            $logFile = 'var/log/dev.log';
            if (file_exists($logFile)) {
                $lines = file($logFile);
                $recentLines = array_slice($lines, -20);

                $io->writeln("Dernières lignes de log:");
                foreach ($recentLines as $line) {
                    if (strpos($line, 'download') !== false ||
                        strpos($line, 'déchiffrement') !== false ||
                        strpos($line, 'error') !== false) {
                        $io->writeln(trim($line));
                    }
                }
            }

            $io->success('Simulation terminée avec succès !');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors de la simulation: ' . $e->getMessage());
            $io->writeln('Trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        } finally {
            // Nettoyer le RequestStack
            $this->requestStack->pop();
        }
    }
}
