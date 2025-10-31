<?php

namespace App\Service;

use App\Entity\Payment;
use App\Entity\Document;
use App\Entity\Lease;
use App\Entity\AccountingEntry;
use App\Repository\DocumentRepository;
use App\Repository\AccountingEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Service pour générer les quittances de loyer et avis d'échéances
 */
class RentReceiptService
{
    public function __construct(
        private Environment $twig,
        private EntityManagerInterface $entityManager,
        private DocumentRepository $documentRepository,
        private AccountingEntryRepository $accountingRepository,
        private SettingsService $settingsService,
        private ParameterBagInterface $params,
        private AccountingConfigService $accountingConfigService
    ) {
    }

    /**
     * Génère une quittance de loyer pour un paiement
     */
    public function generateRentReceipt(Payment $payment): Document
    {
        // Validation des données nécessaires
        if (!$payment->getLease()) {
            throw new \InvalidArgumentException("Le paiement n'a pas de bail associé");
        }

        $lease = $payment->getLease();
        if (!$lease->getTenant()) {
            throw new \InvalidArgumentException("Le bail n'a pas de locataire associé");
        }

        $tenant = $lease->getTenant();

        // Vérifier si une quittance existe déjà pour ce paiement (via le nom du fichier unique)
        $fileName = sprintf(
            'quittance_%s_%s.pdf',
            $tenant->getLastName(),
            $payment->getDueDate()->format('Y_m')
        );

        $existingReceipt = $this->documentRepository->findOneBy([
            'fileName' => $fileName,
            'type' => 'Quittance de loyer'
        ]);

        if ($existingReceipt) {
            return $existingReceipt;
        }

        // Récupérer la société émettrice avec fallback sécurisé
        $company = $payment->getCompany();
        if (!$company && $lease->getCompany()) {
            $company = $lease->getCompany();
        }
        if (!$company && $lease->getProperty() && $lease->getProperty()->getCompany()) {
            $company = $lease->getProperty()->getCompany();
        }

        $organization = $payment->getOrganization() ?: $lease->getOrganization();

        // Générer le HTML de la quittance
        $html = $this->twig->render('pdf/rent_receipt.html.twig', [
            'payment' => $payment,
            'lease' => $lease,
            'tenant' => $tenant,
            'property' => $lease->getProperty(),
            'company' => $company, // ✅ Informations de la société
            'organization' => $organization,
            'settings' => [
                'company_name' => $company ? ($company->getLegalName() ?: $company->getName()) : $this->settingsService->get('company_name', 'LOKAPRO'),
                'company_address' => $company ? $company->getAddress() : $this->settingsService->get('company_address', ''),
                'company_city' => $company ? ($company->getPostalCode() . ' ' . $company->getCity()) : '',
                'company_phone' => $company ? $company->getPhone() : $this->settingsService->get('company_phone', ''),
                'company_email' => $company ? $company->getEmail() : $this->settingsService->get('company_email', ''),
                'company_siret' => $company ? $company->getRegistrationNumber() : '',
                'company_website' => $company ? $company->getWebsite() : '',
            ]
        ]);

        // Générer le PDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Sauvegarder le PDF
        $pdfContent = $dompdf->output();
        $filePath = 'uploads/documents/' . $fileName;

        // Créer le dossier si nécessaire
        $uploadDir = $this->getProjectDir() . '/public/uploads/documents';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        file_put_contents($this->getProjectDir() . '/public/' . $filePath, $pdfContent);

        // Créer l'entité Document
        $document = new Document();

        // Vérifier que les données nécessaires sont présentes
        if (!$payment->getDueDate()) {
            throw new \InvalidArgumentException("La date d'échéance du paiement est manquante");
        }

        $document->setName('Quittance de loyer - ' . $payment->getDueDate()->format('F Y'));
        $document->setType('Quittance de loyer');
        $document->setFileName($fileName);
        $document->setOriginalFileName($fileName);
        $document->setMimeType('application/pdf');
        $document->setDescription('Quittance de loyer pour le paiement #' . $payment->getId());
        $document->setTenant($tenant);
        $document->setProperty($lease->getProperty());
        $document->setLease($lease);
        $document->setDocumentDate($payment->getPaidDate());
        $document->setOrganization($organization); // ✅ Assigner organization
        $document->setCompany($company); // ✅ Assigner company

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        // Créer automatiquement une écriture comptable pour la quittance
        $this->createAccountingEntryForReceipt($document, $payment);

        // Libérer la mémoire après génération
        $this->optimizeMemoryUsage();

        return $document;
    }

    /**
     * Génère un avis d'échéance pour un paiement à venir
     */
    public function generatePaymentNotice(Payment $payment): Document
    {
        // Validation des données nécessaires
        if (!$payment->getLease()) {
            throw new \InvalidArgumentException("Le paiement n'a pas de bail associé");
        }

        $lease = $payment->getLease();
        if (!$lease->getTenant()) {
            throw new \InvalidArgumentException("Le bail n'a pas de locataire associé");
        }

        $tenant = $lease->getTenant();

        // Vérifier si un avis existe déjà pour ce paiement (via le nom du fichier unique)
        $fileName = sprintf(
            'avis_echeance_%s_%s.pdf',
            $tenant->getLastName(),
            $payment->getDueDate()->format('Y_m')
        );

        $existingNotice = $this->documentRepository->findOneBy([
            'fileName' => $fileName,
            'type' => 'Avis d\'échéance'
        ]);

        if ($existingNotice) {
            return $existingNotice;
        }

        // Récupérer la société émettrice avec fallback sécurisé
        $company = $payment->getCompany();
        if (!$company && $lease->getCompany()) {
            $company = $lease->getCompany();
        }
        if (!$company && $lease->getProperty() && $lease->getProperty()->getCompany()) {
            $company = $lease->getProperty()->getCompany();
        }

        $organization = $payment->getOrganization() ?: $lease->getOrganization();

        // Générer le HTML de l'avis d'échéance
        $html = $this->twig->render('pdf/payment_notice.html.twig', [
            'payment' => $payment,
            'lease' => $lease,
            'tenant' => $tenant,
            'property' => $lease->getProperty(),
            'company' => $company, // ✅ Informations de la société
            'organization' => $organization,
            'settings' => [
                'company_name' => $company ? ($company->getLegalName() ?: $company->getName()) : $this->settingsService->get('company_name', 'LOKAPRO'),
                'company_address' => $company ? $company->getAddress() : $this->settingsService->get('company_address', ''),
                'company_city' => $company ? ($company->getPostalCode() . ' ' . $company->getCity()) : '',
                'company_phone' => $company ? $company->getPhone() : $this->settingsService->get('company_phone', ''),
                'company_email' => $company ? $company->getEmail() : $this->settingsService->get('company_email', ''),
                'company_siret' => $company ? $company->getRegistrationNumber() : '',
                'company_website' => $company ? $company->getWebsite() : '',
            ]
        ]);

        // Générer le PDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Sauvegarder le PDF
        $pdfContent = $dompdf->output();
        $filePath = 'uploads/documents/' . $fileName;

        file_put_contents($this->getProjectDir() . '/public/' . $filePath, $pdfContent);

        // Créer l'entité Document
        $document = new Document();

        // Vérifier que les données nécessaires sont présentes
        if (!$payment->getDueDate()) {
            throw new \InvalidArgumentException("La date d'échéance du paiement est manquante");
        }

        $document->setName('Avis d\'échéance - ' . $payment->getDueDate()->format('F Y'));
        $document->setType('Avis d\'échéance');
        $document->setFileName($fileName);
        $document->setOriginalFileName($fileName);
        $document->setMimeType('application/pdf');
        $document->setDescription('Avis d\'échéance pour le paiement #' . $payment->getId());
        $document->setTenant($tenant);
        $document->setProperty($lease->getProperty());
        $document->setLease($lease);
        $document->setDocumentDate($payment->getDueDate());
        $document->setOrganization($organization); // ✅ Assigner organization
        $document->setCompany($company); // ✅ Assigner company

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        // Créer automatiquement une écriture comptable pour l'avis d'échéance
        $this->createAccountingEntryForNotice($document, $payment);

        // Libérer la mémoire après génération
        $this->optimizeMemoryUsage();

        return $document;
    }

    /**
     * Génère automatiquement les quittances pour tous les paiements payés du mois
     */
    public function generateMonthlyReceipts(\DateTime $month): array
    {
        try {
            $startDate = new \DateTime($month->format('Y-m-01 00:00:00'));
            $endDate = new \DateTime($month->format('Y-m-t 23:59:59'));

            // Vérifier que l'EntityManager est ouvert avant de l'utiliser
            if (!$this->entityManager->isOpen()) {
                error_log('EntityManager fermé dans generateMonthlyReceipts - retour d\'un tableau vide');
                return [];
            }

            // Compter le nombre total de paiements pour le traitement par lots
            $totalCount = $this->entityManager->getRepository(Payment::class)
                ->createQueryBuilder('p')
                ->select('COUNT(p.id)')
                ->where('p.status = :status')
                ->andWhere('p.paidDate BETWEEN :startDate AND :endDate')
                ->setParameter('status', 'Payé')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate)
                ->getQuery()
                ->getSingleScalarResult();

            error_log("Génération de quittances pour {$totalCount} paiements");

        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'EntityManager is closed') !== false) {
                error_log('EntityManager fermé dans generateMonthlyReceipts - retour d\'un tableau vide');
                return [];
            }
            throw $e; // Re-lancer les autres exceptions
        }

        return $this->processPaymentsInBatches($startDate, $endDate, 'Payé', 'generateRentReceipt');
    }

    /**
     * Génère automatiquement les avis d'échéance pour les paiements à venir
     */
    public function generateUpcomingNotices(\DateTime $dueMonth): array
    {
        try {
            $startDate = new \DateTime($dueMonth->format('Y-m-01 00:00:00'));
            $endDate = new \DateTime($dueMonth->format('Y-m-t 23:59:59'));

            // Vérifier que l'EntityManager est ouvert avant de l'utiliser
            if (!$this->entityManager->isOpen()) {
                error_log('EntityManager fermé dans generateUpcomingNotices - retour d\'un tableau vide');
                return [];
            }

            // Compter le nombre total de paiements pour le traitement par lots
            $totalCount = $this->entityManager->getRepository(Payment::class)
                ->createQueryBuilder('p')
                ->select('COUNT(p.id)')
                ->where('p.status = :status')
                ->andWhere('p.dueDate BETWEEN :startDate AND :endDate')
                ->setParameter('status', 'En attente')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate)
                ->getQuery()
                ->getSingleScalarResult();

            error_log("Génération d'avis pour {$totalCount} paiements");

        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'EntityManager is closed') !== false) {
                error_log('EntityManager fermé dans generateUpcomingNotices - retour d\'un tableau vide');
                return [];
            }
            throw $e; // Re-lancer les autres exceptions
        }

        return $this->processPaymentsInBatches($startDate, $endDate, 'En attente', 'generatePaymentNotice');
    }

    /**
     * Récupère le chemin du projet
     */
    private function getProjectDir(): string
    {
        return $this->params->get('kernel.project_dir');
    }

    /**
     * Traite les paiements par lots pour éviter l'épuisement de mémoire
     */
    private function processPaymentsInBatches(\DateTime $startDate, \DateTime $endDate, string $status, string $methodName): array
    {
        $batchSize = 50; // Traiter 50 paiements à la fois
        $offset = 0;
        $allGenerated = [];
        $processedCount = 0;

        while (true) {
            // Vérifier la mémoire disponible
            $memoryUsage = memory_get_usage(true);
            $memoryLimit = ini_get('memory_limit');
            $memoryLimitBytes = $this->convertToBytes($memoryLimit);

            if ($memoryUsage > ($memoryLimitBytes * 0.8)) { // Si on utilise plus de 80% de la mémoire
                error_log("Utilisation mémoire élevée ({$memoryUsage} bytes), libération de la mémoire");
                gc_collect_cycles(); // Forcer le garbage collection
            }

            try {
                // Vérifier que l'EntityManager est toujours ouvert
                if (!$this->entityManager->isOpen()) {
                    error_log("EntityManager fermé - arrêt du traitement par lots");
                    break;
                }

                // Récupérer un lot de paiements
                $payments = $this->entityManager->getRepository(Payment::class)
                    ->createQueryBuilder('p')
                    ->where('p.status = :status')
                    ->andWhere('p.dueDate BETWEEN :startDate AND :endDate')
                    ->setParameter('status', $status)
                    ->setParameter('startDate', $startDate)
                    ->setParameter('endDate', $endDate)
                    ->setFirstResult($offset)
                    ->setMaxResults($batchSize)
                    ->getQuery()
                    ->getResult();

                // Si aucun paiement n'est trouvé, on a terminé
                if (empty($payments)) {
                    break;
                }

                error_log("Traitement du lot {$offset}-" . ($offset + count($payments)) . " (" . count($payments) . " paiements)");

                // Traiter chaque paiement du lot
                foreach ($payments as $payment) {
                    try {
                        // Valider les données du paiement
                        if (!$this->validatePaymentData($payment)) {
                            continue;
                        }

                        // Générer le document selon la méthode spécifiée
                        $document = $this->$methodName($payment);
                        $allGenerated[] = $document;
                        $processedCount++;

                        // Libérer la mémoire après chaque génération
                        unset($document);

                    } catch (\Exception $e) {
                        error_log(sprintf(
                            "Erreur génération document pour paiement #%d: %s",
                            $payment->getId(),
                            $e->getMessage()
                        ));

                        // Si l'EntityManager est fermé, arrêter le traitement
                        if (!$this->entityManager->isOpen()) {
                            error_log("EntityManager fermé - arrêt du traitement");
                            break 2;
                        }
                    }
                }

                // Clear l'EntityManager pour libérer la mémoire
                if ($this->entityManager->isOpen()) {
                    $this->entityManager->clear();
                }

                // Forcer le garbage collection
                gc_collect_cycles();

                $offset += $batchSize;

                // Pause pour éviter la surcharge
                usleep(100000); // 100ms

            } catch (\Exception $e) {
                error_log("Erreur lors du traitement par lots: " . $e->getMessage());
                break;
            }
        }

        error_log("Traitement terminé: {$processedCount} documents générés");
        return $allGenerated;
    }

    /**
     * Convertit une chaîne de limite de mémoire en bytes
     */
    private function convertToBytes(string $memoryLimit): int
    {
        $memoryLimit = trim($memoryLimit);
        $last = strtolower($memoryLimit[strlen($memoryLimit) - 1]);
        $value = (int) $memoryLimit;

        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * Valide les données d'un paiement avant génération de document
     */
    private function validatePaymentData(Payment $payment): bool
    {
        // Vérifier que le paiement a un bail
        if (!$payment->getLease()) {
            error_log("Paiement #{$payment->getId()}: bail manquant");
            return false;
        }

        $lease = $payment->getLease();

        // Vérifier que le bail a un locataire
        if (!$lease->getTenant()) {
            error_log("Paiement #{$payment->getId()}: locataire manquant");
            return false;
        }

        // Vérifier que la date d'échéance est présente
        if (!$payment->getDueDate()) {
            error_log("Paiement #{$payment->getId()}: date d'échéance manquante");
            return false;
        }

        // Vérifier que le locataire a un nom de famille
        $tenant = $lease->getTenant();
        if (!$tenant->getLastName()) {
            error_log("Paiement #{$payment->getId()}: nom de famille du locataire manquant");
            return false;
        }

        return true;
    }

    /**
     * Optimise l'utilisation de la mémoire
     */
    private function optimizeMemoryUsage(): void
    {
        // Vérifier l'utilisation mémoire
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = $this->convertToBytes($memoryLimit);

        // Si on utilise plus de 70% de la mémoire, forcer le garbage collection
        if ($memoryUsage > ($memoryLimitBytes * 0.7)) {
            error_log("Utilisation mémoire élevée ({$memoryUsage} bytes), libération de la mémoire");

            // Forcer le garbage collection
            gc_collect_cycles();

            // Clear l'EntityManager si possible
            if ($this->entityManager->isOpen()) {
                $this->entityManager->clear();
            }
        }
    }

    /**
     * Crée une écriture comptable pour une quittance générée
     */
    private function createAccountingEntryForReceipt(Document $document, Payment $payment): void
    {
        // Vérifier si une écriture comptable existe déjà pour ce paiement
        $existingEntry = $this->accountingRepository->findOneBy(['payment' => $payment]);
        if ($existingEntry) {
            // Mettre à jour la référence pour inclure le document
            $existingEntry->setReference('QUITTANCE-' . $document->getId());
            $this->entityManager->flush();
            return;
        }

        // Récupérer la configuration comptable pour les quittances
        $config = $this->accountingConfigService->getConfigurationForOperation('QUITTANCE_LOYER');

        // Si aucune configuration n'est trouvée, utiliser les valeurs par défaut
        $entryType = $config?->getEntryType() ?? 'CREDIT';
        $category = $config?->getCategory() ?? 'LOYER';
        $description = $config?->getDescription() ?? 'Quittance de loyer - ';
        $reference = ($config?->getReference() ?? 'QUITTANCE-') . $document->getId();

        // Créer une nouvelle écriture comptable
        $entry = new AccountingEntry();
        $entry->setEntryDate($payment->getPaidDate() ?? $payment->getDueDate());
        $entry->setDescription($description . $document->getName());
        $entry->setAmount($payment->getAmount());
        $entry->setType($entryType);
        $entry->setCategory($category);
        $entry->setReference($reference);
        $entry->setProperty($payment->getProperty());
        $entry->setOwner($payment->getProperty()?->getOwner());
        $entry->setPayment($payment);
        $entry->setOrganization($payment->getOrganization());
        $entry->setCompany($payment->getCompany());
        $entry->setNotes('Généré automatiquement lors de la création de la quittance');

        $this->entityManager->persist($entry);
        $this->entityManager->flush();
    }

    /**
     * Crée une écriture comptable pour un avis d'échéance généré
     */
    private function createAccountingEntryForNotice(Document $document, Payment $payment): void
    {
        // Vérifier si une écriture comptable existe déjà pour ce paiement
        $existingEntry = $this->accountingRepository->findOneBy(['payment' => $payment]);
        if ($existingEntry) {
            // Mettre à jour la référence pour inclure le document
            $existingEntry->setReference('AVIS-' . $document->getId());
            $this->entityManager->flush();
            return;
        }

        // Récupérer la configuration comptable pour les avis d'échéance
        $config = $this->accountingConfigService->getConfigurationForOperation('AVIS_ECHEANCE');

        // Si aucune configuration n'est trouvée, utiliser les valeurs par défaut
        $entryType = $config?->getEntryType() ?? 'CREDIT';
        $category = $config?->getCategory() ?? 'LOYER_ATTENDU';
        $description = $config?->getDescription() ?? 'Avis d\'échéance - ';
        $reference = ($config?->getReference() ?? 'AVIS-') . $document->getId();

        // Créer une nouvelle écriture comptable pour l'avis d'échéance
        $entry = new AccountingEntry();
        $entry->setEntryDate($payment->getDueDate());
        $entry->setDescription($description . $document->getName());
        $entry->setAmount($payment->getAmount());
        $entry->setType($entryType);
        $entry->setCategory($category);
        $entry->setReference($reference);
        $entry->setProperty($payment->getProperty());
        $entry->setOwner($payment->getProperty()?->getOwner());
        $entry->setPayment($payment);
        $entry->setOrganization($payment->getOrganization());
        $entry->setCompany($payment->getCompany());
        $entry->setNotes('Généré automatiquement lors de la création de l\'avis d\'échéance');

        $this->entityManager->persist($entry);
        $this->entityManager->flush();
    }
}

