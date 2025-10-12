<?php

namespace App\Controller;

use App\Entity\Document;
use App\Form\DocumentType;
use App\Repository\DocumentRepository;
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
        } else {
            // Pour les admins/managers, remplir avec tous les documents
            $documentsByType['Assurance'] = $documentRepository->findByType('Assurance');
            $documentsByType['Avis d\'échéance'] = $documentRepository->findByType('Avis d\'échéance');
            $documentsByType['Bail'] = array_merge(
                $documentRepository->findByType('Bail'),
                $documentRepository->findByType('Contrat de location')
            );
            $documentsByType['Diagnostics'] = $documentRepository->findByType('Diagnostics');
            $documentsByType['OK'] = $documentRepository->findByType('Conseils');
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
            // Pour les admins/managers, montrer tous les documents du type
            if ($type === 'Bail') {
                $documents = array_merge(
                    $documentRepository->findByType('Bail'),
                    $documentRepository->findByType('Contrat de location')
                );
            } else {
                $documents = $documentRepository->findByType($type);
            }
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
                    if ($document->isArchived()) {
                        $stats['archived']++;
                    }

                    $expirationDate = $document->getExpirationDate();
                    if ($expirationDate) {
                        $now = new \DateTime();
                        $in30Days = new \DateTime('+30 days');

                        if ($expirationDate <= $in30Days && $expirationDate > $now) {
                            $stats['expiring_soon']++;
                        } elseif ($expirationDate <= $now) {
                            $stats['expired']++;
                        }
                    }
                }

                return $stats;
            }
        }

        // Pour les admins/managers, retourner les stats globales
        return $documentRepository->getStatistics();
    }
}
