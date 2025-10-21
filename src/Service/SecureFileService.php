<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Document;
use App\Entity\User;
use Psr\Log\LoggerInterface;

/**
 * Service de sécurisation des fichiers uploadés
 */
class SecureFileService
{
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png',
        'image/gif',
        'text/plain',
    ];

    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    private const ENCRYPTION_METHOD = 'AES-256-CBC';

    public function __construct(
        private string $documentsDirectory,
        private SluggerInterface $slugger,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private string $encryptionKey
    ) {
    }

    /**
     * Upload sécurisé d'un fichier
     */
    public function uploadSecureFile(UploadedFile $uploadedFile, Document $document, ?User $user = null): string
    {
        // Validation de sécurité
        $this->validateFileSecurity($uploadedFile);

        // Génération d'un nom de fichier sécurisé
        $secureFilename = $this->generateSecureFilename($uploadedFile);

        // Chiffrement du fichier
        $encryptedContent = $this->encryptFile($uploadedFile->getPathname());

        // Sauvegarde sécurisée
        $filePath = $this->documentsDirectory . '/' . $secureFilename;
        $this->ensureDirectoryExists();

        if (file_put_contents($filePath, $encryptedContent) === false) {
            throw new \RuntimeException('Impossible de sauvegarder le fichier.');
        }

        // Log de l'upload
        $this->logFileAccess('UPLOAD', $secureFilename, $user, $document);

        return $secureFilename;
    }

    /**
     * Téléchargement sécurisé d'un fichier
     */
    public function downloadSecureFile(Document $document, ?User $user = null): Response
    {
        $filePath = $this->documentsDirectory . '/' . $document->getFileName();

        if (!file_exists($filePath)) {
            throw new \RuntimeException('Le fichier n\'existe pas.');
        }

        // Vérification des permissions d'accès
        $this->checkAccessPermissions($document, $user);

        // Déchiffrement du fichier
        $decryptedContent = $this->decryptFile($filePath);

        // Log du téléchargement
        $this->logFileAccess('DOWNLOAD', $document->getFileName(), $user, $document);

        // Création de la réponse
        $response = new Response($decryptedContent);
        $response->headers->set('Content-Type', $document->getMimeType());
        $response->headers->set('Content-Disposition',
            ResponseHeaderBag::DISPOSITION_ATTACHMENT . '; filename="' . $document->getOriginalFileName() . '"'
        );
        $response->headers->set('Content-Length', strlen($decryptedContent));

        return $response;
    }

    /**
     * Validation de sécurité du fichier
     */
    private function validateFileSecurity(UploadedFile $file): void
    {
        // Vérification de la taille
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException('Le fichier est trop volumineux.');
        }

        // Vérification du type MIME
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
            throw new \InvalidArgumentException('Type de fichier non autorisé.');
        }

        // Vérification de l'extension
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif', 'txt'];
        if (!in_array($extension, $allowedExtensions)) {
            throw new \InvalidArgumentException('Extension de fichier non autorisée.');
        }

        // Vérification du contenu du fichier (détection de malware basique)
        $this->scanFileContent($file->getPathname());
    }

    /**
     * Génération d'un nom de fichier sécurisé
     */
    private function generateSecureFilename(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);

        // Ajout d'un hash sécurisé et d'un timestamp
        $hash = hash('sha256', uniqid() . microtime(true));
        $timestamp = date('YmdHis');

        return sprintf(
            '%s_%s_%s.%s',
            $safeFilename,
            $timestamp,
            substr($hash, 0, 16),
            $file->guessExtension()
        );
    }

    /**
     * Chiffrement d'un fichier
     */
    private function encryptFile(string $filePath): string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException('Impossible de lire le fichier.');
        }

        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($content, self::ENCRYPTION_METHOD, $this->encryptionKey, 0, $iv);

        if ($encrypted === false) {
            throw new \RuntimeException('Erreur lors du chiffrement.');
        }

        return base64_encode($iv . $encrypted);
    }

    /**
     * Déchiffrement d'un fichier
     */
    private function decryptFile(string $filePath): string
    {
        $encryptedContent = file_get_contents($filePath);
        if ($encryptedContent === false) {
            throw new \RuntimeException('Impossible de lire le fichier chiffré.');
        }

        $data = base64_decode($encryptedContent);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);

        $decrypted = openssl_decrypt($encrypted, self::ENCRYPTION_METHOD, $this->encryptionKey, 0, $iv);

        if ($decrypted === false) {
            throw new \RuntimeException('Erreur lors du déchiffrement.');
        }

        return $decrypted;
    }

    /**
     * Vérification des permissions d'accès
     */
    private function checkAccessPermissions(Document $document, ?User $user): void
    {
        if (!$user) {
            throw new \RuntimeException('Accès non autorisé.');
        }

        // Vérification selon le rôle de l'utilisateur
        $userRoles = $user->getRoles();

        if (in_array('ROLE_SUPER_ADMIN', $userRoles)) {
            return; // Super admin a accès à tout
        }

        if (in_array('ROLE_ADMIN', $userRoles)) {
            // Admin peut voir les documents de son organisation
            if ($document->getOrganization() && $document->getOrganization() === $user->getOrganization()) {
                return;
            }
        }

        if (in_array('ROLE_MANAGER', $userRoles)) {
            // Manager peut voir les documents de sa société
            if ($document->getCompany() && $document->getCompany() === $user->getCompany()) {
                return;
            }
        }

        if (in_array('ROLE_TENANT', $userRoles)) {
            // Tenant peut voir ses propres documents
            if ($document->getTenant() && $document->getTenant() === $user->getTenant()) {
                return;
            }
        }

        throw new \RuntimeException('Accès non autorisé à ce document.');
    }

    /**
     * Scan basique du contenu du fichier
     */
    private function scanFileContent(string $filePath): void
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return;
        }

        // Détection de patterns suspects
        $suspiciousPatterns = [
            '/<script/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload=/i',
            '/onerror=/i',
            '/eval\(/i',
            '/exec\(/i',
            '/system\(/i',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                throw new \InvalidArgumentException('Contenu suspect détecté dans le fichier.');
            }
        }
    }

    /**
     * Log des accès aux fichiers
     */
    private function logFileAccess(string $action, string $filename, ?User $user, Document $document): void
    {
        $logData = [
            'action' => $action,
            'filename' => $filename,
            'user_id' => $user?->getId(),
            'user_email' => $user?->getEmail(),
            'document_id' => $document->getId(),
            'document_name' => $document->getName(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $this->logger->info('File access', $logData);
    }

    /**
     * S'assurer que le répertoire existe
     */
    private function ensureDirectoryExists(): void
    {
        if (!is_dir($this->documentsDirectory)) {
            mkdir($this->documentsDirectory, 0750, true);
        }
    }

    /**
     * Suppression sécurisée d'un fichier
     */
    public function deleteSecureFile(string $filename): void
    {
        $filePath = $this->documentsDirectory . '/' . $filename;

        if (file_exists($filePath)) {
            // Écraser le fichier avant suppression
            $this->secureDelete($filePath);
        }
    }

    /**
     * Suppression sécurisée (écrasement des données)
     */
    private function secureDelete(string $filePath): void
    {
        $fileSize = filesize($filePath);

        // Écrire des données aléatoires plusieurs fois
        for ($i = 0; $i < 3; $i++) {
            $randomData = random_bytes($fileSize);
            file_put_contents($filePath, $randomData);
        }

        // Supprimer le fichier
        unlink($filePath);
    }
}
