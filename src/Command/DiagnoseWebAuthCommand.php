<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

#[AsCommand(
    name: 'app:diagnose-web-auth',
    description: 'Diagnostique les problèmes d\'authentification dans le contexte web',
)]
class DiagnoseWebAuthCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TokenStorageInterface $tokenStorage,
        private RequestStack $requestStack
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('user-id', 'u', InputOption::VALUE_REQUIRED, 'ID de l\'utilisateur à tester', 1);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Diagnostic des problèmes d\'authentification web');

        $userId = $input->getOption('user-id');

        try {
            // 1. Récupérer l'utilisateur
            $io->section('1. Récupération de l\'utilisateur');
            $user = $this->entityManager->getRepository(User::class)->find($userId);
            if (!$user) {
                $io->error("Utilisateur avec l'ID $userId non trouvé");
                return Command::FAILURE;
            }

            $io->writeln("✅ Utilisateur trouvé: " . $user->getEmail());
            $io->writeln("   ID: " . $user->getId());
            $io->writeln("   Rôles: " . implode(', ', $user->getRoles()));
            $io->writeln("   Actif: " . ($user->isActive() ? 'Oui' : 'Non'));

            // 2. Simuler l'authentification
            $io->section('2. Simulation de l\'authentification');

            // Créer un token d'authentification
            $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
            $this->tokenStorage->setToken($token);

            $io->writeln("✅ Token d'authentification créé");

            // 3. Créer une requête simulée avec session
            $io->section('3. Simulation de la requête avec session');

            $request = Request::create('/secure-documents/3/download', 'GET');
            $request->server->set('HTTP_HOST', 'localhost');
            $request->server->set('REQUEST_URI', '/secure-documents/3/download');
            $request->server->set('REMOTE_ADDR', '127.0.0.1');
            $request->headers->set('User-Agent', 'Test-Simulation/1.0');

            // Simuler une session
            $request->setSession(new \Symfony\Component\HttpFoundation\Session\Session());

            $this->requestStack->push($request);
            $io->writeln("✅ Requête avec session simulée");

            // 4. Tester l'authentification dans le contexte
            $io->section('4. Test de l\'authentification dans le contexte');

            // Vérifier le token actuel
            $currentToken = $this->tokenStorage->getToken();
            if ($currentToken) {
                $io->writeln("✅ Token trouvé dans le TokenStorage");
                $io->writeln("   Utilisateur: " . $currentToken->getUserIdentifier());
                $io->writeln("   Rôles: " . implode(', ', $currentToken->getRoleNames()));
            } else {
                $io->error("❌ Aucun token dans le TokenStorage");
            }

            // 5. Tester le contrôleur avec authentification
            $io->section('5. Test du contrôleur avec authentification');

            try {
                // Récupérer le contrôleur depuis le container
                $container = $this->getApplication()->getKernel()->getContainer();
                $controller = $container->get('App\\Controller\\SecureDocumentController');

                // Récupérer le document
                $document = $this->entityManager->getRepository(\App\Entity\Document::class)->find(3);

                if ($document) {
                    $io->writeln("✅ Document trouvé: " . $document->getName());

                    // Appeler la méthode download
                    $response = $controller->download($document, $request);

                    if ($response->isSuccessful()) {
                        $io->writeln("✅ Téléchargement réussi !");
                        $io->writeln("   Status: " . $response->getStatusCode());
                        $io->writeln("   Content-Type: " . $response->headers->get('Content-Type'));
                        $io->writeln("   Content-Length: " . strlen($response->getContent()));
                    } else {
                        $io->error("❌ Téléchargement échoué");
                        $io->writeln("   Status: " . $response->getStatusCode());
                        $io->writeln("   Content: " . $response->getContent());
                    }
                } else {
                    $io->error("❌ Document non trouvé");
                }

            } catch (\Exception $e) {
                $io->error("❌ Erreur lors du test du contrôleur: " . $e->getMessage());
                $io->writeln("   Type d'erreur: " . get_class($e));
                $io->writeln("   Trace:");
                $io->writeln($e->getTraceAsString());
            }

            // 6. Vérifier la configuration de sécurité
            $io->section('6. Vérification de la configuration de sécurité');

            $securityConfig = 'config/packages/security.yaml';
            if (file_exists($securityConfig)) {
                $io->writeln("✅ Fichier de configuration de sécurité trouvé");
                $content = file_get_contents($securityConfig);

                if (strpos($content, 'ROLE_USER') !== false) {
                    $io->writeln("✅ ROLE_USER trouvé dans la configuration");
                } else {
                    $io->warning("⚠️  ROLE_USER non trouvé dans la configuration");
                }

                if (strpos($content, 'form_login') !== false) {
                    $io->writeln("✅ Authentification par formulaire configurée");
                } else {
                    $io->warning("⚠️  Authentification par formulaire non configurée");
                }
            } else {
                $io->error("❌ Fichier de configuration de sécurité non trouvé");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors du diagnostic: ' . $e->getMessage());
            $io->writeln('Trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        } finally {
            // Nettoyer
            $this->requestStack->pop();
            $this->tokenStorage->setToken(null);
        }
    }
}
