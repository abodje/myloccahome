<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Repository\PaymentRepository;
use App\Repository\LeaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/payments')]
class PaymentController extends AbstractController
{
    #[Route('/', name: 'app_payment_index', methods: ['GET'])]
    public function index(PaymentRepository $paymentRepository, Request $request): Response
    {
        $status = $request->query->get('status', '');
        $month = $request->query->get('month', date('Y-m'));

        if ($status === 'overdue') {
            $payments = $paymentRepository->findOverdue();
        } elseif ($status === 'due_this_month') {
            $payments = $paymentRepository->findDueThisMonth();
        } elseif ($month) {
            $startDate = new \DateTime($month . '-01');
            $endDate = new \DateTime($month . '-01');
            $endDate->modify('last day of this month');
            $payments = $paymentRepository->findByDateRange($startDate, $endDate);
        } else {
            $payments = $paymentRepository->findAllWithRelations();
        }

        return $this->render('payment/index.html.twig', [
            'payments' => $payments,
            'status' => $status,
            'month' => $month,
        ]);
    }

    #[Route('/new', name: 'app_payment_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        LeaseRepository $leaseRepository
    ): Response {
        $payment = new Payment();
        
        if ($request->isMethod('POST')) {
            $leaseId = $request->get('lease_id');
            $lease = $leaseRepository->find($leaseId);
            
            if (!$lease) {
                $this->addFlash('error', 'Contrat non trouvé.');
                return $this->render('payment/new.html.twig', [
                    'payment' => $payment,
                    'leases' => $leaseRepository->findActive(),
                ]);
            }

            $payment->setLease($lease);
            $payment->setDueDate(new \DateTime($request->get('due_date')));
            $payment->setAmount($request->get('amount'));
            $payment->setType($request->get('type'));
            $payment->setStatus($request->get('status', 'En attente'));
            
            if ($request->get('paid_date')) {
                $payment->setPaidDate(new \DateTime($request->get('paid_date')));
            }
            
            $payment->setPaymentMethod($request->get('payment_method'));
            $payment->setReference($request->get('reference'));
            $payment->setNotes($request->get('notes'));

            $entityManager->persist($payment);
            $entityManager->flush();

            $this->addFlash('success', 'Paiement créé avec succès !');
            return $this->redirectToRoute('app_payment_index');
        }

        return $this->render('payment/new.html.twig', [
            'payment' => $payment,
            'leases' => $leaseRepository->findActive(),
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
        if ($request->isMethod('POST')) {
            $payment->setDueDate(new \DateTime($request->get('due_date')));
            $payment->setAmount($request->get('amount'));
            $payment->setType($request->get('type'));
            $payment->setStatus($request->get('status'));
            
            if ($request->get('paid_date')) {
                $payment->setPaidDate(new \DateTime($request->get('paid_date')));
            } else {
                $payment->setPaidDate(null);
            }
            
            $payment->setPaymentMethod($request->get('payment_method'));
            $payment->setReference($request->get('reference'));
            $payment->setNotes($request->get('notes'));
            $payment->setUpdatedAt(new \DateTime());

            $entityManager->flush();

            $this->addFlash('success', 'Paiement modifié avec succès !');
            return $this->redirectToRoute('app_payment_show', ['id' => $payment->getId()]);
        }

        return $this->render('payment/edit.html.twig', [
            'payment' => $payment,
        ]);
    }

    #[Route('/{id}/mark-paid', name: 'app_payment_mark_paid', methods: ['POST'])]
    public function markAsPaid(Request $request, Payment $payment, EntityManagerInterface $entityManager): Response
    {
        $paidDate = $request->get('paid_date') ? new \DateTime($request->get('paid_date')) : new \DateTime();
        $paymentMethod = $request->get('payment_method');
        $reference = $request->get('reference');

        $payment->markAsPaid($paidDate, $paymentMethod, $reference);

        $entityManager->flush();

        $this->addFlash('success', 'Paiement marqué comme payé !');
        return $this->redirectToRoute('app_payment_show', ['id' => $payment->getId()]);
    }

    #[Route('/generate-monthly/{leaseId}', name: 'app_payment_generate_monthly', methods: ['POST'])]
    public function generateMonthlyPayments(
        int $leaseId, 
        Request $request,
        LeaseRepository $leaseRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $lease = $leaseRepository->find($leaseId);
        
        if (!$lease || !$lease->isActive()) {
            $this->addFlash('error', 'Contrat non trouvé ou inactif.');
            return $this->redirectToRoute('app_lease_index');
        }

        $months = (int)$request->get('months', 1);
        $startDate = new \DateTime();
        
        for ($i = 0; $i < $months; $i++) {
            $dueDate = clone $startDate;
            $dueDate->modify('+' . $i . ' months');
            $dueDate->setDate($dueDate->format('Y'), $dueDate->format('n'), $lease->getRentDueDay());

            // Vérifier si un paiement existe déjà pour cette période
            $existingPayment = $entityManager->getRepository(Payment::class)
                ->findOneBy([
                    'lease' => $lease,
                    'dueDate' => $dueDate,
                    'type' => 'Loyer'
                ]);

            if (!$existingPayment) {
                $payment = new Payment();
                $payment->setLease($lease);
                $payment->setDueDate($dueDate);
                $payment->setAmount($lease->getTotalMonthlyAmount());
                $payment->setType('Loyer');
                $payment->setStatus('En attente');

                $entityManager->persist($payment);
            }
        }

        $entityManager->flush();

        $this->addFlash('success', $months . ' paiement(s) mensuel(s) généré(s) avec succès !');
        return $this->redirectToRoute('app_lease_show', ['id' => $leaseId]);
    }
}