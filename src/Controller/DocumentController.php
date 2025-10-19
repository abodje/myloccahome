<?php

namespace App\Controller;

use App\Entity\Document;
use App\Form\DocumentType;
use App\Repository\DocumentRepository;
use App\Repository\PaymentRepository;
use App\Service\RentReceiptService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/mes-documents')]
class DocumentController extends AbstractController
{
    #[Route('/', name: 'app_document_index', methods: ['GET'])]
    public function index(DocumentRepository $documentRepository): Response
    {
        $user = $this->getUser();
        $documentsByType = [];

        // Initialiser toutes les catégories
        $documentsByType = [
            'Assurance' => [],
            'Avis d\'échéance' => [],
            'Bail' => [],
            'Diagnostics' => [],
            'OK' => [],
        ];

        // Filtrer les documents selon le rôle de l'utilisateur
        if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
            // Si l'utilisateur est un locataire, ne montrer que ses documents
            $tenant = $user->getTenant();
            if ($tenant) {
                $tenantDocuments = $documentRepository->findByTenant($tenant->getId());

                // Organiser par type
                foreach ($tenantDocuments as $document) {
                    $type = $document->getType();

                    // Grouper "Bail" et "Contrat de location" ensemble
                    if ($type === 'Bail' || $type === 'Contrat de location') {
                        $type = 'Bail';
                    }
                    // Grouper "Conseils" sous "OK"
                    elseif ($type === 'Conseils') {
                        $type = 'OK';
                    }

                    if (isset($documentsByType[$type])) {
                        $documentsByType[$type][] = $document;
                    }
                }
            }
        } elseif ($user && in_array('ROLE_MANAGER', $user->getRoles())) {
            // Si l'utilisateur est un gestionnaire, montrer les documents des propriétés qu'il gère
            $owner = $user->getOwner();
            if ($owner) {
                $allDocuments = $documentRepository->findByManager($owner->getId());
            } else {
                $allDocuments = $documentRepository->findBy([], ['createdAt' => 'DESC']);
            }
        } elseif ($user && (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_SUPER_ADMIN', $user->getRoles()))) {
            // Pour les admins, filtrer selon l'organisation/société
            $organization = $user->getOrganization();
            $company = $user->getCompany();

            error_log("DocumentController - Admin: organization=" . ($organization ? $organization->getName() : 'null') . ", company=" . ($company ? $company->getName() : 'null'));

            if ($company) {
                // Admin avec société spécifique : filtrer par société
                $allDocuments = $documentRepository->findByCompany($company);
                error_log("DocumentController - Filtered by company: " . $company->getName());
            } elseif ($organization) {
                // Admin avec organisation : filtrer par organisation
                $allDocuments = $documentRepository->findByOrganization($organization);
                error_log("DocumentController - Filtered by organization: " . $organization->getName());
            } else {
                // Super Admin sans organisation/société : tous les documents
                $allDocuments = $documentRepository->findBy([], ['createdAt' => 'DESC']);
                error_log("DocumentController - Super Admin: showing all documents");
            }
        } else {
            // Pour les autres rôles, montrer tous les documents
            $allDocuments = $documentRepository->findBy([], ['createdAt' => 'DESC']);
        }

        // Organiser par type pour tous les documents récupérés
        if (isset($allDocuments)) {
            foreach ($allDocuments as $document) {
                $type = $document->getType();

                // Grouper "Bail" et "Contrat de location" ensemble
                if ($type === 'Bail' || $type === 'Contrat de location') {
                    $type = 'Bail';
                }
                // Grouper "Conseils" sous "OK"
                elseif ($type === 'Conseils') {
                    $type = 'OK';
                }

                if (isset($documentsByType[$type])) {
                    $documentsByType[$type][] = $document;
                }
            }
        }

        // Calculer les statistiques (filtrées selon le rôle)
        $stats = $this->calculateFilteredStats($documentRepository, $user);

        return $this->render('document/index.html.twig', [
            'documents_by_type' => $documentsByType,
            'stats' => $stats,
            'is_tenant_view' => $user && in_array('ROLE_TENANT', $user->getRoles()),
        ]);
    }

    #[Route('/type/{type}', name: 'app_document_by_type', methods: ['GET'])]
    public function byType(string $type, DocumentRepository $documentRepository): Response
    {
        $user = $this->getUser();
        $documents = [];

        // Filtrer selon le rôle
        if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
            // Pour les locataires, filtrer par type ET par tenant
            $tenant = $user->getTenant();
            if ($tenant) {
                $allDocuments = $documentRepository->findByTenant($tenant->getId());
                foreach ($allDocuments as $document) {
                    $documentType = $document->getType();
                    // Grouper "Bail" et "Contrat de location"
                    if (($documentType === 'Bail' || $documentType === 'Contrat de location') && $type === 'Bail') {
                        $documents[] = $document;
                    } elseif ($documentType === $type) {
                        $documents[] = $document;
                    }
                }
            }
        } else {
            // Pour les admins/managers, filtrer par organization/company
            /** @var \App\Entity\User $user */
            $qb = $documentRepository->createQueryBuilder('d');

            // Filtrer par type
            if ($type === 'Bail') {
                $qb->where('d.type IN (:types)')
                   ->setParameter('types', ['Bail', 'Contrat de location']);
            } else {
                $qb->where('d.type = :type')
                   ->setParameter('type', $type);
            }

            // Filtrage multi-tenant
            if ($user && method_exists($user, 'getOrganization') && $user->getOrganization()) {
                // MANAGER: voir documents de SA company
                if (method_exists($user, 'getCompany') && $user->getCompany() && in_array('ROLE_MANAGER', $user->getRoles())) {
                    $qb->andWhere('d.company = :company')
                       ->setParameter('company', $user->getCompany());
                }
                // ADMIN: voir documents de SON organization
                else {
                    $qb->andWhere('d.organization = :organization')
                       ->setParameter('organization', $user->getOrganization());
                }
            }

            $documents = $qb->orderBy('d.createdAt', 'DESC')->getQuery()->getResult();
        }

        return $this->render('document/by_type.html.twig', [
            'documents' => $documents,
            'type' => $type,
        ]);
    }

    #[Route('/nouveau', name: 'app_document_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $document = new Document();
        $form = $this->createForm(DocumentType::class, $document);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('file')->getData();

            if ($uploadedFile) {
                $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$uploadedFile->guessExtension();

                try {
                    $uploadedFile->move(
                        $this->getParameter('documents_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du fichier.');
                    return $this->redirectToRoute('app_document_new');
                }

                $document->setFileName($newFilename);
                $document->setOriginalFileName($uploadedFile->getClientOriginalName());
                $document->setMimeType($uploadedFile->getClientMimeType());
                $document->setFileSize($uploadedFile->getSize());
            }

            $entityManager->persist($document);
            $entityManager->flush();

            $this->addFlash('success', 'Le document a été ajouté avec succès.');

            return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
        }

        return $this->render('document/new.html.twig', [
            'document' => $document,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_document_show', methods: ['GET'])]
    public function show(Document $document): Response
    {
        return $this->render('document/show.html.twig', [
            'document' => $document,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_document_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Document $document, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DocumentType::class, $document);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $document->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Le document a été modifié avec succès.');

            return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
        }

        return $this->render('document/edit.html.twig', [
            'document' => $document,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/telecharger', name: 'app_document_download', methods: ['GET'])]
    public function download(Document $document): Response
    {
        $filePath = $this->getParameter('documents_directory') . '/' . $document->getFileName();

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Le fichier n\'existe pas.');
        }

        return $this->file($filePath, $document->getOriginalFileName());
    }

    #[Route('/{id}/supprimer', name: 'app_document_delete', methods: ['POST'])]
    public function delete(Request $request, Document $document, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$document->getId(), $request->getPayload()->getString('_token'))) {
            // Supprimer le fichier physique
            $filePath = $this->getParameter('documents_directory') . '/' . $document->getFileName();
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $entityManager->remove($document);
            $entityManager->flush();

            $this->addFlash('success', 'Le document a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_document_index');
    }

    #[Route('/recherche', name: 'app_document_search', methods: ['GET'])]
    public function search(Request $request, DocumentRepository $documentRepository): Response
    {
        $query = $request->query->get('q', '');
        $documents = [];
        $user = $this->getUser();

        if ($query) {
            if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
                // Pour les locataires, filtrer par tenant ET par recherche
                $tenant = $user->getTenant();
                if ($tenant) {
                    $allDocuments = $documentRepository->findByTenant($tenant->getId());
                    foreach ($allDocuments as $document) {
                        if (stripos($document->getName(), $query) !== false ||
                            stripos($document->getOriginalFileName(), $query) !== false ||
                            stripos($document->getDescription(), $query) !== false) {
                            $documents[] = $document;
                        }
                    }
                }
            } else {
                // Pour les admins/managers, recherche globale
                $documents = $documentRepository->search($query);
            }
        }

        return $this->render('document/search.html.twig', [
            'documents' => $documents,
            'query' => $query,
        ]);
    }

    #[Route('/expires', name: 'app_document_expiring', methods: ['GET'])]
    public function expiring(DocumentRepository $documentRepository): Response
    {
        $user = $this->getUser();
        $expiringSoon = [];
        $expired = [];

        if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
            // Pour les locataires, filtrer par tenant
            $tenant = $user->getTenant();
            if ($tenant) {
                $allDocuments = $documentRepository->findByTenant($tenant->getId());
                $now = new \DateTime();
                $in30Days = new \DateTime('+30 days');

                foreach ($allDocuments as $document) {
                    $expirationDate = $document->getExpirationDate();
                    if ($expirationDate) {
                        if ($expirationDate <= $in30Days && $expirationDate > $now) {
                            $expiringSoon[] = $document;
                        } elseif ($expirationDate <= $now) {
                            $expired[] = $document;
                        }
                    }
                }
            }
        } else {
            // Pour les admins/managers, montrer tous les documents
            $expiringSoon = $documentRepository->findExpiringSoon();
            $expired = $documentRepository->findExpired();
        }

        return $this->render('document/expiring.html.twig', [
            'expiring_soon' => $expiringSoon,
            'expired' => $expired,
        ]);
    }


    /**
     * Génère une quittance de loyer pour un paiement
     */
    #[Route('/generer-quittance/{paymentId}', name: 'app_document_generate_receipt', methods: ['POST'])]
    public function generateReceipt(
        int $paymentId,
        PaymentRepository $paymentRepository,
        RentReceiptService $receiptService
    ): Response {
        $payment = $paymentRepository->find($paymentId);

        if (!$payment) {
            $this->addFlash('error', 'Paiement introuvable.');
            return $this->redirectToRoute('app_document_index');
        }

        // Vérifier que le paiement est payé
        if ($payment->getStatus() !== 'Payé') {
            $this->addFlash('error', 'La quittance ne peut être générée que pour un paiement déjà effectué.');
            return $this->redirectToRoute('app_payment_index');
        }

        try {
            $receipt = $receiptService->generateRentReceipt($payment);
            $this->addFlash('success', 'La quittance de loyer a été générée avec succès.');
            return $this->redirectToRoute('app_document_show', ['id' => $receipt->getId()]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la génération de la quittance : ' . $e->getMessage());
            return $this->redirectToRoute('app_payment_index');
        }
    }

    /**
     * Génère un avis d'échéance pour un paiement à venir
     */
    #[Route('/generer-avis-echeance/{paymentId}', name: 'app_document_generate_notice', methods: ['POST'])]
    public function generateNotice(
        int $paymentId,
        PaymentRepository $paymentRepository,
        RentReceiptService $receiptService
    ): Response {
        $payment = $paymentRepository->find($paymentId);

        if (!$payment) {
            $this->addFlash('error', 'Paiement introuvable.');
            return $this->redirectToRoute('app_document_index');
        }

        // Vérifier que le paiement est en attente
        if ($payment->getStatus() === 'Payé') {
            $this->addFlash('error', 'Un avis d\'échéance ne peut être généré que pour un paiement en attente.');
            return $this->redirectToRoute('app_payment_index');
        }

        try {
            $notice = $receiptService->generatePaymentNotice($payment);
            $this->addFlash('success', 'L\'avis d\'échéance a été généré avec succès.');
            return $this->redirectToRoute('app_document_show', ['id' => $notice->getId()]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la génération de l\'avis : ' . $e->getMessage());
            return $this->redirectToRoute('app_payment_index');
        }
    }

    /**
     * Génère tous les documents du mois en cours
     */
    #[Route('/generer-documents-mois', name: 'app_document_generate_monthly', methods: ['POST'])]
    public function generateMonthlyDocuments(
        Request $request,
        RentReceiptService $receiptService
    ): Response {
        $monthStr = $request->request->get('month', date('Y-m'));

        try {
            $month = new \DateTime($monthStr . '-01');

            // Générer les quittances du mois
            $receipts = $receiptService->generateMonthlyReceipts($month);

            // Générer les avis d'échéance pour le mois prochain
            $nextMonth = (clone $month)->modify('+1 month');
            $notices = $receiptService->generateUpcomingNotices($nextMonth);

            $total = count($receipts) + count($notices);

            $this->addFlash('success', sprintf(
                '✅ %d document(s) généré(s) : %d quittance(s) et %d avis d\'échéance',
                $total,
                count($receipts),
                count($notices)
            ));
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la génération des documents : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_document_index');
    }

    /**
     * Calcule les statistiques filtrées selon le rôle de l'utilisateur
     */
    private function calculateFilteredStats(DocumentRepository $documentRepository, $user): array
    {
        if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
            // Pour les locataires, calculer les stats sur leurs documents seulement
            $tenant = $user->getTenant();
            if ($tenant) {
                $tenantDocuments = $documentRepository->findByTenant($tenant->getId());

                $stats = [
                    'total' => count($tenantDocuments),
                    'archived' => 0,
                    'expiring_soon' => 0,
                    'expired' => 0
                ];

                foreach ($tenantDocuments as $document) {
                    if ($document->getIsArchived()) {
                        $stats['archived']++;
                    }
                    // Vérifier les dates d'expiration si applicable
                    if ($document->getExpirationDate()) {
                        $now = new \DateTime();
                        $expirationDate = $document->getExpirationDate();
                        $daysUntilExpiration = $now->diff($expirationDate)->days;

                        if ($expirationDate < $now) {
                            $stats['expired']++;
                        } elseif ($daysUntilExpiration <= 30) {
                            $stats['expiring_soon']++;
                        }
                    }
                }

                return $stats;
            }
        } elseif ($user && in_array('ROLE_MANAGER', $user->getRoles())) {
            // Pour les gestionnaires, calculer les stats sur les documents qu'ils gèrent
            $owner = $user->getOwner();
            if ($owner) {
                $managerDocuments = $documentRepository->findByManager($owner->getId());

                $stats = [
                    'total' => count($managerDocuments),
                    'archived' => 0,
                    'expiring_soon' => 0,
                    'expired' => 0
                ];

                foreach ($managerDocuments as $document) {
                    if ($document->getIsArchived()) {
                        $stats['archived']++;
                    }
                    // Vérifier les dates d'expiration si applicable
                    if ($document->getExpirationDate()) {
                        $now = new \DateTime();
                        $expirationDate = $document->getExpirationDate();
                        $daysUntilExpiration = $now->diff($expirationDate)->days;

                        if ($expirationDate < $now) {
                            $stats['expired']++;
                        } elseif ($daysUntilExpiration <= 30) {
                            $stats['expiring_soon']++;
                        }
                    }
                }

                return $stats;
            }
        } elseif ($user && (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_SUPER_ADMIN', $user->getRoles()))) {
            // Pour les admins, calculer les stats selon l'organisation/société
            $organization = $user->getOrganization();
            $company = $user->getCompany();

            if ($company) {
                // Admin avec société spécifique
                $companyDocuments = $documentRepository->findByCompany($company);

                $stats = [
                    'total' => count($companyDocuments),
                    'archived' => 0,
                    'expiring_soon' => 0,
                    'expired' => 0
                ];

                foreach ($companyDocuments as $document) {
                    if ($document->getIsArchived()) {
                        $stats['archived']++;
                    }
                    // Vérifier les dates d'expiration si applicable
                    if ($document->getExpirationDate()) {
                        $now = new \DateTime();
                        $expirationDate = $document->getExpirationDate();
                        $daysUntilExpiration = $now->diff($expirationDate)->days;

                        if ($expirationDate < $now) {
                            $stats['expired']++;
                        } elseif ($daysUntilExpiration <= 30) {
                            $stats['expiring_soon']++;
                        }
                    }
                }

                return $stats;
            } elseif ($organization) {
                // Admin avec organisation
                $orgDocuments = $documentRepository->findByOrganization($organization);

                $stats = [
                    'total' => count($orgDocuments),
                    'archived' => 0,
                    'expiring_soon' => 0,
                    'expired' => 0
                ];

                foreach ($orgDocuments as $document) {
                    if ($document->getIsArchived()) {
                        $stats['archived']++;
                    }
                    // Vérifier les dates d'expiration si applicable
                    if ($document->getExpirationDate()) {
                        $now = new \DateTime();
                        $expirationDate = $document->getExpirationDate();
                        $daysUntilExpiration = $now->diff($expirationDate)->days;

                        if ($expirationDate < $now) {
                            $stats['expired']++;
                        } elseif ($daysUntilExpiration <= 30) {
                            $stats['expiring_soon']++;
                        }
                    }
                }

                return $stats;
            }
        }

        // Pour les super admins sans organisation/société, retourner les stats globales
        return $documentRepository->getStatistics();
    }
}
