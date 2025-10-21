<?php

namespace App\Service;

use App\Entity\MaintenanceRequest;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service pour l'attribution automatique des demandes de maintenance
 */
class MaintenanceAssignmentService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private NotificationService $notificationService,
        private OrangeSmsService $orangeSmsService,
        private SettingsService $settingsService
    ) {
    }

    /**
     * Attribue automatiquement une demande de maintenance à un intervenant
     */
    public function autoAssign(MaintenanceRequest $request): ?User
    {
        // Critères de sélection :
        // 1. Utilisateurs avec rôle ROLE_MANAGER ou ROLE_ADMIN
        // 2. Disponibles (pas trop de demandes en cours)
        // 3. Priorité aux managers responsables de la propriété

        $property = $request->getProperty();

        // Si la propriété a un propriétaire avec un user manager
        if ($property && $property->getOwner() && $property->getOwner()->getUser()) {
            $owner = $property->getOwner();
            if ($owner->getUser() && $this->hasManagerRole($owner->getUser())) {
                $this->assignToUser($request, $owner->getUser());
                return $owner->getUser();
            }
        }

        // Sinon, trouver un manager disponible
        $availableManager = $this->findAvailableManager();
        if ($availableManager) {
            $this->assignToUser($request, $availableManager);
            return $availableManager;
        }

        // En dernier recours, assigner à un admin
        $admin = $this->findFirstAdmin();
        if ($admin) {
            $this->assignToUser($request, $admin);
            return $admin;
        }

        return null;
    }

    /**
     * Assigne une demande à un utilisateur spécifique
     */
    private function assignToUser(MaintenanceRequest $request, User $user): void
    {
        // Note: Vous devrez peut-être ajouter un champ "assignedTo" dans MaintenanceRequest
        // Pour l'instant, on envoie juste une notification

        $request->setStatus('En cours');
        $this->entityManager->flush();

        // Envoyer notification à l'intervenant
        $this->notificationService->notifyMaintenanceAssignment($request, $user);
    }

    /**
     * Trouve un manager disponible (avec le moins de demandes en cours)
     */
    private function findAvailableManager(): ?User
    {
        $managers = $this->userRepository->findByRole('ROLE_MANAGER');

        if (empty($managers)) {
            return null;
        }

        // Pour l'instant, retourner le premier manager
        // TODO: Implémenter la logique de charge de travail
        return $managers[0];
    }

    /**
     * Trouve le premier admin
     */
    private function findFirstAdmin(): ?User
    {
        $admins = $this->userRepository->findByRole('ROLE_ADMIN');
        return !empty($admins) ? $admins[0] : null;
    }

    /**
     * Vérifie si un utilisateur a le rôle manager
     */
    private function hasManagerRole(User $user): bool
    {
        return in_array('ROLE_MANAGER', $user->getRoles()) ||
               in_array('ROLE_ADMIN', $user->getRoles());
    }

    /**
     * Traite toutes les demandes non assignées
     */
    public function processUnassignedRequests(): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        $unassignedRequests = $qb->select('m')
            ->from(MaintenanceRequest::class, 'm')
            ->where('m.status = :status')
            ->setParameter('status', 'Nouvelle')
            ->getQuery()
            ->getResult();

        $assignedCount = 0;
        foreach ($unassignedRequests as $request) {
            if ($this->autoAssign($request)) {
                $assignedCount++;
            }
        }

        return $assignedCount;
    }

    /**
     * Envoie des notifications pour les demandes urgentes non traitées
     */
    public function notifyUrgentRequests(): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        $urgentRequests = $qb->select('m')
            ->from(MaintenanceRequest::class, 'm')
            ->where('m.priority = :priority')
            ->andWhere('m.status != :completed')
            ->setParameter('priority', 'Urgente')
            ->setParameter('completed', 'Terminée')
            ->getQuery()
            ->getResult();

        $notifiedCount = 0;
        foreach ($urgentRequests as $request) {
            $this->notificationService->sendUrgentMaintenanceAlert($request);

            // Envoyer SMS si activé
            if ($this->settingsService->get('orange_sms_enabled', false)) {
                $this->sendUrgentMaintenanceSms($request);
            }

            $notifiedCount++;
        }

        return $notifiedCount;
    }

    /**
     * Envoie un SMS pour une demande de maintenance urgente
     */
    private function sendUrgentMaintenanceSms(MaintenanceRequest $request): void
    {
        try {
            // Notifier le propriétaire/gestionnaire
            $owner = $request->getProperty()->getOwner();

            if ($owner && $owner->getPhone()) {
                $message = sprintf(
                    "URGENT LOKAPRO: Demande maintenance a %s. Priorite: %s. Voir details sur app.lokapro.tech",
                    substr($request->getProperty()->getAddress(), 0, 30),
                    $request->getPriority()
                );

                // Limiter à 160 caractères
                if (strlen($message) > 160) {
                    $message = substr($message, 0, 157) . '...';
                }

                $this->orangeSmsService->envoyerSms($owner->getPhone(), $message);
            }

            // Notifier aussi le locataire si demande urgente
            if ($request->getTenant() && $request->getTenant()->getPhone()) {
                $tenant = $request->getTenant();
                $message = sprintf(
                    "LOKAPRO: Votre demande urgente #%d a ete prise en compte. Intervention prevue sous 24h.",
                    $request->getId()
                );

                $this->orangeSmsService->envoyerSms($tenant->getPhone(), $message);
            }
        } catch (\Exception $e) {
            // Logger l'erreur mais ne pas bloquer le processus
            error_log("Erreur envoi SMS maintenance: " . $e->getMessage());
        }
    }

    /**
     * Vérifie les demandes en retard et envoie des alertes
     */
    public function checkOverdueRequests(): int
    {
        $now = new \DateTime();

        $qb = $this->entityManager->createQueryBuilder();
        $overdueRequests = $qb->select('m')
            ->from(MaintenanceRequest::class, 'm')
            ->where('m.scheduledDate < :now')
            ->andWhere('m.status != :completed')
            ->setParameter('now', $now)
            ->setParameter('completed', 'Terminée')
            ->getQuery()
            ->getResult();

        $notifiedCount = 0;
        foreach ($overdueRequests as $request) {
            $request->setStatus('En retard');
            $this->notificationService->sendOverdueMaintenanceAlert($request);
            $notifiedCount++;
        }

        if ($notifiedCount > 0) {
            $this->entityManager->flush();
        }

        return $notifiedCount;
    }
}

