<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\User;
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
        $user = $this->getUser();

        // Log de la tentative de téléchargement
        $userInfo = $this->getUserInfo($user);
        $this->logger->info('Tentative de téléchargement sécurisé', [
            'document_id' => $document->getId(),
            'document_name' => $document->getName(),
            'user_id' => $userInfo['id'],
            'user_email' => $userInfo['email'],
            'user_roles' => $userInfo['roles'],
            'ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
        ]);

        try {
            // Vérification de l'authentification
            if (!$user) {
                $this->logger->warning('Tentative de téléchargement sans authentification', [
                    'document_id' => $document->getId(),
                    'ip' => $request->getClientIp(),
                ]);
                throw $this->createAccessDeniedException('Vous devez être connecté pour télécharger ce document.');
            }

            // Vérification supplémentaire des permissions
            if (!$this->hasAccessToDocument($document, $user)) {
                $this->logger->warning('Tentative d\'accès non autorisé à un document', [
                    'user_id' => $userInfo['id'],
                    'user_email' => $userInfo['email'],
                    'user_roles' => $userInfo['roles'],
                    'document_id' => $document->getId(),
                    'document_name' => $document->getName(),
                    'document_organization' => $document->getOrganization()?->getName(),
                    'user_organization' => $userInfo['organization_name'],
                    'ip' => $request->getClientIp(),
                ]);

                throw $this->createAccessDeniedException('Accès non autorisé à ce document.');
            }

            // Log du téléchargement autorisé
            $this->logger->info('Téléchargement autorisé', [
                'document_id' => $document->getId(),
                'user_id' => $userInfo['id'],
                'file_name' => $document->getFileName(),
            ]);

            return $this->secureFileService->downloadSecureFile($document, $user);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du téléchargement sécurisé', [
                'document_id' => $document->getId(),
                'document_name' => $document->getName(),
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'user_id' => $userInfo['id'],
                'user_email' => $userInfo['email'],
                'ip' => $request->getClientIp(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Ne pas rediriger pour les erreurs d'accès, laisser Symfony gérer
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException) {
                throw $e;
            }

            $this->addFlash('error', 'Erreur lors du téléchargement du document : ' . $e->getMessage());
            return $this->redirectToRoute('app_document_index');
        }
    }

    /**
     * Prévisualisation sécurisée d'un document (pour les images)
     */
    #[Route('/{id}/preview', name: 'app_secure_document_preview', methods: ['GET'])]
    public function preview(Document $document, Request $request): Response
    {
        $user = $this->getUser();
        $userInfo = $this->getUserInfo($user);

        $this->logger->info('Tentative de prévisualisation sécurisée', [
            'document_id' => $document->getId(),
            'document_name' => $document->getName(),
            'mime_type' => $document->getMimeType(),
            'user_id' => $userInfo['id'],
            'user_email' => $userInfo['email'],
            'ip' => $request->getClientIp(),
        ]);

        try {
            // Vérification de l'authentification
            if (!$user) {
                $this->logger->warning('Tentative de prévisualisation sans authentification', [
                    'document_id' => $document->getId(),
                    'ip' => $request->getClientIp(),
                ]);
                throw $this->createAccessDeniedException('Vous devez être connecté pour prévisualiser ce document.');
            }

            // Vérification des permissions
            if (!$this->hasAccessToDocument($document, $user)) {
                $this->logger->warning('Tentative d\'accès non autorisé à la prévisualisation', [
                    'user_id' => $userInfo['id'],
                    'document_id' => $document->getId(),
                    'ip' => $request->getClientIp(),
                ]);
                throw $this->createAccessDeniedException('Accès non autorisé à ce document.');
            }

            // Seules les images peuvent être prévisualisées
            $imageMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($document->getMimeType(), $imageMimeTypes)) {
                $this->logger->info('Tentative de prévisualisation d\'un type non supporté', [
                    'document_id' => $document->getId(),
                    'mime_type' => $document->getMimeType(),
                    'user_id' => $userInfo['id'],
                ]);
                throw $this->createNotFoundException('Prévisualisation non disponible pour ce type de fichier.');
            }

            $this->logger->info('Prévisualisation autorisée', [
                'document_id' => $document->getId(),
                'user_id' => $userInfo['id'],
                'mime_type' => $document->getMimeType(),
            ]);

            $response = $this->secureFileService->downloadSecureFile($document, $user);
            $response->headers->set('Content-Disposition', 'inline');
            $response->headers->set('Cache-Control', 'private, max-age=3600');

            return $response;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la prévisualisation sécurisée', [
                'document_id' => $document->getId(),
                'document_name' => $document->getName(),
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'user_id' => $userInfo['id'],
                'user_email' => $userInfo['email'],
                'ip' => $request->getClientIp(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Ne pas rediriger pour les erreurs d'accès, laisser Symfony gérer
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException ||
                $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                throw $e;
            }

            return new Response('Erreur lors de la prévisualisation.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Helper pour obtenir les informations utilisateur de manière sécurisée
     */
    private function getUserInfo($user): array
    {
        if (!$user) {
            return [
                'id' => null,
                'email' => null,
                'roles' => [],
                'organization_id' => null,
                'organization_name' => null,
                'company_id' => null,
                'tenant_id' => null,
                'owner_id' => null,
            ];
        }

        return [
            'id' => method_exists($user, 'getId') ? $user->getId() : null,
            'email' => method_exists($user, 'getEmail') ? $user->getEmail() : null,
            'roles' => method_exists($user, 'getRoles') ? $user->getRoles() : [],
            'organization_id' => method_exists($user, 'getOrganization') && $user->getOrganization() ? $user->getOrganization()->getId() : null,
            'organization_name' => method_exists($user, 'getOrganization') && $user->getOrganization() ? $user->getOrganization()->getName() : null,
            'company_id' => method_exists($user, 'getCompany') && $user->getCompany() ? $user->getCompany()->getId() : null,
            'tenant_id' => method_exists($user, 'getTenant') && $user->getTenant() ? $user->getTenant()->getId() : null,
            'owner_id' => method_exists($user, 'getOwner') && $user->getOwner() ? $user->getOwner()->getId() : null,
        ];
    }

    /**
     * Vérification des permissions d'accès à un document
     */
    private function hasAccessToDocument(Document $document, User $user): bool
    {
        if (!$user) {
            return false;
        }

        $userRoles = $user->getRoles();
        $documentOrg = $document->getOrganization();
        $userOrg = $user->getOrganization();

        // Super admin a accès à tout
        if (in_array('ROLE_SUPER_ADMIN', $userRoles)) {
            $this->logger->debug('Accès autorisé : Super admin', [
                'user_id' => $user->getId(),
                'document_id' => $document->getId(),
            ]);
            return true;
        }

        // Admin peut voir les documents de son organisation
        if (in_array('ROLE_ADMIN', $userRoles)) {
            $hasAccess = $documentOrg && $userOrg && $documentOrg->getId() === $userOrg->getId();
            $this->logger->debug('Vérification accès Admin', [
                'user_id' => $user->getId(),
                'user_org_id' => $userOrg?->getId(),
                'document_id' => $document->getId(),
                'document_org_id' => $documentOrg?->getId(),
                'has_access' => $hasAccess,
            ]);
            return $hasAccess;
        }

        // Manager peut voir les documents de sa société
        if (in_array('ROLE_MANAGER', $userRoles)) {
            $documentCompany = $document->getCompany();
            $userCompany = $user->getCompany();
            $hasAccess = $documentCompany && $userCompany && $documentCompany->getId() === $userCompany->getId();
            $this->logger->debug('Vérification accès Manager', [
                'user_id' => $user->getId(),
                'user_company_id' => $userCompany?->getId(),
                'document_id' => $document->getId(),
                'document_company_id' => $documentCompany?->getId(),
                'has_access' => $hasAccess,
            ]);
            return $hasAccess;
        }

        // Tenant peut voir ses propres documents
        if (in_array('ROLE_TENANT', $userRoles)) {
            $documentTenant = $document->getTenant();
            $userTenant = $user->getTenant();
            $hasAccess = $documentTenant && $userTenant && $documentTenant->getId() === $userTenant->getId();
            $this->logger->debug('Vérification accès Tenant', [
                'user_id' => $user->getId(),
                'user_tenant_id' => $userTenant?->getId(),
                'document_id' => $document->getId(),
                'document_tenant_id' => $documentTenant?->getId(),
                'has_access' => $hasAccess,
            ]);
            return $hasAccess;
        }

        // Owner peut voir les documents de ses propriétés
        if (in_array('ROLE_OWNER', $userRoles)) {
            $documentOwner = $document->getOwner();
            $userOwner = $user->getOwner();
            $hasAccess = $documentOwner && $userOwner && $documentOwner->getId() === $userOwner->getId();
            $this->logger->debug('Vérification accès Owner', [
                'user_id' => $user->getId(),
                'user_owner_id' => $userOwner?->getId(),
                'document_id' => $document->getId(),
                'document_owner_id' => $documentOwner?->getId(),
                'has_access' => $hasAccess,
            ]);
            return $hasAccess;
        }

        // Utilisateur standard peut voir les documents de son organisation
        if ($documentOrg && $userOrg && $documentOrg->getId() === $userOrg->getId()) {
            $this->logger->debug('Accès autorisé : même organisation', [
                'user_id' => $user->getId(),
                'user_org_id' => $userOrg->getId(),
                'document_id' => $document->getId(),
                'document_org_id' => $documentOrg->getId(),
            ]);
            return true;
        }

        $this->logger->debug('Accès refusé : aucune règle correspondante', [
            'user_id' => $user->getId(),
            'user_roles' => $userRoles,
            'document_id' => $document->getId(),
            'document_org_id' => $documentOrg?->getId(),
            'user_org_id' => $userOrg?->getId(),
        ]);

        return false;
    }

    /**
     * Diagnostic des permissions d'accès (pour le débogage)
     */
    #[Route('/{id}/debug-access', name: 'app_secure_document_debug_access', methods: ['GET'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function debugAccess(Document $document, Request $request): Response
    {
        $user = $this->getUser();
        $userInfo = $this->getUserInfo($user);

        $debugInfo = [
            'document' => [
                'id' => $document->getId(),
                'name' => $document->getName(),
                'file_name' => $document->getFileName(),
                'mime_type' => $document->getMimeType(),
                'organization_id' => $document->getOrganization()?->getId(),
                'organization_name' => $document->getOrganization()?->getName(),
                'company_id' => $document->getCompany()?->getId(),
                'tenant_id' => $document->getTenant()?->getId(),
                'owner_id' => $document->getOwner()?->getId(),
            ],
            'user' => $userInfo,
            'access_check' => [
                'has_access' => $this->hasAccessToDocument($document, $user),
                'file_exists' => file_exists($this->secureFileService->getDocumentsDirectory() . '/' . $document->getFileName()),
                'file_size' => file_exists($this->secureFileService->getDocumentsDirectory() . '/' . $document->getFileName())
                    ? filesize($this->secureFileService->getDocumentsDirectory() . '/' . $document->getFileName())
                    : 0,
            ],
            'request' => [
                'ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('User-Agent'),
                'referer' => $request->headers->get('Referer'),
            ]
        ];

        return $this->json($debugInfo);
    }
}
