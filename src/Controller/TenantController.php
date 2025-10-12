<?php

namespace App\Controller;

use App\Entity\Tenant;
use App\Entity\User;
use App\Form\TenantType;
use App\Repository\TenantRepository;
use App\Repository\LeaseRepository;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/locataires')]
class TenantController extends AbstractController
{
    #[Route('/', name: 'app_tenant_index', methods: ['GET'])]
    public function index(TenantRepository $tenantRepository, Request $request): Response
    {
        $search = $request->query->get('search');
        $status = $request->query->get('status'); // actif, inactif

        if ($search) {
            $tenants = $tenantRepository->findByNameOrEmail($search);
        } elseif ($status === 'actif') {
            $tenants = $tenantRepository->findWithActiveLeases();
        } else {
            $tenants = $tenantRepository->findBy([], ['lastName' => 'ASC', 'firstName' => 'ASC']);
        }

        $stats = $tenantRepository->getStatistics();

        return $this->render('tenant/index.html.twig', [
            'tenants' => $tenants,
            'stats' => $stats,
            'search' => $search,
            'current_status' => $status,
        ]);
    }

    #[Route('/nouveau', name: 'app_tenant_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $tenant = new Tenant();
        $form = $this->createForm(TenantType::class, $tenant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tenant);

            // Créer un compte User si demandé
            $createAccount = $request->request->get('create_user_account');
            if ($createAccount) {
                $user = new User();
                $user->setEmail($tenant->getEmail())
                     ->setFirstName($tenant->getFirstName())
                     ->setLastName($tenant->getLastName())
                     ->setPhone($tenant->getPhone())
                     ->setAddress($tenant->getAddress())
                     ->setCity($tenant->getCity())
                     ->setPostalCode($tenant->getPostalCode())
                     ->setBirthDate($tenant->getBirthDate())
                     ->setRoles(['ROLE_TENANT'])
                     ->setActive(true);

                // Générer un mot de passe aléatoire ou utiliser celui fourni
                $password = $request->request->get('user_password') ?? bin2hex(random_bytes(8));
                $hashedPassword = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);

                // Lier le User au Tenant
                $tenant->setUser($user);

                $entityManager->persist($user);

                $this->addFlash('success', "Le locataire a été créé avec succès. Compte créé avec le mot de passe : {$password}");
            } else {
                $this->addFlash('success', 'Le locataire a été créé avec succès.');
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_tenant_show', ['id' => $tenant->getId()]);
        }

        return $this->render('tenant/new.html.twig', [
            'tenant' => $tenant,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tenant_show', methods: ['GET'])]
    public function show(
        Tenant $tenant,
        LeaseRepository $leaseRepository,
        PaymentRepository $paymentRepository
    ): Response {
        $leases = $leaseRepository->findByTenant($tenant->getId());
        $currentLease = $tenant->getCurrentLease();

        // Historique des paiements du locataire
        $payments = [];
        foreach ($leases as $lease) {
            $leasePayments = $paymentRepository->findByLease($lease->getId());
            $payments = array_merge($payments, $leasePayments);
        }

        // Trier par date décroissante
        usort($payments, function($a, $b) {
            return $b->getDueDate() <=> $a->getDueDate();
        });

        return $this->render('tenant/show.html.twig', [
            'tenant' => $tenant,
            'current_lease' => $currentLease,
            'leases' => $leases,
            'recent_payments' => array_slice($payments, 0, 10),
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_tenant_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tenant $tenant, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TenantType::class, $tenant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tenant->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Le locataire a été modifié avec succès.');

            return $this->redirectToRoute('app_tenant_show', ['id' => $tenant->getId()]);
        }

        return $this->render('tenant/edit.html.twig', [
            'tenant' => $tenant,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_tenant_delete', methods: ['POST'])]
    public function delete(Request $request, Tenant $tenant, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tenant->getId(), $request->getPayload()->getString('_token'))) {
            // Vérifier qu'il n'y a pas de contrat actif
            if ($tenant->getCurrentLease()) {
                $this->addFlash('error', 'Impossible de supprimer un locataire avec un contrat actif.');
                return $this->redirectToRoute('app_tenant_show', ['id' => $tenant->getId()]);
            }

            $entityManager->remove($tenant);
            $entityManager->flush();

            $this->addFlash('success', 'Le locataire a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_tenant_index');
    }

    #[Route('/{id}/contrats', name: 'app_tenant_leases', methods: ['GET'])]
    public function leases(Tenant $tenant, LeaseRepository $leaseRepository): Response
    {
        $leases = $leaseRepository->findByTenant($tenant->getId());

        return $this->render('tenant/leases.html.twig', [
            'tenant' => $tenant,
            'leases' => $leases,
        ]);
    }

    #[Route('/{id}/paiements', name: 'app_tenant_payments', methods: ['GET'])]
    public function payments(Tenant $tenant, PaymentRepository $paymentRepository): Response
    {
        // Récupérer tous les paiements du locataire via ses contrats
        $payments = [];
        foreach ($tenant->getLeases() as $lease) {
            $leasePayments = $paymentRepository->findByLease($lease->getId());
            $payments = array_merge($payments, $leasePayments);
        }

        // Trier par date décroissante
        usort($payments, function($a, $b) {
            return $b->getDueDate() <=> $a->getDueDate();
        });

        return $this->render('tenant/payments.html.twig', [
            'tenant' => $tenant,
            'payments' => $payments,
        ]);
    }

    #[Route('/{id}/documents', name: 'app_tenant_documents', methods: ['GET'])]
    public function documents(Tenant $tenant): Response
    {
        return $this->render('tenant/documents.html.twig', [
            'tenant' => $tenant,
        ]);
    }

    #[Route('/recherche', name: 'app_tenant_search', methods: ['GET'])]
    public function search(Request $request, TenantRepository $tenantRepository): Response
    {
        $query = $request->query->get('q', '');
        $tenants = [];

        if ($query) {
            $tenants = $tenantRepository->findByNameOrEmail($query);
        }

        return $this->render('tenant/search.html.twig', [
            'tenants' => $tenants,
            'query' => $query,
        ]);
    }

    #[Route('/export', name: 'app_tenant_export', methods: ['GET'])]
    public function export(TenantRepository $tenantRepository): Response
    {
        $tenants = $tenantRepository->findBy([], ['lastName' => 'ASC', 'firstName' => 'ASC']);

        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="locataires_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://temp', 'w');

        // En-têtes CSV
        fputcsv($output, [
            'ID',
            'Prénom',
            'Nom',
            'Email',
            'Téléphone',
            'Date de naissance',
            'Profession',
            'Revenus mensuels',
            'Adresse',
            'Ville',
            'Code postal',
            'Date de création'
        ], ';');

        // Données
        foreach ($tenants as $tenant) {
            fputcsv($output, [
                $tenant->getId(),
                $tenant->getFirstName(),
                $tenant->getLastName(),
                $tenant->getEmail(),
                $tenant->getPhone(),
                $tenant->getBirthDate() ? $tenant->getBirthDate()->format('d/m/Y') : '',
                $tenant->getProfession(),
                $tenant->getMonthlyIncome(),
                $tenant->getAddress(),
                $tenant->getCity(),
                $tenant->getPostalCode(),
                $tenant->getCreatedAt()->format('d/m/Y H:i')
            ], ';');
        }

        rewind($output);
        $response->setContent(stream_get_contents($output));
        fclose($output);

        return $response;
    }

    #[Route('/{id}/creer-compte', name: 'app_tenant_create_account', methods: ['POST'])]
    public function createAccount(
        Tenant $tenant,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        // Vérifier si le tenant a déjà un compte
        if ($tenant->getUser()) {
            $this->addFlash('warning', 'Ce locataire possède déjà un compte utilisateur.');
            return $this->redirectToRoute('app_tenant_show', ['id' => $tenant->getId()]);
        }

        // Créer le compte User
        $user = new User();
        $user->setEmail($tenant->getEmail())
             ->setFirstName($tenant->getFirstName())
             ->setLastName($tenant->getLastName())
             ->setPhone($tenant->getPhone())
             ->setAddress($tenant->getAddress())
             ->setCity($tenant->getCity())
             ->setPostalCode($tenant->getPostalCode())
             ->setBirthDate($tenant->getBirthDate())
             ->setRoles(['ROLE_TENANT'])
             ->setActive(true);

        // Générer un mot de passe aléatoire
        $password = bin2hex(random_bytes(8));
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Lier au Tenant
        $tenant->setUser($user);

        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', "Compte créé avec succès ! Email: {$tenant->getEmail()} / Mot de passe: {$password}");
        $this->addFlash('info', "N'oubliez pas de communiquer ces identifiants au locataire de manière sécurisée.");

        return $this->redirectToRoute('app_tenant_show', ['id' => $tenant->getId()]);
    }
}
