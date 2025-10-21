<?php

// Test direct du CalendarController via Symfony Console
// À exécuter avec: php bin/console app:test-calendar

namespace App\Command;

use App\Controller\CalendarController;
use App\Repository\PaymentRepository;
use App\Repository\LeaseRepository;
use App\Repository\MaintenanceRequestRepository;
use App\Repository\PropertyRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'app:test-calendar',
    description: 'Teste l\'API du calendrier pour diagnostiquer les problèmes.',
)]
class TestCalendarCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private PaymentRepository $paymentRepository;
    private LeaseRepository $leaseRepository;
    private MaintenanceRequestRepository $maintenanceRepository;
    private PropertyRepository $propertyRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        PaymentRepository $paymentRepository,
        LeaseRepository $leaseRepository,
        MaintenanceRequestRepository $maintenanceRepository,
        PropertyRepository $propertyRepository
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->paymentRepository = $paymentRepository;
        $this->leaseRepository = $leaseRepository;
        $this->maintenanceRepository = $maintenanceRepository;
        $this->propertyRepository = $propertyRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('🔍 Diagnostic Calendrier LOKAPRO');

        // Test 1: Vérifier les données dans la base
        $io->section('1. Vérification des données');

        $paymentCount = $this->paymentRepository->count([]);
        $leaseCount = $this->leaseRepository->count([]);
        $maintenanceCount = $this->maintenanceRepository->count([]);
        $propertyCount = $this->propertyRepository->count([]);

        $io->table(
            ['Entité', 'Nombre'],
            [
                ['Paiements', $paymentCount],
                ['Baux', $leaseCount],
                ['Maintenances', $maintenanceCount],
                ['Propriétés', $propertyCount],
            ]
        );

        if ($paymentCount === 0 && $leaseCount === 0 && $maintenanceCount === 0) {
            $io->warning('⚠️ Aucune donnée trouvée dans la base. C\'est probablement la cause du problème.');
            $io->text('Solutions:');
            $io->text('  - Créer des données de test');
            $io->text('  - Importer des données existantes');
            $io->text('  - Vérifier les fixtures');
            return Command::SUCCESS;
        }

        // Test 2: Simuler l'API calendrier
        $io->section('2. Test de l\'API calendrier');

        try {
            // Créer une requête simulée
            $request = new Request();
            $request->query->set('start', '2024-01-01T00:00:00+00:00');
            $request->query->set('end', '2024-12-31T23:59:59+00:00');

            // Créer le contrôleur
            $controller = new CalendarController();

            // Utiliser la réflexion pour accéder à la méthode privée
            $reflection = new \ReflectionClass($controller);
            $method = $reflection->getMethod('events');
            $method->setAccessible(true);

            // Simuler l'utilisateur connecté (premier utilisateur trouvé)
            $user = $this->entityManager->getRepository(\App\Entity\User::class)->findOneBy([]);

            if (!$user) {
                $io->error('❌ Aucun utilisateur trouvé dans la base');
                return Command::FAILURE;
            }

            $io->text("Utilisateur de test: {$user->getEmail()} (Rôles: " . implode(', ', $user->getRoles()) . ")");

            // Simuler l'authentification
            $controller->setContainer($this->getApplication()->getKernel()->getContainer());

            // Appeler la méthode events
            $response = $method->invokeArgs($controller, [
                $request,
                $this->paymentRepository,
                $this->leaseRepository,
                $this->maintenanceRepository,
                $this->propertyRepository
            ]);

            $data = json_decode($response->getContent(), true);
            $eventCount = is_array($data) ? count($data) : 0;

            $io->success("✅ API calendrier fonctionne");
            $io->text("Nombre d'événements: $eventCount");

            if ($eventCount > 0) {
                $io->section('3. Événements trouvés');

                $eventTypes = [];
                foreach ($data as $event) {
                    $type = $event['type'] ?? 'unknown';
                    $eventTypes[$type] = ($eventTypes[$type] ?? 0) + 1;
                }

                $io->table(
                    ['Type', 'Nombre'],
                    array_map(fn($type, $count) => [$type, $count], array_keys($eventTypes), array_values($eventTypes))
                );

                // Afficher quelques exemples
                $io->section('4. Exemples d\'événements');
                $count = 0;
                foreach ($data as $event) {
                    if ($count >= 3) break;
                    $io->text("  " . ($count + 1) . ". " . ($event['title'] ?? 'Sans titre') .
                             " (" . ($event['type'] ?? 'Sans type') . ") - " .
                             ($event['start'] ?? 'Sans date'));
                    $count++;
                }
            } else {
                $io->warning('⚠️ Aucun événement retourné par l\'API');
                $io->text('Causes possibles:');
                $io->text('  - Filtres trop restrictifs');
                $io->text('  - Problème de permissions');
                $io->text('  - Erreur dans les requêtes');
            }

        } catch (\Exception $e) {
            $io->error('❌ Erreur lors du test de l\'API: ' . $e->getMessage());
            $io->text('Trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }

        // Test 3: Vérifier les logs
        $io->section('5. Vérification des logs');
        $logFile = 'var/log/dev.log';
        if (file_exists($logFile)) {
            $logs = file_get_contents($logFile);
            $calendarLogs = array_filter(explode("\n", $logs), function($line) {
                return strpos($line, 'Calendar') !== false;
            });

            if (!empty($calendarLogs)) {
                $io->text('Derniers logs du calendrier:');
                $recentLogs = array_slice($calendarLogs, -5);
                foreach ($recentLogs as $log) {
                    $io->text("  $log");
                }
            } else {
                $io->text('Aucun log spécifique au calendrier trouvé');
            }
        } else {
            $io->text('Fichier de log non trouvé: ' . $logFile);
        }

        $io->success('🎉 Diagnostic terminé');
        return Command::SUCCESS;
    }
}
