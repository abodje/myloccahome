<?php

namespace App\Service;

use App\Entity\Payment;
use App\Entity\Document;
use App\Entity\Lease;
use App\Repository\DocumentRepository;
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
        private SettingsService $settingsService,
        private ParameterBagInterface $params
    ) {
    }

    /**
     * Génère une quittance de loyer pour un paiement
     */
    public function generateRentReceipt(Payment $payment): Document
    {
        // Vérifier si une quittance existe déjà pour ce paiement (via le nom du fichier unique)
        $fileName = sprintf(
            'quittance_%s_%s.pdf',
            $payment->getLease()->getTenant()->getLastName(),
            $payment->getDueDate()->format('Y_m')
        );

        $existingReceipt = $this->documentRepository->findOneBy([
            'fileName' => $fileName,
            'type' => 'Quittance de loyer'
        ]);

        if ($existingReceipt) {
            return $existingReceipt;
        }

        // Récupérer la société émettrice
        $company = $payment->getCompany() ?: $payment->getLease()->getCompany() ?: $payment->getLease()->getProperty()->getCompany();
        $organization = $payment->getOrganization() ?: $payment->getLease()->getOrganization();

        // Générer le HTML de la quittance
        $html = $this->twig->render('pdf/rent_receipt.html.twig', [
            'payment' => $payment,
            'lease' => $payment->getLease(),
            'tenant' => $payment->getLease()->getTenant(),
            'property' => $payment->getLease()->getProperty(),
            'company' => $company, // ✅ Informations de la société
            'organization' => $organization,
            'settings' => [
                'company_name' => $company ? $company->getLegalName() ?: $company->getName() : $this->settingsService->get('company_name', 'MYLOCCA'),
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
        $document->setTenant($payment->getLease()->getTenant());
        $document->setProperty($payment->getLease()->getProperty());
        $document->setLease($payment->getLease());
        $document->setDocumentDate($payment->getPaidDate());
        $document->setOrganization($organization); // ✅ Assigner organization
        $document->setCompany($company); // ✅ Assigner company

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        return $document;
    }

    /**
     * Génère un avis d'échéance pour un paiement à venir
     */
    public function generatePaymentNotice(Payment $payment): Document
    {
        // Vérifier si un avis existe déjà pour ce paiement (via le nom du fichier unique)
        $fileName = sprintf(
            'avis_echeance_%s_%s.pdf',
            $payment->getLease()->getTenant()->getLastName(),
            $payment->getDueDate()->format('Y_m')
        );

        $existingNotice = $this->documentRepository->findOneBy([
            'fileName' => $fileName,
            'type' => 'Avis d\'échéance'
        ]);

        if ($existingNotice) {
            return $existingNotice;
        }

        // Récupérer la société émettrice
        $company = $payment->getCompany() ?: $payment->getLease()->getCompany() ?: $payment->getLease()->getProperty()->getCompany();
        $organization = $payment->getOrganization() ?: $payment->getLease()->getOrganization();

        // Générer le HTML de l'avis d'échéance
        $html = $this->twig->render('pdf/payment_notice.html.twig', [
            'payment' => $payment,
            'lease' => $payment->getLease(),
            'tenant' => $payment->getLease()->getTenant(),
            'property' => $payment->getLease()->getProperty(),
            'company' => $company, // ✅ Informations de la société
            'organization' => $organization,
            'settings' => [
                'company_name' => $company ? $company->getLegalName() ?: $company->getName() : $this->settingsService->get('company_name', 'MYLOCCA'),
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
        $document->setTenant($payment->getLease()->getTenant());
        $document->setProperty($payment->getLease()->getProperty());
        $document->setLease($payment->getLease());
        $document->setDocumentDate($payment->getDueDate());
        $document->setOrganization($organization); // ✅ Assigner organization
        $document->setCompany($company); // ✅ Assigner company

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        return $document;
    }

    /**
     * Génère automatiquement les quittances pour tous les paiements payés du mois
     */
    public function generateMonthlyReceipts(\DateTime $month): array
    {
        $startDate = new \DateTime($month->format('Y-m-01 00:00:00'));
        $endDate = new \DateTime($month->format('Y-m-t 23:59:59'));

        $payments = $this->entityManager->getRepository(Payment::class)
            ->createQueryBuilder('p')
            ->where('p.status = :status')
            ->andWhere('p.paidDate BETWEEN :startDate AND :endDate')
            ->setParameter('status', 'Payé')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();

        $generatedReceipts = [];
        foreach ($payments as $payment) {
            try {
                $receipt = $this->generateRentReceipt($payment);
                $generatedReceipts[] = $receipt;
            } catch (\Exception $e) {
                // Log l'erreur mais continue
                error_log("Erreur génération quittance pour paiement #{$payment->getId()}: " . $e->getMessage());
            }
        }

        return $generatedReceipts;
    }

    /**
     * Génère automatiquement les avis d'échéance pour les paiements à venir
     */
    public function generateUpcomingNotices(\DateTime $dueMonth): array
    {
        $startDate = new \DateTime($dueMonth->format('Y-m-01 00:00:00'));
        $endDate = new \DateTime($dueMonth->format('Y-m-t 23:59:59'));

        $payments = $this->entityManager->getRepository(Payment::class)
            ->createQueryBuilder('p')
            ->where('p.status = :status')
            ->andWhere('p.dueDate BETWEEN :startDate AND :endDate')
            ->setParameter('status', 'En attente')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();

        $generatedNotices = [];
        foreach ($payments as $payment) {
            try {
                $notice = $this->generatePaymentNotice($payment);
                $generatedNotices[] = $notice;
            } catch (\Exception $e) {
                // Log l'erreur mais continue
                error_log("Erreur génération avis pour paiement #{$payment->getId()}: " . $e->getMessage());
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
}

