<?php

namespace App\Service;

use App\Entity\Payment;
use App\Entity\Tenant;
use App\Entity\Property;
use App\Entity\Lease;
use App\Entity\Document;
use App\Entity\MaintenanceRequest;
use App\Entity\AccountingEntry;
use App\Repository\PaymentRepository;
use App\Repository\TenantRepository;
use App\Repository\PropertyRepository;
use App\Repository\LeaseRepository;
use App\Repository\DocumentRepository;
use App\Repository\MaintenanceRequestRepository;
use App\Repository\AccountingEntryRepository;
use App\Service\CurrencyService;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Twig\Environment;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Filesystem\Filesystem;
use TCPDF;

class ExportService
{
    private EntityManagerInterface $entityManager;
    private Environment $twig;
    private Filesystem $filesystem;
    private CurrencyService $currencyService;
    private string $exportDir;

    public function __construct(
        EntityManagerInterface $entityManager,
        Environment $twig,
        Filesystem $filesystem,
        CurrencyService $currencyService
    ) {
        $this->entityManager = $entityManager;
        $this->twig = $twig;
        $this->filesystem = $filesystem;
        $this->currencyService = $currencyService;
        $this->exportDir = 'var/exports';

        // Créer le dossier d'export s'il n'existe pas
        if (!$this->filesystem->exists($this->exportDir)) {
            $this->filesystem->mkdir($this->exportDir);
        }
    }

    /**
     * Récupère la devise par défaut configurée
     */
    private function getDefaultCurrency(): string
    {
        $currency = $this->currencyService->getDefaultCurrency();
        return $currency->getSymbol();
    }

    public function generateFinancialReport(int $year, int $month, string $format): string
    {
        $startDate = new \DateTime("{$year}-{$month}-01");
        $endDate = new \DateTime("{$year}-{$month}-" . $startDate->format('t'));

        $payments = $this->entityManager->getRepository(Payment::class)
            ->createQueryBuilder('p')
            ->where('p.paidDate >= :startDate')
            ->andWhere('p.paidDate <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();

        $data = [
            'title' => "Rapport Financier {$year}-{$month}",
            'period' => "{$year}-{$month}",
            'payments' => $payments,
            'total_revenue' => array_sum(array_map(fn($p) => $p->getAmount(), $payments)),
            'total_payments' => count($payments),
        ];

        if ($format === 'excel') {
            return $this->generateExcelFinancialReport($data);
        } else {
            return $this->generatePdfFinancialReport($data);
        }
    }

    public function generatePaymentsExport(?string $startDate, ?string $endDate, string $status, string $format): string
    {
        $qb = $this->entityManager->getRepository(Payment::class)->createQueryBuilder('p');

        if ($startDate) {
            $qb->andWhere('p.paidDate >= :startDate')
               ->setParameter('startDate', new \DateTime($startDate));
        }

        if ($endDate) {
            $qb->andWhere('p.paidDate <= :endDate')
               ->setParameter('endDate', new \DateTime($endDate));
        }

        if ($status !== 'all') {
            $qb->andWhere('p.status = :status')
               ->setParameter('status', $status);
        }

        $payments = $qb->orderBy('p.paidDate', 'DESC')->getQuery()->getResult();

        $data = [
            'title' => 'Export Paiements',
            'payments' => $payments,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => $status,
            ],
        ];

        if ($format === 'excel') {
            return $this->generateExcelPaymentsExport($data);
        } else {
            return $this->generatePdfPaymentsExport($data);
        }
    }

    public function generateOverduePaymentsExport(string $format): string
    {
        $overduePayments = $this->entityManager->getRepository(Payment::class)
            ->createQueryBuilder('p')
            ->where('p.status != :status')
            ->andWhere('p.dueDate < :today')
            ->setParameter('status', 'Payé')
            ->setParameter('today', new \DateTime())
            ->orderBy('p.dueDate', 'ASC')
            ->getQuery()
            ->getResult();

        $data = [
            'title' => 'Paiements Impayés',
            'payments' => $overduePayments,
            'total_overdue' => array_sum(array_map(fn($p) => $p->getAmount(), $overduePayments)),
        ];

        if ($format === 'excel') {
            return $this->generateExcelOverdueExport($data);
        } else {
            return $this->generatePdfOverdueExport($data);
        }
    }

    public function generateTenantsExport(bool $includeHistory, string $format): string
    {
        $tenants = $this->entityManager->getRepository(Tenant::class)
            ->createQueryBuilder('t')
            ->leftJoin('t.leases', 'l')
            ->addSelect('l')
            ->orderBy('t.lastName', 'ASC')
            ->getQuery()
            ->getResult();

        $data = [
            'title' => 'Liste Locataires',
            'tenants' => $tenants,
            'include_history' => $includeHistory,
        ];

        if ($format === 'excel') {
            return $this->generateExcelTenantsExport($data);
        } else {
            return $this->generatePdfTenantsExport($data);
        }
    }

    public function generatePropertiesExport(bool $includeInventory, string $format): string
    {
        $properties = $this->entityManager->getRepository(Property::class)
            ->createQueryBuilder('p')
            ->leftJoin('p.leases', 'l')
            ->leftJoin('p.maintenanceRequests', 'mr')
            ->addSelect('l', 'mr')
            ->orderBy('p.address', 'ASC')
            ->getQuery()
            ->getResult();

        $data = [
            'title' => 'Inventaire Biens',
            'properties' => $properties,
            'include_inventory' => $includeInventory,
        ];

        if ($format === 'excel') {
            return $this->generateExcelPropertiesExport($data);
        } else {
            return $this->generatePdfPropertiesExport($data);
        }
    }

    public function generateLeasesExport(string $status, string $format): string
    {
        $qb = $this->entityManager->getRepository(Lease::class)->createQueryBuilder('l');

        if ($status !== 'all') {
            $qb->andWhere('l.status = :status')
               ->setParameter('status', $status);
        }

        $leases = $qb->leftJoin('l.tenant', 't')
                    ->leftJoin('l.property', 'p')
                    ->addSelect('t', 'p')
                    ->orderBy('l.startDate', 'DESC')
                    ->getQuery()
                    ->getResult();

        $data = [
            'title' => 'Export Baux',
            'leases' => $leases,
            'status' => $status,
        ];

        if ($format === 'excel') {
            return $this->generateExcelLeasesExport($data);
        } else {
            return $this->generatePdfLeasesExport($data);
        }
    }

    public function generateTaxDeclaration(int $year, string $format): string
    {
        $startDate = new \DateTime("{$year}-01-01");
        $endDate = new \DateTime("{$year}-12-31");

        $payments = $this->entityManager->getRepository(Payment::class)
            ->createQueryBuilder('p')
            ->where('p.paidDate >= :startDate')
            ->andWhere('p.paidDate <= :endDate')
            ->andWhere('p.status = :status')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('status', 'Payé')
            ->getQuery()
            ->getResult();

        $totalRevenue = array_sum(array_map(fn($p) => $p->getAmount(), $payments));

        $data = [
            'title' => "Déclaration Fiscale {$year}",
            'year' => $year,
            'payments' => $payments,
            'total_revenue' => $totalRevenue,
            'tax_base' => $totalRevenue * 0.7, // Base imposable estimée
        ];

        if ($format === 'excel') {
            return $this->generateExcelTaxDeclaration($data);
        } else {
            return $this->generatePdfTaxDeclaration($data);
        }
    }

    public function generateAccountingReport(string $startDate, string $endDate, string $format, ?int $organizationId = null, ?int $companyId = null): string
    {
        $startDateTime = new \DateTime($startDate);
        $endDateTime = new \DateTime($endDate);

        $qb = $this->entityManager->getRepository(AccountingEntry::class)
            ->createQueryBuilder('ae')
            ->where('ae.entryDate >= :startDate')
            ->andWhere('ae.entryDate <= :endDate')
            ->setParameter('startDate', $startDateTime)
            ->setParameter('endDate', $endDateTime);

        // Filtrage par organisation/société
        if ($companyId) {
            $qb->andWhere('ae.company = :companyId')
               ->setParameter('companyId', $companyId);
        } elseif ($organizationId) {
            $qb->andWhere('ae.organization = :organizationId')
               ->setParameter('organizationId', $organizationId);
        }

        $entries = $qb->orderBy('ae.entryDate', 'ASC')
            ->addOrderBy('ae.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        // Calculer les totaux
        $totalCredits = 0;
        $totalDebits = 0;
        $runningBalance = 0;

        foreach ($entries as $entry) {
            $amount = (float)$entry->getAmount();
            if ($entry->isCredit()) {
                $totalCredits += $amount;
                $runningBalance += $amount;
            } else {
                $totalDebits += $amount;
                $runningBalance -= $amount;
            }
        }

        $data = [
            'title' => 'Rapport Comptable',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'entries' => $entries,
            'total_credits' => $totalCredits,
            'total_debits' => $totalDebits,
            'net_balance' => $totalCredits - $totalDebits,
        ];

        if ($format === 'excel') {
            return $this->generateExcelAccountingReport($data);
        } else {
            return $this->generatePdfAccountingReport($data);
        }
    }

    public function generateCompleteExport(int $year, string $format): string
    {
        $exportData = [
            'year' => $year,
            'payments' => $this->entityManager->getRepository(Payment::class)->findAll(),
            'tenants' => $this->entityManager->getRepository(Tenant::class)->findAll(),
            'properties' => $this->entityManager->getRepository(Property::class)->findAll(),
            'leases' => $this->entityManager->getRepository(Lease::class)->findAll(),
            'documents' => $this->entityManager->getRepository(Document::class)->findAll(),
            'maintenance' => $this->entityManager->getRepository(MaintenanceRequest::class)->findAll(),
        ];

        return $this->generateZipCompleteExport($exportData);
    }

    // Méthodes privées pour générer les fichiers Excel
    private function generateExcelFinancialReport(array $data): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // En-tête
        $sheet->setCellValue('A1', $data['title']);
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        // Informations générales
        $sheet->setCellValue('A3', 'Période:');
        $sheet->setCellValue('B3', $data['period']);
        $sheet->setCellValue('A4', 'Total des revenus:');
        $sheet->setCellValue('B4', number_format($data['total_revenue'], 2) . ' ' . $this->getDefaultCurrency());
        $sheet->setCellValue('A5', 'Nombre de paiements:');
        $sheet->setCellValue('B5', $data['total_payments']);

        // En-têtes du tableau
        $headers = ['Date', 'Locataire', 'Propriété', 'Montant', 'Statut', 'Méthode'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '7', $header);
            $col++;
        }

        // Données
        $row = 8;
        foreach ($data['payments'] as $payment) {
            $sheet->setCellValue('A' . $row, $payment->getPaidDate() ? $payment->getPaidDate()->format('d/m/Y') : 'N/A');
            $sheet->setCellValue('B' . $row, $payment->getLease()?->getTenant()?->getFullName() ?? 'N/A');
            $sheet->setCellValue('C' . $row, $payment->getLease()?->getProperty()?->getAddress() ?? 'N/A');
            $sheet->setCellValue('D' . $row, number_format($payment->getAmount(), 2) . ' ' . $this->getDefaultCurrency());
            $sheet->setCellValue('E' . $row, $payment->getStatus());
            $sheet->setCellValue('F' . $row, $payment->getPaymentMethod() ?? 'N/A');
            $row++;
        }

        // Style
        $this->applyExcelStyles($sheet, 1, 7, $row - 1, 6);

        return $this->saveExcelFile($spreadsheet, 'rapport-financier');
    }

    private function generateExcelPaymentsExport(array $data): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', $data['title']);
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        $headers = ['ID', 'Date Paiement', 'Date Échéance', 'Locataire', 'Propriété', 'Montant', 'Statut', 'Méthode'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '3', $header);
            $col++;
        }

        $row = 4;
        foreach ($data['payments'] as $payment) {
            $sheet->setCellValue('A' . $row, $payment->getId());
            $sheet->setCellValue('B' . $row, $payment->getPaidDate() ? $payment->getPaidDate()->format('d/m/Y') : 'N/A');
            $sheet->setCellValue('C' . $row, $payment->getDueDate()->format('d/m/Y'));
            $sheet->setCellValue('D' . $row, $payment->getLease()?->getTenant()?->getFullName() ?? 'N/A');
            $sheet->setCellValue('E' . $row, $payment->getLease()?->getProperty()?->getAddress() ?? 'N/A');
            $sheet->setCellValue('F' . $row, number_format($payment->getAmount(), 2) . ' ' . $this->getDefaultCurrency());
            $sheet->setCellValue('G' . $row, $payment->getStatus());
            $sheet->setCellValue('H' . $row, $payment->getPaymentMethod() ?? 'N/A');
            $row++;
        }

        $this->applyExcelStyles($sheet, 1, 3, $row - 1, 8);

        return $this->saveExcelFile($spreadsheet, 'export-paiements');
    }

    private function generateExcelOverdueExport(array $data): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', $data['title']);
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        $sheet->setCellValue('A3', 'Total des impayés:');
        $sheet->setCellValue('B3', number_format($data['total_overdue'], 2) . ' ' . $this->getDefaultCurrency());

        $headers = ['Locataire', 'Propriété', 'Montant', 'Date Échéance', 'Jours de Retard', 'Statut', 'Actions'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '5', $header);
            $col++;
        }

        $row = 6;
        foreach ($data['payments'] as $payment) {
            $daysOverdue = (new \DateTime())->diff($payment->getDueDate())->days;

            $sheet->setCellValue('A' . $row, $payment->getLease()?->getTenant()?->getFullName() ?? 'N/A');
            $sheet->setCellValue('B' . $row, $payment->getLease()?->getProperty()?->getAddress() ?? 'N/A');
            $sheet->setCellValue('C' . $row, number_format($payment->getAmount(), 2) . ' ' . $this->getDefaultCurrency());
            $sheet->setCellValue('D' . $row, $payment->getDueDate()->format('d/m/Y'));
            $sheet->setCellValue('E' . $row, $daysOverdue . ' jours');
            $sheet->setCellValue('F' . $row, $payment->getStatus());
            $sheet->setCellValue('G' . $row, 'Relance requise');
            $row++;
        }

        $this->applyExcelStyles($sheet, 1, 5, $row - 1, 7);

        return $this->saveExcelFile($spreadsheet, 'paiements-impayes');
    }

    private function generateExcelTenantsExport(array $data): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', $data['title']);
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        $headers = ['Nom', 'Prénom', 'Email', 'Téléphone', 'Propriété', 'Statut'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '3', $header);
            $col++;
        }

        $row = 4;
        foreach ($data['tenants'] as $tenant) {
            $sheet->setCellValue('A' . $row, $tenant->getLastName());
            $sheet->setCellValue('B' . $row, $tenant->getFirstName());
            $sheet->setCellValue('C' . $row, $tenant->getEmail());
            $sheet->setCellValue('D' . $row, $tenant->getPhone());
            $sheet->setCellValue('E' . $row, $tenant->getLeases()->first()?->getProperty()?->getAddress() ?? 'N/A');
            $sheet->setCellValue('F' . $row, $tenant->getLeases()->first()?->getStatus() ?? 'N/A');
            $row++;
        }

        $this->applyExcelStyles($sheet, 1, 3, $row - 1, 6);

        return $this->saveExcelFile($spreadsheet, 'liste-locataires');
    }

    private function generateExcelPropertiesExport(array $data): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', $data['title']);
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        $headers = ['Adresse', 'Type', 'Surface', 'Loyer', 'Statut'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '3', $header);
            $col++;
        }

        $row = 4;
        foreach ($data['properties'] as $property) {
            $sheet->setCellValue('A' . $row, $property->getAddress());
            $sheet->setCellValue('B' . $row, $property->getPropertyType() ?? 'N/A');
            $sheet->setCellValue('C' . $row, $property->getSurface() . ' m²');
            $sheet->setCellValue('D' . $row, number_format((float)$property->getMonthlyRent(), 2) . ' ' . $this->getDefaultCurrency());
            $firstLease = $property->getLeases()->first();
            $sheet->setCellValue('E' . $row, $firstLease ? $firstLease->getStatus() : 'Libre');
            $row++;
        }

        $this->applyExcelStyles($sheet, 1, 3, $row - 1, 5);

        return $this->saveExcelFile($spreadsheet, 'inventaire-biens');
    }

    private function generateExcelLeasesExport(array $data): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', $data['title']);
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        $headers = ['Locataire', 'Propriété', 'Début', 'Fin', 'Loyer', 'Statut', 'Garantie'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '3', $header);
            $col++;
        }

        $row = 4;
        foreach ($data['leases'] as $lease) {
            $sheet->setCellValue('A' . $row, $lease->getTenant()?->getFullName() ?? 'N/A');
            $sheet->setCellValue('B' . $row, $lease->getProperty()?->getAddress() ?? 'N/A');
            $sheet->setCellValue('C' . $row, $lease->getStartDate()->format('d/m/Y'));
            $sheet->setCellValue('D' . $row, $lease->getEndDate()->format('d/m/Y'));
            $sheet->setCellValue('E' . $row, number_format($lease->getMonthlyRent(), 2) . ' ' . $this->getDefaultCurrency());
            $sheet->setCellValue('F' . $row, $lease->getStatus());
            $sheet->setCellValue('G' . $row, number_format($lease->getSecurityDeposit(), 2) . ' ' . $this->getDefaultCurrency());
            $row++;
        }

        $this->applyExcelStyles($sheet, 1, 3, $row - 1, 7);

        return $this->saveExcelFile($spreadsheet, 'export-baux');
    }

    private function generateExcelTaxDeclaration(array $data): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', $data['title']);
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        $sheet->setCellValue('A3', 'Année:');
        $sheet->setCellValue('B3', $data['year']);
        $sheet->setCellValue('A4', 'Revenus bruts:');
        $sheet->setCellValue('B4', number_format($data['total_revenue'], 2) . ' ' . $this->getDefaultCurrency());
        $sheet->setCellValue('A5', 'Base imposable (70%):');
        $sheet->setCellValue('B5', number_format($data['tax_base'], 2) . ' ' . $this->getDefaultCurrency());

        $headers = ['Mois', 'Revenus', 'Nombre de paiements', 'Moyenne mensuelle'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '7', $header);
            $col++;
        }

        $monthlyData = [];
        foreach ($data['payments'] as $payment) {
            if ($payment->getPaidDate()) {
                $month = $payment->getPaidDate()->format('Y-m');
                if (!isset($monthlyData[$month])) {
                    $monthlyData[$month] = ['revenue' => 0, 'count' => 0];
                }
                $monthlyData[$month]['revenue'] += $payment->getAmount();
                $monthlyData[$month]['count']++;
            }
        }

        $row = 8;
        foreach ($monthlyData as $month => $data) {
            $sheet->setCellValue('A' . $row, $month);
            $sheet->setCellValue('B' . $row, number_format($data['revenue'], 2) . ' ' . $this->getDefaultCurrency());
            $sheet->setCellValue('C' . $row, $data['count']);
            $sheet->setCellValue('D' . $row, number_format($data['revenue'] / $data['count'], 2) . ' ' . $this->getDefaultCurrency());
            $row++;
        }

        $this->applyExcelStyles($sheet, 1, 7, $row - 1, 4);

        return $this->saveExcelFile($spreadsheet, 'declaration-fiscale');
    }

    private function generateExcelAccountingReport(array $data): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', $data['title']);
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        $sheet->setCellValue('A3', 'Période:');
        $sheet->setCellValue('B3', $data['start_date'] . ' - ' . $data['end_date']);
        $sheet->setCellValue('A4', 'Total CRÉDITS:');
        $sheet->setCellValue('B4', number_format($data['total_credits'], 2) . ' ' . $this->getDefaultCurrency());
        $sheet->setCellValue('D4', 'Total DÉBITS:');
        $sheet->setCellValue('E4', number_format($data['total_debits'], 2) . ' ' . $this->getDefaultCurrency());
        $sheet->setCellValue('G4', 'Solde NET:');
        $sheet->setCellValue('H4', number_format($data['net_balance'], 2) . ' ' . $this->getDefaultCurrency());

        $headers = ['Date', 'Référence', 'Type', 'Catégorie', 'Description', 'DÉBIT', 'CRÉDIT', 'Solde', 'Organisation'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '6', $header);
            $sheet->getStyle($col . '6')->getFont()->setBold(true);
            $col++;
        }

        $row = 7;
        $runningBalance = 0;
        foreach ($data['entries'] as $entry) {
            $sheet->setCellValue('A' . $row, $entry->getEntryDate() ? $entry->getEntryDate()->format('d/m/Y') : 'N/A');
            $sheet->setCellValue('B' . $row, $entry->getReference() ?? 'N/A');
            $sheet->setCellValue('C' . $row, $entry->getType());
            $sheet->setCellValue('D' . $row, $entry->getCategory());
            $sheet->setCellValue('E' . $row, $entry->getDescription());
            
            // Calculer le solde
            $amount = (float)$entry->getAmount();
            if ($entry->isCredit()) {
                $sheet->setCellValue('F' . $row, ''); // DÉBIT vide
                $sheet->setCellValue('G' . $row, number_format($amount, 2)); // CRÉDIT
                $runningBalance += $amount;
            } else {
                $sheet->setCellValue('F' . $row, number_format($amount, 2)); // DÉBIT
                $sheet->setCellValue('G' . $row, ''); // CRÉDIT vide
                $runningBalance -= $amount;
            }
            
            $sheet->setCellValue('H' . $row, number_format($runningBalance, 2)); // Solde courant
            $sheet->setCellValue('I' . $row, $entry->getOrganization()?->getName() ?? 'N/A');
            
            // Colorier les lignes selon le type
            if ($entry->isCredit()) {
                $sheet->getStyle('G' . $row)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_DARKGREEN);
            } else {
                $sheet->getStyle('F' . $row)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_DARKRED);
            }
            
            $row++;
        }

        // Appliquer les styles
        $this->applyExcelStyles($sheet, 1, 6, $row - 1, 9);
        
        // Ajuster la largeur des colonnes
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(10);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(30);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(15);
        $sheet->getColumnDimension('I')->setWidth(20);

        return $this->saveExcelFile($spreadsheet, 'rapport-comptable');
    }

    private function savePdfFile(TCPDF $pdf, string $filename): string
    {
        $filePath = $this->exportDir . '/' . $filename . '-' . date('Y-m-d-H-i-s') . '.pdf';

        // Utiliser le chemin absolu pour éviter les problèmes d'URL
        $absolutePath = realpath($this->exportDir) . '/' . $filename . '-' . date('Y-m-d-H-i-s') . '.pdf';

        $pdf->Output($absolutePath, 'F');

        return $absolutePath;
    }

    private function generateZipCompleteExport(array $data): string
    {
        $zipFile = $this->exportDir . '/export-complet-' . date('Y-m-d-H-i-s') . '.zip';
        $absoluteZipPath = realpath($this->exportDir) . '/export-complet-' . date('Y-m-d-H-i-s') . '.zip';

        $zip = new \ZipArchive();

        if ($zip->open($absoluteZipPath, \ZipArchive::CREATE) !== TRUE) {
            throw new \Exception('Impossible de créer le fichier ZIP');
        }

        // Ajouter chaque type de données
        $zip->addFromString('paiements.csv', $this->arrayToCsv($data['payments']));
        $zip->addFromString('locataires.csv', $this->arrayToCsv($data['tenants']));
        $zip->addFromString('proprietes.csv', $this->arrayToCsv($data['properties']));
        $zip->addFromString('baux.csv', $this->arrayToCsv($data['leases']));
        $zip->addFromString('documents.csv', $this->arrayToCsv($data['documents']));
        $zip->addFromString('maintenance.csv', $this->arrayToCsv($data['maintenance']));

        $zip->close();

        return $absoluteZipPath;
    }

    // Méthodes utilitaires
    private function applyExcelStyles($sheet, int $startRow, int $headerRow, int $endRow, int $endCol): void
    {
        $headerRange = 'A' . $headerRow . ':' . chr(64 + $endCol) . $headerRow;
        $dataRange = 'A' . $startRow . ':' . chr(64 + $endCol) . $endRow;

        // Style des en-têtes
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E3F2FD']
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        // Style des données
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
        ]);

        // Auto-resize des colonnes
        foreach (range('A', chr(64 + $endCol)) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function saveExcelFile(Spreadsheet $spreadsheet, string $filename): string
    {
        $filePath = $this->exportDir . '/' . $filename . '-' . date('Y-m-d-H-i-s') . '.xlsx';

        // Utiliser le chemin absolu pour éviter les problèmes d'URL
        $absolutePath = realpath($this->exportDir) . '/' . $filename . '-' . date('Y-m-d-H-i-s') . '.xlsx';

        $writer = new Xlsx($spreadsheet);
        $writer->save($absolutePath);

        return $absolutePath;
    }

    private function arrayToCsv(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');

        // En-têtes
        $firstItem = reset($data);
        if (is_object($firstItem)) {
            $headers = [];
            $reflection = new \ReflectionClass($firstItem);
            foreach ($reflection->getProperties() as $property) {
                $headers[] = $property->getName();
            }
            fputcsv($output, $headers);
        }

        // Données
        foreach ($data as $item) {
            if (is_object($item)) {
                $row = [];
                $reflection = new \ReflectionClass($item);
                foreach ($reflection->getProperties() as $property) {
                    $property->setAccessible(true);
                    $value = $property->getValue($item);
                    if ($value instanceof \DateTime) {
                        $row[] = $value->format('Y-m-d H:i:s');
                    } else {
                        $row[] = $value;
                    }
                }
                fputcsv($output, $row);
            }
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    // Méthodes PDF avec TCPDF
    private function generatePdfFinancialReport(array $data): string
    {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Configuration du document
        $pdf->SetCreator('LOKAPRO');
        $pdf->SetAuthor('LOKAPRO System');
        $pdf->SetTitle($data['title']);
        $pdf->SetSubject('Rapport Financier');

        // Configuration des marges
        $pdf->SetMargins(15, 20, 15);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(10);

        // Ajout d'une page
        $pdf->AddPage();

        // En-tête
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, $data['title'], 0, 1, 'C');
        $pdf->Ln(5);

        // Informations générales
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Période: ' . $data['period'], 0, 1);
        $pdf->Cell(0, 8, 'Total des revenus: ' . number_format($data['total_revenue'], 2) . ' ' . $this->getDefaultCurrency(), 0, 1);
        $pdf->Cell(0, 8, 'Nombre de paiements: ' . $data['total_payments'], 0, 1);
        $pdf->Ln(10);

        // Tableau des paiements
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(30, 8, 'Date', 1, 0, 'C');
        $pdf->Cell(50, 8, 'Locataire', 1, 0, 'C');
        $pdf->Cell(50, 8, 'Propriété', 1, 0, 'C');
        $pdf->Cell(30, 8, 'Montant', 1, 0, 'C');
        $pdf->Cell(30, 8, 'Statut', 1, 1, 'C');

        $pdf->SetFont('helvetica', '', 9);
        foreach ($data['payments'] as $payment) {
            $pdf->Cell(30, 8, $payment->getPaidDate() ? $payment->getPaidDate()->format('d/m/Y') : 'N/A', 1, 0, 'C');
            $pdf->Cell(50, 8, $payment->getLease()?->getTenant()?->getFullName() ?? 'N/A', 1, 0, 'L');
            $pdf->Cell(50, 8, $payment->getLease()?->getProperty()?->getAddress() ?? 'N/A', 1, 0, 'L');
            $pdf->Cell(30, 8, number_format($payment->getAmount(), 2) . ' ' . $this->getDefaultCurrency(), 1, 0, 'R');
            $pdf->Cell(30, 8, $payment->getStatus(), 1, 1, 'C');
        }

        return $this->savePdfFile($pdf, 'rapport-financier');
    }

    private function generatePdfPaymentsExport(array $data): string
    {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->SetCreator('LOKAPRO');
        $pdf->SetAuthor('LOKAPRO System');
        $pdf->SetTitle($data['title']);
        $pdf->SetSubject('Export Paiements');

        $pdf->SetMargins(10, 20, 10);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(10);

        $pdf->AddPage();

        // En-tête
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, $data['title'], 0, 1, 'C');
        $pdf->Ln(5);

        // Filtres appliqués
        if (!empty($data['filters']['start_date']) || !empty($data['filters']['end_date'])) {
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 6, 'Période: ' . ($data['filters']['start_date'] ?? 'Début') . ' - ' . ($data['filters']['end_date'] ?? 'Fin'), 0, 1);
        }

        $pdf->Ln(5);

        // Tableau
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(15, 8, 'ID', 1, 0, 'C');
        $pdf->Cell(25, 8, 'Date Paiement', 1, 0, 'C');
        $pdf->Cell(25, 8, 'Date Échéance', 1, 0, 'C');
        $pdf->Cell(40, 8, 'Locataire', 1, 0, 'C');
        $pdf->Cell(40, 8, 'Propriété', 1, 0, 'C');
        $pdf->Cell(25, 8, 'Montant', 1, 0, 'C');
        $pdf->Cell(20, 8, 'Statut', 1, 1, 'C');

        $pdf->SetFont('helvetica', '', 7);
        foreach ($data['payments'] as $payment) {
            $pdf->Cell(15, 8, $payment->getId(), 1, 0, 'C');
            $pdf->Cell(25, 8, $payment->getPaidDate() ? $payment->getPaidDate()->format('d/m/Y') : 'N/A', 1, 0, 'C');
            $pdf->Cell(25, 8, $payment->getDueDate()->format('d/m/Y'), 1, 0, 'C');
            $pdf->Cell(40, 8, substr($payment->getLease()?->getTenant()?->getFullName() ?? 'N/A', 0, 20), 1, 0, 'L');
            $pdf->Cell(40, 8, substr($payment->getLease()?->getProperty()?->getAddress() ?? 'N/A', 0, 20), 1, 0, 'L');
            $pdf->Cell(25, 8, number_format($payment->getAmount(), 2) . ' ' . $this->getDefaultCurrency(), 1, 0, 'R');
            $pdf->Cell(20, 8, $payment->getStatus(), 1, 1, 'C');
        }

        return $this->savePdfFile($pdf, 'export-paiements');
    }

    private function generatePdfOverdueExport(array $data): string
    {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->SetCreator('LOKAPRO');
        $pdf->SetAuthor('LOKAPRO System');
        $pdf->SetTitle($data['title']);
        $pdf->SetSubject('Paiements Impayés');

        $pdf->SetMargins(15, 20, 15);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(10);

        $pdf->AddPage();

        // En-tête
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, $data['title'], 0, 1, 'C');
        $pdf->Ln(5);

        // Total des impayés
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Total des impayés: ' . number_format($data['total_overdue'], 2) . ' ' . $this->getDefaultCurrency(), 0, 1);
        $pdf->Ln(10);

        // Tableau
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(50, 8, 'Locataire', 1, 0, 'C');
        $pdf->Cell(60, 8, 'Propriété', 1, 0, 'C');
        $pdf->Cell(30, 8, 'Montant', 1, 0, 'C');
        $pdf->Cell(25, 8, 'Date Échéance', 1, 0, 'C');
        $pdf->Cell(25, 8, 'Jours Retard', 1, 1, 'C');

        $pdf->SetFont('helvetica', '', 9);
        foreach ($data['payments'] as $payment) {
            $daysOverdue = (new \DateTime())->diff($payment->getDueDate())->days;

            $pdf->Cell(50, 8, $payment->getLease()?->getTenant()?->getFullName() ?? 'N/A', 1, 0, 'L');
            $pdf->Cell(60, 8, $payment->getLease()?->getProperty()?->getAddress() ?? 'N/A', 1, 0, 'L');
            $pdf->Cell(30, 8, number_format($payment->getAmount(), 2) . ' ' . $this->getDefaultCurrency(), 1, 0, 'R');
            $pdf->Cell(25, 8, $payment->getDueDate()->format('d/m/Y'), 1, 0, 'C');
            $pdf->Cell(25, 8, $daysOverdue . ' jours', 1, 1, 'C');
        }

        return $this->savePdfFile($pdf, 'paiements-impayes');
    }

    private function generatePdfTenantsExport(array $data): string
    {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->SetCreator('LOKAPRO');
        $pdf->SetAuthor('LOKAPRO System');
        $pdf->SetTitle($data['title']);
        $pdf->SetSubject('Liste Locataires');

        $pdf->SetMargins(15, 20, 15);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(10);

        $pdf->AddPage();

        // En-tête
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, $data['title'], 0, 1, 'C');
        $pdf->Ln(5);

        if ($data['include_history']) {
            $pdf->SetFont('helvetica', 'I', 10);
            $pdf->Cell(0, 6, 'Avec historique des paiements', 0, 1, 'C');
            $pdf->Ln(5);
        }

        // Tableau
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(40, 8, 'Nom', 1, 0, 'C');
        $pdf->Cell(40, 8, 'Prénom', 1, 0, 'C');
        $pdf->Cell(60, 8, 'Email', 1, 0, 'C');
        $pdf->Cell(30, 8, 'Téléphone', 1, 0, 'C');
        $pdf->Cell(20, 8, 'Statut', 1, 1, 'C');

        $pdf->SetFont('helvetica', '', 9);
        foreach ($data['tenants'] as $tenant) {
            $pdf->Cell(40, 8, $tenant->getLastName(), 1, 0, 'L');
            $pdf->Cell(40, 8, $tenant->getFirstName(), 1, 0, 'L');
            $pdf->Cell(60, 8, $tenant->getEmail(), 1, 0, 'L');
            $pdf->Cell(30, 8, $tenant->getPhone(), 1, 0, 'C');
            $pdf->Cell(20, 8, $tenant->getLeases()->first()?->getStatus() ?? 'N/A', 1, 1, 'C');
        }

        return $this->savePdfFile($pdf, 'liste-locataires');
    }

    private function generatePdfPropertiesExport(array $data): string
    {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->SetCreator('LOKAPRO');
        $pdf->SetAuthor('LOKAPRO System');
        $pdf->SetTitle($data['title']);
        $pdf->SetSubject('Inventaire Biens');

        $pdf->SetMargins(15, 20, 15);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(10);

        $pdf->AddPage();

        // En-tête
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, $data['title'], 0, 1, 'C');
        $pdf->Ln(5);

        if ($data['include_inventory']) {
            $pdf->SetFont('helvetica', 'I', 10);
            $pdf->Cell(0, 6, 'Inventaire complet', 0, 1, 'C');
            $pdf->Ln(5);
        }

        // Tableau
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(80, 8, 'Adresse', 1, 0, 'C');
        $pdf->Cell(30, 8, 'Type', 1, 0, 'C');
        $pdf->Cell(25, 8, 'Surface', 1, 0, 'C');
        $pdf->Cell(30, 8, 'Loyer', 1, 0, 'C');
        $pdf->Cell(25, 8, 'Statut', 1, 1, 'C');

        $pdf->SetFont('helvetica', '', 9);
        foreach ($data['properties'] as $property) {
            $pdf->Cell(80, 8, $property->getAddress(), 1, 0, 'L');
            $pdf->Cell(30, 8, $property->getPropertyType() ?? 'N/A', 1, 0, 'C');
            $pdf->Cell(25, 8, $property->getSurface() . ' m²', 1, 0, 'C');
            $pdf->Cell(30, 8, number_format((float)$property->getMonthlyRent(), 2) . ' ' . $this->getDefaultCurrency(), 1, 0, 'R');
            $firstLease = $property->getLeases()->first();
            $pdf->Cell(25, 8, $firstLease ? $firstLease->getStatus() : 'Libre', 1, 1, 'C');
        }

        return $this->savePdfFile($pdf, 'inventaire-biens');
    }

    private function generatePdfLeasesExport(array $data): string
    {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->SetCreator('LOKAPRO');
        $pdf->SetAuthor('LOKAPRO System');
        $pdf->SetTitle($data['title']);
        $pdf->SetSubject('Export Baux');

        $pdf->SetMargins(10, 20, 10);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(10);

        $pdf->AddPage();

        // En-tête
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, $data['title'], 0, 1, 'C');
        $pdf->Ln(5);

        if ($data['status'] !== 'all') {
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 6, 'Statut: ' . $data['status'], 0, 1, 'C');
            $pdf->Ln(5);
        }

        // Tableau
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(40, 8, 'Locataire', 1, 0, 'C');
        $pdf->Cell(50, 8, 'Propriété', 1, 0, 'C');
        $pdf->Cell(25, 8, 'Début', 1, 0, 'C');
        $pdf->Cell(25, 8, 'Fin', 1, 0, 'C');
        $pdf->Cell(25, 8, 'Loyer', 1, 0, 'C');
        $pdf->Cell(25, 8, 'Statut', 1, 1, 'C');

        $pdf->SetFont('helvetica', '', 7);
        foreach ($data['leases'] as $lease) {
            $pdf->Cell(40, 8, substr($lease->getTenant()?->getFullName() ?? 'N/A', 0, 18), 1, 0, 'L');
            $pdf->Cell(50, 8, substr($lease->getProperty()?->getAddress() ?? 'N/A', 0, 25), 1, 0, 'L');
            $pdf->Cell(25, 8, $lease->getStartDate()->format('d/m/Y'), 1, 0, 'C');
            $pdf->Cell(25, 8, $lease->getEndDate()->format('d/m/Y'), 1, 0, 'C');
            $pdf->Cell(25, 8, number_format($lease->getMonthlyRent(), 2) . ' ' . $this->getDefaultCurrency(), 1, 0, 'R');
            $pdf->Cell(25, 8, $lease->getStatus(), 1, 1, 'C');
        }

        return $this->savePdfFile($pdf, 'export-baux');
    }

    private function generatePdfTaxDeclaration(array $data): string
    {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->SetCreator('LOKAPRO');
        $pdf->SetAuthor('LOKAPRO System');
        $pdf->SetTitle($data['title']);
        $pdf->SetSubject('Déclaration Fiscale');

        $pdf->SetMargins(20, 20, 20);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(10);

        $pdf->AddPage();

        // En-tête
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->Cell(0, 12, $data['title'], 0, 1, 'C');
        $pdf->Ln(10);

        // Informations générales
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Année: ' . $data['year'], 0, 1);
        $pdf->Cell(0, 8, 'Revenus bruts: ' . number_format($data['total_revenue'], 2) . ' ' . $this->getDefaultCurrency(), 0, 1);
        $pdf->Cell(0, 8, 'Base imposable (70%): ' . number_format($data['tax_base'], 2) . ' ' . $this->getDefaultCurrency(), 0, 1);
        $pdf->Ln(15);

        // Répartition mensuelle
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 8, 'Répartition Mensuelle des Revenus', 0, 1, 'C');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(40, 8, 'Mois', 1, 0, 'C');
        $pdf->Cell(40, 8, 'Revenus', 1, 0, 'C');
        $pdf->Cell(40, 8, 'Nb Paiements', 1, 0, 'C');
        $pdf->Cell(40, 8, 'Moyenne mensuelle', 1, 1, 'C');

        $pdf->SetFont('helvetica', '', 9);

        $monthlyData = [];
        foreach ($data['payments'] as $payment) {
            if ($payment->getPaidDate()) {
                $month = $payment->getPaidDate()->format('Y-m');
                if (!isset($monthlyData[$month])) {
                    $monthlyData[$month] = ['revenue' => 0, 'count' => 0];
                }
                $monthlyData[$month]['revenue'] += $payment->getAmount();
                $monthlyData[$month]['count']++;
            }
        }

        foreach ($monthlyData as $month => $monthData) {
            $pdf->Cell(40, 8, $month, 1, 0, 'C');
            $pdf->Cell(40, 8, number_format($monthData['revenue'], 2) . ' ' . $this->getDefaultCurrency(), 1, 0, 'R');
            $pdf->Cell(40, 8, $monthData['count'], 1, 0, 'C');
            $pdf->Cell(40, 8, number_format($monthData['revenue'] / $monthData['count'], 2) . ' ' . $this->getDefaultCurrency(), 1, 1, 'R');
        }

        return $this->savePdfFile($pdf, 'declaration-fiscale');
    }

    private function generatePdfAccountingReport(array $data): string
    {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->SetCreator('LOKAPRO');
        $pdf->SetAuthor('LOKAPRO System');
        $pdf->SetTitle($data['title']);
        $pdf->SetSubject('Rapport Comptable');

        $pdf->SetMargins(10, 20, 10);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(10);

        $pdf->AddPage();

        // En-tête
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, $data['title'], 0, 1, 'C');
        $pdf->Ln(5);

        // Période et totaux
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, 'Période: ' . $data['start_date'] . ' - ' . $data['end_date'], 0, 1, 'C');
        $pdf->Ln(3);
        $pdf->Cell(0, 6, 'Total CRÉDITS: ' . number_format($data['total_credits'], 2) . ' ' . $this->getDefaultCurrency(), 0, 1, 'C');
        $pdf->Cell(0, 6, 'Total DÉBITS: ' . number_format($data['total_debits'], 2) . ' ' . $this->getDefaultCurrency(), 0, 1, 'C');
        $pdf->Cell(0, 6, 'Solde NET: ' . number_format($data['net_balance'], 2) . ' ' . $this->getDefaultCurrency(), 0, 1, 'C');
        $pdf->Ln(10);

        // Tableau avec affichage bancaire
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(18, 8, 'Date', 1, 0, 'C');
        $pdf->Cell(25, 8, 'Référence', 1, 0, 'C');
        $pdf->Cell(10, 8, 'Type', 1, 0, 'C');
        $pdf->Cell(20, 8, 'Catégorie', 1, 0, 'C');
        $pdf->Cell(35, 8, 'Description', 1, 0, 'C');
        $pdf->Cell(20, 8, 'DÉBIT', 1, 0, 'C');
        $pdf->Cell(20, 8, 'CRÉDIT', 1, 0, 'C');
        $pdf->Cell(20, 8, 'Solde', 1, 1, 'C');

        $pdf->SetFont('helvetica', '', 6);
        $runningBalance = 0;
        foreach ($data['entries'] as $entry) {
            $amount = (float)$entry->getAmount();
            
            $pdf->Cell(18, 7, $entry->getEntryDate() ? $entry->getEntryDate()->format('d/m/Y') : 'N/A', 1, 0, 'C');
            $pdf->Cell(25, 7, substr($entry->getReference() ?? 'N/A', 0, 15), 1, 0, 'L');
            $pdf->Cell(10, 7, substr($entry->getType(), 0, 5), 1, 0, 'C');
            $pdf->Cell(20, 7, substr($entry->getCategory(), 0, 10), 1, 0, 'L');
            $pdf->Cell(35, 7, substr($entry->getDescription(), 0, 20), 1, 0, 'L');
            
            // Colonnes DÉBIT et CRÉDIT
            if ($entry->isCredit()) {
                $pdf->Cell(20, 7, '', 1, 0, 'R'); // DÉBIT vide
                $pdf->SetTextColor(0, 128, 0); // Vert pour CRÉDIT
                $pdf->Cell(20, 7, number_format($amount, 2), 1, 0, 'R');
                $pdf->SetTextColor(0, 0, 0); // Remettre noir
                $runningBalance += $amount;
            } else {
                $pdf->SetTextColor(139, 0, 0); // Rouge pour DÉBIT
                $pdf->Cell(20, 7, number_format($amount, 2), 1, 0, 'R');
                $pdf->SetTextColor(0, 0, 0); // Remettre noir
                $pdf->Cell(20, 7, '', 1, 0, 'R'); // CRÉDIT vide
                $runningBalance -= $amount;
            }
            
            $pdf->Cell(20, 7, number_format($runningBalance, 2), 1, 1, 'R');
        }

        return $this->savePdfFile($pdf, 'rapport-comptable');
    }
}
