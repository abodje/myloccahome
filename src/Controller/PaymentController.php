<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Form\PaymentType;
use App\Repository\PaymentRepository;
use App\Repository\RentalContractRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/payments')]
class PaymentController extends AbstractController
{
    #[Route('/', name: 'app_payment_index', methods: ['GET'])]
    public function index(Request $request, PaymentRepository $paymentRepository): Response
    {
        $status = $request->query->get('status');
        $period = $request->query->get('period');
        $contractId = $request->query->get('contract');

        if ($contractId) {
            $payments = $paymentRepository->findPaymentsByContract($contractId);
        } elseif ($period) {
            $payments = $paymentRepository->findPaymentsByPeriod($period);
        } elseif ($status) {
            $payments = $paymentRepository->findBy(['status' => $status], ['dueDate' => 'DESC']);
        } else {
            $payments = $paymentRepository->findBy([], ['dueDate' => 'DESC']);
        }

        return $this->render('payment/index.html.twig', [
            'payments' => $payments,
            'status' => $status,
            'period' => $period,
            'contract_id' => $contractId,
        ]);
    }

    #[Route('/new', name: 'app_payment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, RentalContractRepository $contractRepository): Response
    {
        $payment = new Payment();
        
        // Pré-remplir si contract_id est fourni
        $contractId = $request->query->get('contract_id');
        if ($contractId) {
            $contract = $contractRepository->find($contractId);
            if ($contract) {
                $payment->setRentalContract($contract);
                $payment->setAmount($contract->getTotalMonthlyAmount());
                
                // Suggérer la prochaine période
                $nextPeriod = new \DateTime('first day of next month');
                $payment->setPeriod($nextPeriod->format('Y-m'));
                
                $dueDate = new \DateTime($nextPeriod->format('Y-m-') . str_pad($contract->getRentDueDay(), 2, '0', STR_PAD_LEFT));
                $payment->setDueDate($dueDate);
            }
        }

        $form = $this->createForm(PaymentType::class, $payment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($payment);
            $entityManager->flush();

            $this->addFlash('success', 'Le paiement a été créé avec succès.');

            return $this->redirectToRoute('app_payment_index', [], Response::HTTP_SEE_OTHER);
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

    #[Route('/{id}/edit', name: 'app_payment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Payment $payment, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PaymentType::class, $payment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $payment->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Le paiement a été modifié avec succès.');

            return $this->redirectToRoute('app_payment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('payment/edit.html.twig', [
            'payment' => $payment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_payment_delete', methods: ['POST'])]
    public function delete(Request $request, Payment $payment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$payment->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($payment);
            $entityManager->flush();
            $this->addFlash('success', 'Le paiement a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_payment_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/mark-paid', name: 'app_payment_mark_paid', methods: ['POST'])]
    public function markPaid(Request $request, Payment $payment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('mark_paid'.$payment->getId(), $request->getPayload()->getString('_token'))) {
            $paymentDate = new \DateTime($request->request->get('payment_date', 'now'));
            $paymentMethod = $request->request->get('payment_method');
            $reference = $request->request->get('reference');
            
            $payment->markAsPaid($paymentDate, $paymentMethod, $reference);
            $entityManager->flush();

            $this->addFlash('success', 'Le paiement a été marqué comme payé.');
        }

        return $this->redirectToRoute('app_payment_show', ['id' => $payment->getId()]);
    }

    #[Route('/{id}/mark-overdue', name: 'app_payment_mark_overdue', methods: ['POST'])]
    public function markOverdue(Request $request, Payment $payment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('mark_overdue'.$payment->getId(), $request->getPayload()->getString('_token'))) {
            $payment->setStatus('overdue');
            
            // Ajouter des pénalités si spécifiées
            $lateFee = $request->request->get('late_fee');
            if ($lateFee && is_numeric($lateFee)) {
                $payment->setLateFee($lateFee);
            }
            
            $payment->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('warning', 'Le paiement a été marqué comme en retard.');
        }

        return $this->redirectToRoute('app_payment_show', ['id' => $payment->getId()]);
    }

    #[Route('/overdue', name: 'app_payment_overdue', methods: ['GET'])]
    public function overdue(PaymentRepository $paymentRepository): Response
    {
        $payments = $paymentRepository->findOverduePayments();

        return $this->render('payment/overdue.html.twig', [
            'payments' => $payments,
        ]);
    }

    #[Route('/current-month', name: 'app_payment_current_month', methods: ['GET'])]
    public function currentMonth(PaymentRepository $paymentRepository): Response
    {
        $currentPeriod = date('Y-m');
        $payments = $paymentRepository->findPaymentsByPeriod($currentPeriod);

        return $this->render('payment/current_month.html.twig', [
            'payments' => $payments,
            'period' => $currentPeriod,
        ]);
    }

    #[Route('/reports', name: 'app_payment_reports', methods: ['GET'])]
    public function reports(Request $request, PaymentRepository $paymentRepository): Response
    {
        $year = $request->query->get('year', date('Y'));
        $month = $request->query->get('month');

        if ($month) {
            $revenue = $paymentRepository->getMonthlyRevenue((int)$year, (int)$month);
            $period = sprintf('%04d-%02d', $year, $month);
            $payments = $paymentRepository->findPaymentsByPeriod($period);
            
            return $this->render('payment/monthly_report.html.twig', [
                'payments' => $payments,
                'revenue' => $revenue,
                'year' => $year,
                'month' => $month,
                'period' => $period,
            ]);
        } else {
            $yearlyRevenues = $paymentRepository->getYearlyRevenue((int)$year);
            
            return $this->render('payment/yearly_report.html.twig', [
                'yearlyRevenues' => $yearlyRevenues,
                'year' => $year,
            ]);
        }
    }

    #[Route('/bulk-generate', name: 'app_payment_bulk_generate', methods: ['GET', 'POST'])]
    public function bulkGenerate(Request $request, EntityManagerInterface $entityManager, RentalContractRepository $contractRepository): Response
    {
        if ($request->isMethod('POST')) {
            $period = $request->request->get('period');
            $contracts = $contractRepository->findActive();
            $generated = 0;

            foreach ($contracts as $contract) {
                // Vérifier si un paiement existe déjà pour cette période
                $existingPayment = $entityManager->getRepository(Payment::class)->findOneBy([
                    'rentalContract' => $contract,
                    'period' => $period
                ]);

                if (!$existingPayment) {
                    $payment = new Payment();
                    $payment->setRentalContract($contract);
                    $payment->setAmount($contract->getTotalMonthlyAmount());
                    $payment->setPeriod($period);

                    $dueDate = new \DateTime($period . '-' . str_pad($contract->getRentDueDay(), 2, '0', STR_PAD_LEFT));
                    $payment->setDueDate($dueDate);

                    $entityManager->persist($payment);
                    $generated++;
                }
            }

            $entityManager->flush();

            $this->addFlash('success', "{$generated} paiements ont été générés pour la période {$period}.");
            return $this->redirectToRoute('app_payment_index');
        }

        return $this->render('payment/bulk_generate.html.twig');
    }
}