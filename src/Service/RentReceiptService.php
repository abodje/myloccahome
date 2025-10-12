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
        // Vérifier si une quittance existe déjà pour ce paiement
        $existingReceipt = $this->documentRepository->findOneBy([
            'payment' => $payment,
            'category' => 'Quittance de loyer'
        ]);

        if ($existingReceipt) {
            return $existingReceipt;
        }

        // Générer le HTML de la quittance
        $html = $this->twig->render('pdf/rent_receipt.html.twig', [
            'payment' => $payment,
            'lease' => $payment->getLease(),
            'tenant' => $payment->getLease()->getTenant(),
            'property' => $payment->getLease()->getProperty(),
            'settings' => [
                'company_name' => $this->settingsService->get('company_name', 'MYLOCCA'),
                'company_address' => $this->settingsService->get('company_address', ''),
                'company_phone' => $this->settingsService->get('company_phone', ''),
                'company_email' => $this->settingsService->get('company_email', ''),
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
        $fileName = sprintf(
            'quittance_%s_%s.pdf',
            $payment->getLease()->getTenant()->getLastName(),
            $payment->getDueDate()->format('Y_m')
        );
        $filePath = 'uploads/documents/' . $fileName;

        // Créer le dossier si nécessaire
        $uploadDir = $this->getProjectDir() . '/public/uploads/documents';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        file_put_contents($this->getProjectDir() . '/public/' . $filePath, $pdfContent);

        // Créer l'entité Document
        $document = new Document();
        $document->setTitle('Quittance de loyer - ' . $payment->getDueDate()->format('F Y'));
        $document->setCategory('Quittance de loyer');
        $document->setFilePath($filePath);
        $document->setUploadDate(new \DateTime());
        $document->setTenant($payment->getLease()->getTenant());
        $document->setProperty($payment->getLease()->getProperty());
        $document->setLease($payment->getLease());
        $document->setPayment($payment);

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        return $document;
    }

    /**
     * Génère un avis d'échéance pour un paiement à venir
     */
    public function generatePaymentNotice(Payment $payment): Document
    {
        // Vérifier si un avis existe déjà pour ce paiement
        $existingNotice = $this->documentRepository->findOneBy([
            'payment' => $payment,
            'category' => 'Avis d\'échéance'
        ]);

        if ($existingNotice) {
            return $existingNotice;
        }

        // Générer le HTML de l'avis d'échéance
        $html = $this->twig->render('pdf/payment_notice.html.twig', [
            'payment' => $payment,
            'lease' => $payment->getLease(),
            'tenant' => $payment->getLease()->getTenant(),
            'property' => $payment->getLease()->getProperty(),
            'settings' => [
                'company_name' => $this->settingsService->get('company_name', 'MYLOCCA'),
                'company_address' => $this->settingsService->get('company_address', ''),
                'company_phone' => $this->settingsService->get('company_phone', ''),
                'company_email' => $this->settingsService->get('company_email', ''),
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
        $fileName = sprintf(
            'avis_echeance_%s_%s.pdf',
            $payment->getLease()->getTenant()->getLastName(),
            $payment->getDueDate()->format('Y_m')
        );
        $filePath = 'uploads/documents/' . $fileName;

        file_put_contents($this->getProjectDir() . '/public/' . $filePath, $pdfContent);

        // Créer l'entité Document
        $document = new Document();
        $document->setTitle('Avis d\'échéance - ' . $payment->getDueDate()->format('F Y'));
        $document->setCategory('Avis d\'échéance');
        $document->setFilePath($filePath);
        $document->setUploadDate(new \DateTime());
        $document->setTenant($payment->getLease()->getTenant());
        $document->setProperty($payment->getLease()->getProperty());
        $document->setLease($payment->getLease());
        $document->setPayment($payment);

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

