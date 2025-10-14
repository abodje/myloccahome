<?php

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service pour gérer l'audit log / historique des actions
 */
class AuditLogService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private RequestStack $requestStack
    ) {
    }

    /**
     * Enregistre une action dans l'audit log
     */
    public function log(
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): AuditLog {
        $auditLog = new AuditLog();

        // Récupérer l'utilisateur connecté
        $user = $this->security->getUser();
        if ($user instanceof User) {
            $auditLog->setUser($user);

            // Récupérer organization/company de l'utilisateur
            if (method_exists($user, 'getOrganization') && $user->getOrganization()) {
                $auditLog->setOrganization($user->getOrganization());
            }
            if (method_exists($user, 'getCompany') && $user->getCompany()) {
                $auditLog->setCompany($user->getCompany());
            }
        }

        $auditLog->setAction($action);
        $auditLog->setEntityType($entityType);
        $auditLog->setEntityId($entityId);
        $auditLog->setDescription($description);
        $auditLog->setOldValues($oldValues);
        $auditLog->setNewValues($newValues);

        // Récupérer les informations de la requête
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $auditLog->setIpAddress($request->getClientIp());
            $auditLog->setUserAgent($request->headers->get('User-Agent'));
        }

        $this->entityManager->persist($auditLog);
        $this->entityManager->flush();

        return $auditLog;
    }

    /**
     * Log une création
     */
    public function logCreate(string $entityType, int $entityId, ?string $description = null, ?array $data = null): AuditLog
    {
        return $this->log('CREATE', $entityType, $entityId, $description, null, $data);
    }

    /**
     * Log une modification
     */
    public function logUpdate(string $entityType, int $entityId, ?string $description = null, ?array $oldValues = null, ?array $newValues = null): AuditLog
    {
        return $this->log('UPDATE', $entityType, $entityId, $description, $oldValues, $newValues);
    }

    /**
     * Log une suppression
     */
    public function logDelete(string $entityType, int $entityId, ?string $description = null, ?array $data = null): AuditLog
    {
        return $this->log('DELETE', $entityType, $entityId, $description, $data, null);
    }

    /**
     * Log une consultation
     */
    public function logView(string $entityType, int $entityId, ?string $description = null): AuditLog
    {
        return $this->log('VIEW', $entityType, $entityId, $description);
    }

    /**
     * Log une connexion
     */
    public function logLogin(?string $email = null): AuditLog
    {
        $description = $email ? "Connexion de {$email}" : "Connexion réussie";
        return $this->log('LOGIN', 'User', null, $description);
    }

    /**
     * Log une déconnexion
     */
    public function logLogout(): AuditLog
    {
        return $this->log('LOGOUT', 'User', null, "Déconnexion");
    }

    /**
     * Log un téléchargement
     */
    public function logDownload(string $entityType, int $entityId, ?string $fileName = null): AuditLog
    {
        $description = $fileName ? "Téléchargement de {$fileName}" : "Téléchargement";
        return $this->log('DOWNLOAD', $entityType, $entityId, $description);
    }

    /**
     * Log un export
     */
    public function logExport(string $entityType, string $format = 'PDF', ?string $description = null): AuditLog
    {
        $desc = $description ?: "Export {$entityType} en {$format}";
        return $this->log('EXPORT', $entityType, null, $desc);
    }

    /**
     * Log un envoi d'email
     */
    public function logEmailSent(string $to, string $subject): AuditLog
    {
        return $this->log('SEND_EMAIL', 'Email', null, "Email envoyé à {$to}: {$subject}");
    }

    /**
     * Log un envoi de SMS
     */
    public function logSmsSent(string $to, ?string $message = null): AuditLog
    {
        $description = "SMS envoyé à {$to}";
        if ($message) {
            $description .= ": " . substr($message, 0, 50);
        }
        return $this->log('SEND_SMS', 'SMS', null, $description);
    }

    /**
     * Extrait les changements entre deux ensembles de données
     */
    public function extractChanges(array $oldData, array $newData): array
    {
        $changes = [];

        foreach ($newData as $key => $newValue) {
            $oldValue = $oldData[$key] ?? null;

            // Ignorer les champs non modifiés
            if ($oldValue === $newValue) {
                continue;
            }

            // Ignorer certains champs sensibles
            if (in_array($key, ['password', 'token', 'salt', 'updatedAt'])) {
                continue;
            }

            $changes[$key] = [
                'old' => $oldValue,
                'new' => $newValue
            ];
        }

        return $changes;
    }

    /**
     * Format les changements pour l'affichage
     */
    public function formatChanges(array $oldValues, array $newValues): string
    {
        $changes = [];

        foreach ($newValues as $field => $newValue) {
            $oldValue = $oldValues[$field] ?? 'N/A';

            if ($oldValue != $newValue) {
                $changes[] = "{$field}: {$oldValue} → {$newValue}";
            }
        }

        return implode(', ', $changes);
    }

    /**
     * Nettoyage automatique des vieux logs
     */
    public function cleanOldLogs(int $daysToKeep = 90): int
    {
        $date = new \DateTime("-{$daysToKeep} days");
        $repository = $this->entityManager->getRepository(AuditLog::class);

        return $repository->deleteOlderThan($date);
    }

    /**
     * Récupère l'historique pour une entité
     */
    public function getEntityHistory(string $entityType, int $entityId, int $limit = 50): array
    {
        $repository = $this->entityManager->getRepository(AuditLog::class);
        return $repository->findByEntity($entityType, $entityId, $limit);
    }

    /**
     * Récupère les statistiques d'activité
     */
    public function getActivityStats(\DateTime $startDate = null, \DateTime $endDate = null): array
    {
        if (!$startDate) {
            $startDate = new \DateTime('-30 days');
        }
        if (!$endDate) {
            $endDate = new \DateTime();
        }

        $repository = $this->entityManager->getRepository(AuditLog::class);

        return [
            'by_action' => $repository->getActivityStats($startDate, $endDate),
            'by_entity' => $repository->countByEntityType($startDate, $endDate),
            'most_active_users' => $repository->getMostActiveUsers(10),
            'total_count' => $repository->countAll(),
            'today_count' => count($repository->findToday())
        ];
    }
}

