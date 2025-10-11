<?php

namespace App\Controller;

use App\Entity\RentalContract;
use App\Entity\Payment;
use App\Form\RentalContractType;
use App\Repository\RentalContractRepository;
use App\Repository\PropertyRepository;
use App\Repository\TenantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/contracts')]
class RentalContractController extends AbstractController
{
    #[Route('/', name: 'app_contract_index', methods: ['GET'])]
    public function index(Request $request, RentalContractRepository $contractRepository): Response
    {
        $status = $request->query->get('status');

        if ($status) {
            $contracts = $contractRepository->findBy(['status' => $status], ['startDate' => 'DESC']);
        } else {
            $contracts = $contractRepository->findBy([], ['startDate' => 'DESC']);
        }

        return $this->render('contract/index.html.twig', [
            'contracts' => $contracts,
            'status' => $status,
        ]);
    }

    #[Route('/new', name: 'app_contract_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, PropertyRepository $propertyRepository, TenantRepository $tenantRepository): Response
    {
        $contract = new RentalContract();
        
        // Pré-remplir si property_id ou tenant_id sont fournis
        $propertyId = $request->query->get('property_id');
        $tenantId = $request->query->get('tenant_id');
        
        if ($propertyId) {
            $property = $propertyRepository->find($propertyId);
            if ($property) {
                $contract->setProperty($property);
                // Pré-remplir avec les données de la propriété
                $contract->setRentAmount($property->getRentAmount());
                $contract->setCharges($property->getCharges());
                $contract->setDeposit($property->getDeposit());
            }
        }
        
        if ($tenantId) {
            $tenant = $tenantRepository->find($tenantId);
            if ($tenant) {
                $contract->setTenant($tenant);
            }
        }

        $form = $this->createForm(RentalContractType::class, $contract);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Générer un numéro de contrat unique
            $contract->setContractNumber('CTR-' . date('Y') . '-' . str_pad($contractRepository->count([]) + 1, 4, '0', STR_PAD_LEFT));
            
            $entityManager->persist($contract);
            
            // Mettre à jour le statut de la propriété
            if ($contract->getProperty()) {
                $contract->getProperty()->setStatus('occupied');
            }
            
            $entityManager->flush();

            $this->addFlash('success', 'Le contrat a été créé avec succès.');

            return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('contract/new.html.twig', [
            'contract' => $contract,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_contract_show', methods: ['GET'])]
    public function show(RentalContract $contract): Response
    {
        $payments = $contract->getPayments()->toArray();
        
        // Trier les paiements par période (plus récents en premier)
        usort($payments, fn($a, $b) => $b->getPeriod() <=> $a->getPeriod());

        return $this->render('contract/show.html.twig', [
            'contract' => $contract,
            'payments' => $payments,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_contract_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, RentalContract $contract, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RentalContractType::class, $contract);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contract->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Le contrat a été modifié avec succès.');

            return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('contract/edit.html.twig', [
            'contract' => $contract,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/terminate', name: 'app_contract_terminate', methods: ['POST'])]
    public function terminate(Request $request, RentalContract $contract, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('terminate'.$contract->getId(), $request->getPayload()->getString('_token'))) {
            $contract->setStatus('terminated');
            $contract->setEndDate(new \DateTime());
            $contract->setUpdatedAt(new \DateTime());
            
            // Libérer la propriété
            if ($contract->getProperty()) {
                $contract->getProperty()->setStatus('available');
            }
            
            $entityManager->flush();

            $this->addFlash('success', 'Le contrat a été résilié avec succès.');
        }

        return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
    }

    #[Route('/{id}/renew', name: 'app_contract_renew', methods: ['GET', 'POST'])]
    public function renew(Request $request, RentalContract $contract, EntityManagerInterface $entityManager): Response
    {
        // Créer un nouveau contrat basé sur l'ancien
        $newContract = new RentalContract();
        $newContract->setProperty($contract->getProperty());
        $newContract->setTenant($contract->getTenant());
        $newContract->setRentAmount($contract->getRentAmount());
        $newContract->setCharges($contract->getCharges());
        $newContract->setDeposit($contract->getDeposit());
        $newContract->setRentDueDay($contract->getRentDueDay());
        $newContract->setSpecialConditions($contract->getSpecialConditions());

        $form = $this->createForm(RentalContractType::class, $newContract);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Générer un nouveau numéro de contrat
            $newContract->setContractNumber('CTR-' . date('Y') . '-' . str_pad($entityManager->getRepository(RentalContract::class)->count([]) + 1, 4, '0', STR_PAD_LEFT));
            
            // Terminer l'ancien contrat
            $contract->setStatus('terminated');
            $contract->setEndDate($newContract->getStartDate());
            $contract->setUpdatedAt(new \DateTime());
            
            $entityManager->persist($newContract);
            $entityManager->flush();

            $this->addFlash('success', 'Le contrat a été renouvelé avec succès.');

            return $this->redirectToRoute('app_contract_show', ['id' => $newContract->getId()]);
        }

        return $this->render('contract/renew.html.twig', [
            'oldContract' => $contract,
            'newContract' => $newContract,
            'form' => $form,
        ]);
    }

    #[Route('/active', name: 'app_contract_active', methods: ['GET'])]
    public function active(RentalContractRepository $contractRepository): Response
    {
        $contracts = $contractRepository->findActive();

        return $this->render('contract/active.html.twig', [
            'contracts' => $contracts,
        ]);
    }

    #[Route('/expiring', name: 'app_contract_expiring', methods: ['GET'])]
    public function expiring(Request $request, RentalContractRepository $contractRepository): Response
    {
        $days = $request->query->get('days', 60);
        $contracts = $contractRepository->findExpiringContracts((int)$days);

        return $this->render('contract/expiring.html.twig', [
            'contracts' => $contracts,
            'days' => $days,
        ]);
    }

    #[Route('/{id}/generate-payments', name: 'app_contract_generate_payments', methods: ['POST'])]
    public function generatePayments(Request $request, RentalContract $contract, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('generate_payments'.$contract->getId(), $request->getPayload()->getString('_token'))) {
            $months = (int)$request->request->get('months', 12);
            $startDate = new \DateTime($request->request->get('start_date', 'first day of next month'));
            
            for ($i = 0; $i < $months; $i++) {
                $period = clone $startDate;
                $period->modify("+{$i} months");
                
                // Vérifier si un paiement existe déjà pour cette période
                $existingPayment = $entityManager->getRepository(Payment::class)->findOneBy([
                    'rentalContract' => $contract,
                    'period' => $period->format('Y-m')
                ]);
                
                if (!$existingPayment) {
                    $payment = new Payment();
                    $payment->setRentalContract($contract);
                    $payment->setAmount($contract->getTotalMonthlyAmount());
                    $payment->setPeriod($period->format('Y-m'));
                    
                    $dueDate = new \DateTime($period->format('Y-m-') . str_pad($contract->getRentDueDay(), 2, '0', STR_PAD_LEFT));
                    $payment->setDueDate($dueDate);
                    
                    $entityManager->persist($payment);
                }
            }
            
            $entityManager->flush();
            
            $this->addFlash('success', "Les échéances de paiement ont été générées pour les {$months} prochains mois.");
        }

        return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
    }
}