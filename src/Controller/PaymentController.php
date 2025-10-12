<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Form\PaymentType;
use App\Repository\PaymentRepository;
use App\Repository\LeaseRepository;
use App\Service\PdfService;
use App\Service\ContractGenerationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/mes-paiements')]
class PaymentController extends AbstractController
{
    #[Route('/', name: 'app_payment_index', methods: ['GET'])]
    public function index(PaymentRepository $paymentRepository, Request $request): Response
    {
        $status = $request->query->get('status');
        $type = $request->query->get('type');
        $year = $request->query->get('year', date('Y'));
        $month = $request->query->get('month');

        $payments = $paymentRepository->findWithFilters($status, $type, $year, $month);

        // Statistiques
        $stats = [
            'total_pending' => $paymentRepository->count(['status' => 'En attente']),
            'total_paid' => $paymentRepository->count(['status' => 'Payé']),
            'total_overdue' => count($paymentRepository->findOverdue()),
            'monthly_income' => $paymentRepository->getMonthlyIncome(),
        ];

        return $this->render('payment/index.html.twig', [
            'payments' => $payments,
            'stats' => $stats,
            'current_status' => $status,
            'current_type' => $type,
            'current_year' => $year,
            'current_month' => $month,
        ]);
    }

    #[Route('/nouveau', name: 'app_payment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $payment = new Payment();
        $form = $this->createForm(PaymentType::class, $payment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($payment);
            $entityManager->flush();

            $this->addFlash('success', 'Le paiement a été créé avec succès.');

            return $this->redirectToRoute('app_payment_show', ['id' => $payment->getId()]);
        }

        return $this->render('payment/new.html.twig', [
            'payment' => $payment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_payment_show', methods: ['GET'])]
    public function show(Payment $payment): Response
    {
        return $this->render('payment/show.html.twig', [
            'payment' => $payment,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_payment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Payment $payment, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PaymentType::class, $payment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $payment->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Le paiement a été modifié avec succès.');

            return $this->redirectToRoute('app_payment_show', ['id' => $payment->getId()]);
        }

        return $this->render('payment/edit.html.twig', [
            'payment' => $payment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/marquer-paye', name: 'app_payment_mark_paid', methods: ['POST'])]
    public function markPaid(
        Request $request,
        Payment $payment,
        EntityManagerInterface $entityManager,
        ContractGenerationService $contractService
    ): Response {
        $paidDate = $request->request->get('paid_date') ?? $request->getPayload()->getString('paid_date');
        $paymentMethod = $request->request->get('payment_method') ?? $request->getPayload()->getString('payment_method');
        $reference = $request->request->get('reference') ?? $request->getPayload()->getString('reference');

        $payment->markAsPaid(
            $paidDate ? new \DateTime($paidDate) : new \DateTime(),
            $paymentMethod,
            $reference
        );

        $entityManager->flush();

        $this->addFlash('success', 'Le paiement a été marqué comme payé.');

        // 🎯 Générer automatiquement le contrat de bail si c'est la caution
        if ($payment->getType() === 'Dépôt de garantie' || $payment->getType() === 'Caution') {
            try {
                $contract = $contractService->generateContractAfterDeposit($payment);
                if ($contract) {
                    $this->addFlash('success', '📄 Le contrat de bail a été généré automatiquement et est disponible dans les documents !');
                } else {
                    $this->addFlash('info', 'Le contrat existe déjà ou la caution n\'est pas encore payée.');
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la génération du contrat : ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_payment_show', ['id' => $payment->getId()]);
    }

    #[Route('/generer-loyers/{year}/{month}', name: 'app_payment_generate_rents', methods: ['POST'])]
    public function generateRents(
        int $year,
        int $month,
        LeaseRepository $leaseRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $activeLeases = $leaseRepository->findBy(['status' => 'Actif']);
        $generated = 0;

        foreach ($activeLeases as $lease) {
            // Vérifier si le loyer n'existe pas déjà pour ce mois
            $dueDate = new \DateTime("{$year}-{$month}-" . ($lease->getRentDueDay() ?? 1));

            // ⚠️ VÉRIFICATION : Ne pas générer de loyer après la fin du bail
            if ($lease->getEndDate() && $dueDate > $lease->getEndDate()) {
                // La date d'échéance dépasse la fin du bail, on ne génère pas
                continue;
            }

            $existingPayment = $entityManager->getRepository(Payment::class)->findOneBy([
                'lease' => $lease,
                'dueDate' => $dueDate,
                'type' => 'Loyer'
            ]);

            if (!$existingPayment) {
                $payment = new Payment();
                $payment->setLease($lease);
                $payment->setDueDate($dueDate);
                $payment->setAmount($lease->getMonthlyRent());
                $payment->setType('Loyer');
                $payment->setStatus('En attente');

                $entityManager->persist($payment);
                $generated++;
            }
        }

        if ($generated > 0) {
            $entityManager->flush();
            $this->addFlash('success', "{$generated} loyers ont été générés pour {$month}/{$year}.");
        } else {
            $this->addFlash('info', "Aucun nouveau loyer à générer pour {$month}/{$year}.");
        }

        return $this->redirectToRoute('app_payment_index');
    }

    #[Route('/en-retard', name: 'app_payment_overdue', methods: ['GET'])]
    public function overdue(PaymentRepository $paymentRepository): Response
    {
        $overduePayments = $paymentRepository->findOverdue();

        return $this->render('payment/overdue.html.twig', [
            'overdue_payments' => $overduePayments,
        ]);
    }

    #[Route('/quittances/{year}/{month}', name: 'app_payment_receipts', methods: ['GET'])]
    public function receipts(int $year, int $month, PaymentRepository $paymentRepository): Response
    {
        $startDate = new \DateTime("{$year}-{$month}-01");
        $endDate = clone $startDate;
        $endDate->modify('last day of this month');

        $paidPayments = $paymentRepository->findPaidByPeriod($startDate, $endDate);

        return $this->render('payment/receipts.html.twig', [
            'payments' => $paidPayments,
            'year' => $year,
            'month' => $month,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_payment_delete', methods: ['POST'])]
    public function delete(Request $request, Payment $payment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$payment->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($payment);
            $entityManager->flush();

            $this->addFlash('success', 'Le paiement a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_payment_index');
    }

    #[Route('/moyens-paiement', name: 'app_payment_methods', methods: ['GET'])]
    public function paymentMethods(): Response
    {
        return $this->render('payment/methods.html.twig');
    }

    #[Route('/configurer-prelevement', name: 'app_payment_setup_direct_debit', methods: ['GET', 'POST'])]
    public function setupDirectDebit(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            // Ici vous pourriez traiter la configuration du prélèvement automatique
            $this->addFlash('success', 'Prélèvement automatique configuré avec succès.');
            return $this->redirectToRoute('app_payment_methods');
        }

        return $this->render('payment/setup_direct_debit.html.twig');
    }

    #[Route('/quittance/{id}', name: 'app_payment_receipt', methods: ['GET'])]
    public function receipt(Payment $payment): Response
    {
        if (!$payment->isPaid()) {
            throw $this->createNotFoundException('Quittance disponible uniquement pour les paiements effectués.');
        }

        return $this->render('payment/receipt.html.twig', [
            'payment' => $payment,
        ]);
    }

    #[Route('/{id}/recu-pdf', name: 'app_payment_receipt_pdf', methods: ['GET'])]
    public function downloadReceipt(Payment $payment, PdfService $pdfService): Response
    {
        if (!$payment->isPaid()) {
            throw $this->createNotFoundException('Reçu disponible uniquement pour les paiements effectués.');
        }

        $pdfService->generatePaymentReceipt($payment, true);
        return new Response(); // Le PDF est déjà envoyé par generatePaymentReceipt
    }

    #[Route('/quittance-mensuelle/{leaseId}/{month}', name: 'app_payment_monthly_quittance_pdf', methods: ['GET'])]
    public function downloadMonthlyQuittance(
        int $leaseId,
        string $month,
        LeaseRepository $leaseRepository,
        PaymentRepository $paymentRepository,
        PdfService $pdfService
    ): Response {
        $lease = $leaseRepository->find($leaseId);
        if (!$lease) {
            throw $this->createNotFoundException('Contrat introuvable.');
        }

        $monthDate = \DateTime::createFromFormat('Y-m', $month);
        if (!$monthDate) {
            throw $this->createNotFoundException('Format de mois invalide.');
        }
        $monthDate->modify('first day of this month');

        // Récupérer les paiements payés pour ce mois
        $startDate = clone $monthDate;
        $endDate = (clone $monthDate)->modify('last day of this month');

        $payments = $paymentRepository->createQueryBuilder('p')
            ->where('p.lease = :lease')
            ->andWhere('p.status = :status')
            ->andWhere('p.paidDate BETWEEN :start AND :end')
            ->setParameter('lease', $lease)
            ->setParameter('status', 'Payé')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->getQuery()
            ->getResult();

        if (empty($payments)) {
            throw $this->createNotFoundException('Aucun paiement trouvé pour ce mois.');
        }

        $pdfService->generateRentQuittance($payments, $lease, $monthDate, true);
        return new Response(); // Le PDF est déjà envoyé
    }
}
