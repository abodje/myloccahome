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
        private ParameterBagInterface $params
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

            $payments = $this->entityManager->getRepository(Payment::class)
                ->createQueryBuilder('p')
                ->where('p.status = :status')
                ->andWhere('p.paidDate BETWEEN :startDate AND :endDate')
                ->setParameter('status', 'Payé')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate)
                ->getQuery()
                ->getResult();
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'EntityManager is closed') !== false) {
                error_log('EntityManager fermé dans generateMonthlyReceipts - retour d\'un tableau vide');
                return [];
            }
            throw $e; // Re-lancer les autres exceptions
        }

        $generatedReceipts = [];
        foreach ($payments as $payment) {
            try {
                // Vérifier que toutes les entités nécessaires sont présentes
                if (!$payment->getLease() || !$payment->getLease()->getTenant()) {
                    error_log("Paiement #{$payment->getId()}: bail ou locataire manquant");
                    continue;
                }

                $receipt = $this->generateRentReceipt($payment);
                $generatedReceipts[] = $receipt;

                // Clear l'EntityManager pour libérer la mémoire (seulement si ouvert)
                if ($this->entityManager->isOpen()) {
                    $this->entityManager->clear(Document::class);
                }

            } catch (\Exception $e) {
                // Log l'erreur avec plus de détails
                error_log(sprintf(
                    "Erreur génération quittance pour paiement #%d: %s\nStack trace: %s",
                    $payment->getId(),
                    $e->getMessage(),
                    $e->getTraceAsString()
                ));

                // Si l'EntityManager est fermé, on continue avec le suivant
                if (!$this->entityManager->isOpen()) {
                    error_log("EntityManager fermé - impossible de continuer la génération");
                    break;
                }
            }
        }

        return $generatedReceipts;
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

            $payments = $this->entityManager->getRepository(Payment::class)
                ->createQueryBuilder('p')
                ->where('p.status = :status')
                ->andWhere('p.dueDate BETWEEN :startDate AND :endDate')
                ->setParameter('status', 'En attente')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate)
                ->getQuery()
                ->getResult();
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'EntityManager is closed') !== false) {
                error_log('EntityManager fermé dans generateUpcomingNotices - retour d\'un tableau vide');
                return [];
            }
            throw $e; // Re-lancer les autres exceptions
        }

        $generatedNotices = [];
        foreach ($payments as $payment) {
            try {
                // Vérifier que toutes les entités nécessaires sont présentes
                if (!$payment->getLease() || !$payment->getLease()->getTenant()) {
                    error_log("Paiement #{$payment->getId()}: bail ou locataire manquant");
                    continue;
                }

                $notice = $this->generatePaymentNotice($payment);
                $generatedNotices[] = $notice;

                // Clear l'EntityManager pour libérer la mémoire (seulement si ouvert)
                if ($this->entityManager->isOpen()) {
                    $this->entityManager->clear(Document::class);
                }

            } catch (\Exception $e) {
                // Log l'erreur avec plus de détails
                error_log(sprintf(
                    "Erreur génération avis pour paiement #%d: %s\nStack trace: %s",
                    $payment->getId(),
                    $e->getMessage(),
                    $e->getTraceAsString()
                ));

                // Si l'EntityManager est fermé, on continue avec le suivant
                if (!$this->entityManager->isOpen()) {
                    error_log("EntityManager fermé - impossible de continuer la génération");
                    break;
                }
            }
        }

        return $generatedNotices;
    }

    /**
     * Récupère le chemin du projet
     */
    private function getProjectDir(): string
    {
        return $this->params->get('kernel.project_dir');
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

        // Créer une nouvelle écriture comptable
        $entry = new AccountingEntry();
        $entry->setEntryDate($payment->getPaidDate() ?? $payment->getDueDate());
        $entry->setDescription('Quittance de loyer - ' . $document->getName());
        $entry->setAmount($payment->getAmount());
        $entry->setType('CREDIT'); // Les quittances sont des crédits (revenus)
        $entry->setCategory('LOYER');
        $entry->setReference('QUITTANCE-' . $document->getId());
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

        // Créer une nouvelle écriture comptable pour l'avis d'échéance
        $entry = new AccountingEntry();
        $entry->setEntryDate($payment->getDueDate());
        $entry->setDescription('Avis d\'échéance - ' . $document->getName());
        $entry->setAmount($payment->getAmount());
        $entry->setType('CREDIT'); // Les avis d'échéance sont des crédits (revenus attendus)
        $entry->setCategory('LOYER_ATTENDU');
        $entry->setReference('AVIS-' . $document->getId());
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

