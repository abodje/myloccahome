<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Repository\PaymentRepository;
use App\Repository\RentalContractRepository;
use App\Repository\TenantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/payment')]
class PaymentController extends AbstractController
{
    #[Route('/', name: 'app_payment_index', methods: ['GET'])]
    public function index(PaymentRepository $paymentRepository, Request $request): Response
    {
        $filter = $request->query->get('filter');
        $tenant = $request->query->get('tenant');
        $contract = $request->query->get('contract');

        if ($filter === 'overdue') {
            $payments = $paymentRepository->findOverduePayments();
        } elseif ($filter === 'due_soon') {
            $payments = $paymentRepository->findPaymentsDueSoon(7);
        } elseif ($tenant) {
            $payments = $paymentRepository->findByTenant($tenant);
        } elseif ($contract) {
            $payments = $paymentRepository->findByContract($contract);
        } else {
            $payments = $paymentRepository->findBy([], ['dueDate' => 'DESC']);
        }

        return $this->render('payment/index.html.twig', [
            'payments' => $payments,
            'filter' => $filter,
            'tenant' => $tenant,
            'contract' => $contract,
        ]);
    }

    #[Route('/new', name: 'app_payment_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        RentalContractRepository $contractRepository,
        TenantRepository $tenantRepository
    ): Response {
        $payment = new Payment();
        $contracts = $contractRepository->findActiveContracts();
        $tenants = $tenantRepository->findAll();

        // Pré-sélection si passé en paramètre
        $preselectedContract = $request->query->get('contract');
        $preselectedTenant = $request->query->get('tenant');

        if ($request->isMethod('POST')) {
            $contract = $contractRepository->find($request->request->get('contract_id'));
            $tenant = $tenantRepository->find($request->request->get('tenant_id'));

            if (!$contract || !$tenant) {
                $this->addFlash('error', 'Contrat ou locataire non trouvé.');
                return $this->render('payment/new.html.twig', [
                    'payment' => $payment,
                    'contracts' => $contracts,
                    'tenants' => $tenants,
                    'preselectedContract' => $preselectedContract,
                    'preselectedTenant' => $preselectedTenant,
                ]);
            }

            $payment->setRentalContract($contract);
            $payment->setTenant($tenant);
            $payment->setAmount($request->request->get('amount'));
            $payment->setDueDate(new \DateTime($request->request->get('due_date')));
            $payment->setType($request->request->get('type'));
            $payment->setStatus($request->request->get('status'));
            
            if ($request->request->get('paid_date')) {
                $payment->setPaidDate(new \DateTime($request->request->get('paid_date')));
            }
            
            $payment->setPaymentMethod($request->request->get('payment_method'));
            $payment->setReference($request->request->get('reference'));
            $payment->setNotes($request->request->get('notes'));

            try {
                $entityManager->persist($payment);
                $entityManager->flush();

                $this->addFlash('success', 'Paiement enregistré avec succès !');
                return $this->redirectToRoute('app_payment_show', ['id' => $payment->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'enregistrement du paiement.');
            }
        }

        return $this->render('payment/new.html.twig', [
            'payment' => $payment,
            'contracts' => $contracts,
            'tenants' => $tenants,
            'preselectedContract' => $preselectedContract,
            'preselectedTenant' => $preselectedTenant,
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
            $payment->setAmount($request->request->get('amount'));
            $payment->setDueDate(new \DateTime($request->request->get('due_date')));
            $payment->setType($request->request->get('type'));
            $payment->setStatus($request->request->get('status'));
            
            if ($request->request->get('paid_date')) {
                $payment->setPaidDate(new \DateTime($request->request->get('paid_date')));
            } else {
                $payment->setPaidDate(null);
            }
            
            $payment->setPaymentMethod($request->request->get('payment_method'));
            $payment->setReference($request->request->get('reference'));
            $payment->setNotes($request->request->get('notes'));
            $payment->setUpdatedAt(new \DateTime());

            try {
                $entityManager->flush();

                $this->addFlash('success', 'Paiement modifié avec succès !');
                return $this->redirectToRoute('app_payment_show', ['id' => $payment->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification du paiement.');
            }
        }

        return $this->render('payment/edit.html.twig', [
            'payment' => $payment,
        ]);
    }

    #[Route('/{id}', name: 'app_payment_delete', methods: ['POST'])]
    public function delete(Request $request, Payment $payment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$payment->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($payment);
                $entityManager->flush();
                $this->addFlash('success', 'Paiement supprimé avec succès !');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression du paiement.');
            }
        }

        return $this->redirectToRoute('app_payment_index');
    }

    #[Route('/{id}/mark-paid', name: 'app_payment_mark_paid', methods: ['POST'])]
    public function markAsPaid(Request $request, Payment $payment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('mark_paid'.$payment->getId(), $request->request->get('_token'))) {
            $paidDate = $request->request->get('paid_date') ? new \DateTime($request->request->get('paid_date')) : new \DateTime();
            $paymentMethod = $request->request->get('payment_method');
            $reference = $request->request->get('reference');

            $payment->markAsPaid($paidDate, $paymentMethod, $reference);

            $entityManager->flush();

            $this->addFlash('success', 'Paiement marqué comme payé !');
        }

        return $this->redirectToRoute('app_payment_show', ['id' => $payment->getId()]);
    }

    #[Route('/bulk-generate', name: 'app_payment_bulk_generate', methods: ['GET', 'POST'])]
    public function bulkGenerate(
        Request $request,
        EntityManagerInterface $entityManager,
        RentalContractRepository $contractRepository
    ): Response {
        if ($request->isMethod('POST')) {
            $month = new \DateTime($request->request->get('month') . '-01');
            $contracts = $contractRepository->findActiveContracts();
            $generated = 0;

            foreach ($contracts as $contract) {
                // Vérifier s'il n'existe pas déjà un paiement pour ce mois
                $existingPayment = $entityManager->getRepository(Payment::class)->findOneBy([
                    'rentalContract' => $contract,
                    'dueDate' => $month,
                    'type' => 'rent'
                ]);

                if (!$existingPayment) {
                    $payment = new Payment();
                    $payment->setRentalContract($contract);
                    $payment->setTenant($contract->getTenant());
                    $payment->setAmount($contract->getTotalRent());
                    $payment->setDueDate(clone $month);
                    $payment->setType('rent');
                    $payment->setStatus('pending');

                    $entityManager->persist($payment);
                    $generated++;
                }
            }

            if ($generated > 0) {
                $entityManager->flush();
                $this->addFlash('success', "$generated paiement(s) généré(s) pour " . $month->format('m/Y'));
            } else {
                $this->addFlash('info', 'Aucun paiement à générer pour ce mois (déjà existants).');
            }

            return $this->redirectToRoute('app_payment_index');
        }

        return $this->render('payment/bulk_generate.html.twig');
    }
}