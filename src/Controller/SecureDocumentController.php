<?php

namespace App\Controller;

use App\Entity\Document;
use App\Service\SecureFileService;
use App\Repository\DocumentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;

/**
 * Contrôleur sécurisé pour les téléchargements de documents
 */
#[Route('/secure-documents')]
#[IsGranted('ROLE_USER')]
class SecureDocumentController extends AbstractController
{
    public function __construct(
        private SecureFileService $secureFileService,
        private DocumentRepository $documentRepository,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Téléchargement sécurisé d'un document
     */
    #[Route('/{id}/download', name: 'app_secure_document_download', methods: ['GET'])]
    public function download(Document $document, Request $request): Response
    {
        try {
            $user = $this->getUser();

            // Vérification supplémentaire des permissions
            if (!$this->hasAccessToDocument($document, $user)) {
                $this->logger->warning('Unauthorized document access attempt', [
                    'user_id' => $user?->getId(),
                    'document_id' => $document->getId(),
                    'ip' => $request->getClientIp(),
                ]);

                throw $this->createAccessDeniedException('Accès non autorisé à ce document.');
            }

            return $this->secureFileService->downloadSecureFile($document, $user);

        } catch (\Exception $e) {
            $this->logger->error('Error downloading secure document', [
                'document_id' => $document->getId(),
                'error' => $e->getMessage(),
                'user_id' => $this->getUser()?->getId(),
            ]);

            $this->addFlash('error', 'Erreur lors du téléchargement du document.');
            return $this->redirectToRoute('app_document_index');
        }
    }

    /**
     * Prévisualisation sécurisée d'un document (pour les images)
     */
    #[Route('/{id}/preview', name: 'app_secure_document_preview', methods: ['GET'])]
    public function preview(Document $document, Request $request): Response
    {
        try {
            $user = $this->getUser();

            if (!$this->hasAccessToDocument($document, $user)) {
                throw $this->createAccessDeniedException('Accès non autorisé à ce document.');
            }

            // Seules les images peuvent être prévisualisées
            $imageMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($document->getMimeType(), $imageMimeTypes)) {
                throw $this->createNotFoundException('Prévisualisation non disponible pour ce type de fichier.');
            }

            $response = $this->secureFileService->downloadSecureFile($document, $user);
            $response->headers->set('Content-Disposition', 'inline');

            return $response;

        } catch (\Exception $e) {
            $this->logger->error('Error previewing secure document', [
                'document_id' => $document->getId(),
                'error' => $e->getMessage(),
                'user_id' => $this->getUser()?->getId(),
            ]);

            return new Response('Erreur lors de la prévisualisation.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Vérification des permissions d'accès à un document
     */
    private function hasAccessToDocument(Document $document, $user): bool
    {
        if (!$user) {
            return false;
        }

        $userRoles = $user->getRoles();

        // Super admin a accès à tout
        if (in_array('ROLE_SUPER_ADMIN', $userRoles)) {
            return true;
        }

        // Admin peut voir les documents de son organisation
        if (in_array('ROLE_ADMIN', $userRoles)) {
            return $document->getOrganization() &&
                   $document->getOrganization() === $user->getOrganization();
        }

        // Manager peut voir les documents de sa société
        if (in_array('ROLE_MANAGER', $userRoles)) {
            return $document->getCompany() &&
                   $document->getCompany() === $user->getCompany();
        }

        // Tenant peut voir ses propres documents
        if (in_array('ROLE_TENANT', $userRoles)) {
            return $document->getTenant() &&
                   $document->getTenant() === $user->getTenant();
        }

        // Owner peut voir les documents de ses propriétés
        if (in_array('ROLE_OWNER', $userRoles)) {
            return $document->getOwner() &&
                   $document->getOwner() === $user->getOwner();
        }

        return false;
    }

    /**
     * Statistiques d'accès aux documents (pour les admins)
     */
    #[Route('/access-stats', name: 'app_secure_document_stats', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function accessStats(Request $request): Response
    {
        // Cette méthode pourrait être étendue pour afficher des statistiques
        // d'accès aux documents basées sur les logs

        return $this->render('secure_document/stats.html.twig', [
            'title' => 'Statistiques d\'accès aux documents',
        ]);
    }
}
