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
        // Organiser les documents par type
        $documentsByType = [
            'Assurance' => $documentRepository->findByType('Assurance'),
            'Avis d\'échéance' => $documentRepository->findByType('Avis d\'échéance'),
            'Bail' => array_merge(
                $documentRepository->findByType('Bail'),
                $documentRepository->findByType('Contrat de location')
            ),
            'Diagnostics' => $documentRepository->findByType('Diagnostics'),
            'OK' => $documentRepository->findByType('Conseils'),
        ];

        // Calculer les statistiques
        $stats = $documentRepository->getStatistics();

        return $this->render('document/index.html.twig', [
            'documents_by_type' => $documentsByType,
            'stats' => $stats,
        ]);
    }

    #[Route('/type/{type}', name: 'app_document_by_type', methods: ['GET'])]
    public function byType(string $type, DocumentRepository $documentRepository): Response
    {
        $documents = $documentRepository->findByType($type);

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

        if ($query) {
            $documents = $documentRepository->search($query);
        }

        return $this->render('document/search.html.twig', [
            'documents' => $documents,
            'query' => $query,
        ]);
    }

    #[Route('/expires', name: 'app_document_expiring', methods: ['GET'])]
    public function expiring(DocumentRepository $documentRepository): Response
    {
        $expiringSoon = $documentRepository->findExpiringSoon();
        $expired = $documentRepository->findExpired();

        return $this->render('document/expiring.html.twig', [
            'expiring_soon' => $expiringSoon,
            'expired' => $expired,
        ]);
    }
}
