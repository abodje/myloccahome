<?php

// Test détaillé du CalendarController
// À exécuter avec: php bin/console app:debug-calendar

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
    name: 'app:debug-calendar',
    description: 'Debug détaillé du calendrier pour identifier les problèmes.',
)]
class DebugCalendarCommand extends Command
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
        $io->title('🐛 Debug Détaillé Calendrier');

        // Récupérer un utilisateur tenant
        $user = $this->entityManager->getRepository(\App\Entity\User::class)
            ->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_TENANT%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$user) {
            $io->error('❌ Aucun utilisateur tenant trouvé');
            return Command::FAILURE;
        }

        $io->text("Utilisateur: {$user->getEmail()}");
        $io->text("Rôles: " . implode(', ', $user->getRoles()));

        // Vérifier le tenant associé
        $tenant = $user->getTenant();
        if (!$tenant) {
            $io->error('❌ Aucun tenant associé à cet utilisateur');
            return Command::FAILURE;
        }

        $io->text("Tenant: {$tenant->getFullName()}");

        // Test des paiements
        $io->section('Test des paiements');
        $payments = $this->paymentRepository->findByTenantWithFilters($tenant->getId());
        $io->text("Paiements trouvés: " . count($payments));

        if (!empty($payments)) {
            $io->text("Premier paiement:");
            $firstPayment = $payments[0];
            $io->text("  - ID: " . $firstPayment->getId());
            $io->text("  - Montant: " . $firstPayment->getAmount());
            $io->text("  - Date: " . ($firstPayment->getPaidDate() ? $firstPayment->getPaidDate()->format('Y-m-d') : 'N/A'));
            $io->text("  - Propriété: " . ($firstPayment->getProperty() ? $firstPayment->getProperty()->getFullAddress() : 'N/A'));
        }

        // Test des baux
        $io->section('Test des baux');
        $leases = $this->leaseRepository->findBy(['tenant' => $tenant]);
        $io->text("Baux trouvés: " . count($leases));

        if (!empty($leases)) {
            $io->text("Premier bail:");
            $firstLease = $leases[0];
            $io->text("  - ID: " . $firstLease->getId());
            $io->text("  - Début: " . ($firstLease->getStartDate() ? $firstLease->getStartDate()->format('Y-m-d') : 'N/A'));
            $io->text("  - Fin: " . ($firstLease->getEndDate() ? $firstLease->getEndDate()->format('Y-m-d') : 'N/A'));
            $io->text("  - Propriété: " . ($firstLease->getProperty() ? $firstLease->getProperty()->getFullAddress() : 'N/A'));
        }

        // Test de la méthode getPaymentsForCalendar
        $io->section('Test getPaymentsForCalendar');

        $startDate = new \DateTime('2024-01-01');
        $endDate = new \DateTime('2024-12-31');

        // Créer le contrôleur
        $controller = new CalendarController();

        // Utiliser la réflexion pour accéder à la méthode privée
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getPaymentsForCalendar');
        $method->setAccessible(true);

        try {
            $paymentEvents = $method->invokeArgs($controller, [
                $this->paymentRepository,
                $startDate,
                $endDate,
                $user
            ]);

            $io->text("Événements paiements générés: " . count($paymentEvents));

            if (!empty($paymentEvents)) {
                $io->text("Premier événement paiement:");
                $firstEvent = $paymentEvents[0];
                $io->text("  - Titre: " . ($firstEvent['title'] ?? 'N/A'));
                $io->text("  - Type: " . ($firstEvent['type'] ?? 'N/A'));
                $io->text("  - Date: " . ($firstEvent['start'] ?? 'N/A'));
                $io->text("  - Couleur: " . ($firstEvent['color'] ?? 'N/A'));
                $io->text("  - Données complètes: " . json_encode($firstEvent, JSON_PRETTY_PRINT));
            }
        } catch (\Exception $e) {
            $io->error('❌ Erreur dans getPaymentsForCalendar: ' . $e->getMessage());
            $io->text('Trace: ' . $e->getTraceAsString());
        }

        // Test de la méthode getLeasesForCalendar
        $io->section('Test getLeasesForCalendar');

        try {
            $method = $reflection->getMethod('getLeasesForCalendar');
            $method->setAccessible(true);

            $leaseEvents = $method->invokeArgs($controller, [
                $this->leaseRepository,
                $startDate,
                $endDate,
                $user
            ]);

            $io->text("Événements baux générés: " . count($leaseEvents));

            if (!empty($leaseEvents)) {
                $io->text("Premier événement bail:");
                $firstEvent = $leaseEvents[0];
                $io->text("  - Titre: " . ($firstEvent['title'] ?? 'N/A'));
                $io->text("  - Type: " . ($firstEvent['type'] ?? 'N/A'));
                $io->text("  - Date: " . ($firstEvent['start'] ?? 'N/A'));
                $io->text("  - Couleur: " . ($firstEvent['color'] ?? 'N/A'));
                $io->text("  - Données complètes: " . json_encode($firstEvent, JSON_PRETTY_PRINT));
            }
        } catch (\Exception $e) {
            $io->error('❌ Erreur dans getLeasesForCalendar: ' . $e->getMessage());
            $io->text('Trace: ' . $e->getTraceAsString());
        }

        $io->success('🎉 Debug terminé');
        return Command::SUCCESS;
    }
}
