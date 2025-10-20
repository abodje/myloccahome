<?php

namespace App\Command;

use App\Entity\Payment;
use App\Entity\Document;
use App\Repository\PaymentRepository;
use App\Repository\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:diagnose-rent-receipt-issues',
    description: 'Diagnostique les problèmes avec la génération des quittances de loyer.',
)]
class DiagnoseRentReceiptIssuesCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private PaymentRepository $paymentRepository;
    private DocumentRepository $documentRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        PaymentRepository $paymentRepository,
        DocumentRepository $documentRepository
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->paymentRepository = $paymentRepository;
        $this->documentRepository = $documentRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Diagnostic des problèmes de quittances de loyer');

        try {
            // 1. Analyser les paiements payés du mois dernier
            $io->section('1. Analyse des paiements payés');

            $lastMonth = new \DateTime('first day of last month');
            $startDate = new \DateTime($lastMonth->format('Y-m-01 00:00:00'));
            $endDate = new \DateTime($lastMonth->format('Y-m-t 23:59:59'));

            $payments = $this->paymentRepository->createQueryBuilder('p')
                ->join('p.lease', 'l')
                ->join('l.tenant', 't')
                ->where('p.status = :status')
                ->andWhere('p.paidDate BETWEEN :startDate AND :endDate')
                ->setParameter('status', 'Payé')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate)
                ->getQuery()
                ->getResult();

            $io->writeln(sprintf('Période analysée: %s', $lastMonth->format('F Y')));
            $io->writeln(sprintf('Nombre de paiements payés trouvés: %d', count($payments)));

            if (empty($payments)) {
                $io->warning('⚠️ Aucun paiement payé trouvé pour le mois dernier.');
                $io->writeln('C\'est pourquoi aucun email de quittance n\'a été envoyé.');
                return Command::SUCCESS;
            }

            // 2. Analyser chaque paiement
            $io->section('2. Analyse détaillée des paiements');

            $issues = [
                'no_email' => 0,
                'no_receipt' => 0,
                'receipt_found' => 0,
                'valid' => 0
            ];

            foreach ($payments as $payment) {
                $tenant = $payment->getLease()->getTenant();
                $io->writeln(sprintf("\nPaiement #%d:", $payment->getId()));
                $io->writeln(sprintf("  - Locataire: %s %s", $tenant->getFirstName(), $tenant->getLastName()));
                $io->writeln(sprintf("  - Email: %s", $tenant->getEmail() ?: 'NON DÉFINI'));
                $io->writeln(sprintf("  - Date de paiement: %s", $payment->getPaidDate()->format('Y-m-d')));
                $io->writeln(sprintf("  - Montant: %s FCFA", number_format($payment->getAmount())));

                // Vérifier l'email
                if (!$tenant->getEmail()) {
                    $io->writeln('  ❌ Problème: Email non défini');
                    $issues['no_email']++;
                    continue;
                }

                // Chercher la quittance
                $fileName = sprintf(
                    'quittance_%s_%s.pdf',
                    $tenant->getLastName(),
                    $payment->getDueDate()->format('Y_m')
                );

                $receipt = $this->documentRepository->findOneBy([
                    'type' => 'Quittance de loyer',
                    'fileName' => $fileName
                ]);

                if (!$receipt) {
                    $io->writeln(sprintf('  ❌ Problème: Quittance non trouvée (%s)', $fileName));
                    $issues['no_receipt']++;
                } else {
                    $io->writeln(sprintf('  ✅ Quittance trouvée: %s', $receipt->getFileName()));
                    $issues['receipt_found']++;

                    // Vérifier si le fichier existe physiquement
                    $filePath = $this->getDocumentFilePath($receipt);
                    if ($filePath && file_exists($filePath)) {
                        $io->writeln(sprintf('  ✅ Fichier physique trouvé: %s', $filePath));
                        $issues['valid']++;
                    } else {
                        $io->writeln('  ❌ Problème: Fichier physique non trouvé');
                    }
                }
            }

            // 3. Résumé des problèmes
            $io->section('3. Résumé des problèmes');
            $io->table(
                ['Type de problème', 'Nombre', 'Pourcentage'],
                [
                    ['Email non défini', $issues['no_email'], round($issues['no_email'] / count($payments) * 100, 1) . '%'],
                    ['Quittance non trouvée', $issues['no_receipt'], round($issues['no_receipt'] / count($payments) * 100, 1) . '%'],
                    ['Quittance trouvée', $issues['receipt_found'], round($issues['receipt_found'] / count($payments) * 100, 1) . '%'],
                    ['Valide (email + quittance + fichier)', $issues['valid'], round($issues['valid'] / count($payments) * 100, 1) . '%'],
                ]
            );

            // 4. Recommandations
            $io->section('4. Recommandations');

            if ($issues['no_email'] > 0) {
                $io->writeln('• Ajouter des adresses email pour les locataires sans email');
            }

            if ($issues['no_receipt'] > 0) {
                $io->writeln('• Générer les quittances manquantes avec la commande app:generate-rent-documents');
            }

            if ($issues['valid'] === 0 && count($payments) > 0) {
                $io->warning('⚠️ Aucun paiement valide pour l\'envoi de quittances.');
                $io->writeln('Il faut d\'abord générer les quittances et définir les emails des locataires.');
            } elseif ($issues['valid'] > 0) {
                $io->success(sprintf('✅ %d paiements sont prêts pour l\'envoi de quittances.', $issues['valid']));
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('❌ Erreur lors du diagnostic : ' . $e->getMessage());
            $io->writeln('Trace : ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    private function getDocumentFilePath(Document $document): ?string
    {
        // Simuler la logique de récupération du chemin de fichier
        $documentsDirectory = 'public/uploads/documents';
        $fileName = $document->getFileName();

        if (!$fileName) {
            return null;
        }

        $filePath = $documentsDirectory . '/' . $fileName;

        return file_exists($filePath) ? $filePath : null;
    }
}
