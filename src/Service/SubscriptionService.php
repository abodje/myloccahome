<?php

namespace App\Service;

use App\Entity\Organization;
use App\Entity\Plan;
use App\Entity\Subscription;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de gestion des abonnements SaaS
 */
class SubscriptionService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Crée un nouvel abonnement pour une organisation
     */
    public function createSubscription(
        Organization $organization,
        Plan $plan,
        string $billingCycle = 'MONTHLY',
        string $paymentMethod = null,
        string $transactionId = null
    ): Subscription {
        $subscription = new Subscription();
        $subscription->setOrganization($organization);
        $subscription->setPlan($plan);
        $subscription->setBillingCycle($billingCycle);

        // Calculer le montant
        $amount = $billingCycle === 'YEARLY' ? $plan->getYearlyPrice() : $plan->getMonthlyPrice();
        $subscription->setAmount($amount);
        $subscription->setCurrency($plan->getCurrency());

        // Définir les dates et statut
        $now = new \DateTime();
        $subscription->setStartDate($now);
        $subscription->setCreatedAt($now);
        $subscription->setStatus('PENDING');
        $subscription->setAutoRenew(true);

        // Date de fin (1 mois ou 1 an selon le cycle)
        $endDate = $billingCycle === 'YEARLY'
            ? (clone $now)->modify('+1 year')
            : (clone $now)->modify('+1 month');
        $subscription->setEndDate($endDate);

        // Prochaine date de facturation
        $subscription->setNextBillingDate($endDate);

        // Enregistrer les informations de paiement
        if ($paymentMethod) {
            $subscription->setPaymentMethod($paymentMethod);
        }
        if ($transactionId) {
            $subscription->setPaymentTransactionId($transactionId);
        }

        $this->entityManager->persist($subscription);

        return $subscription;
    }

    /**
     * Active un abonnement
     */
    public function activateSubscription(Subscription $subscription): void
    {
        $subscription->activate();

        // Mettre à jour l'organisation
        $organization = $subscription->getOrganization();
        $organization->setActiveSubscription($subscription);
        $organization->setStatus('ACTIVE');

        // Appliquer les fonctionnalités du plan
        $this->applyPlanFeatures($organization, $subscription->getPlan());

        $this->entityManager->flush();

        $this->logger->info("Abonnement #{$subscription->getId()} activé pour {$organization->getName()}");
    }

    /**
     * Renouvelle un abonnement
     */
    public function renewSubscription(Subscription $subscription): void
    {
        $subscription->renew();
        $subscription->setLastPaymentDate(new \DateTime());

        $this->entityManager->flush();

        $this->logger->info("Abonnement #{$subscription->getId()} renouvelé pour {$subscription->getOrganization()->getName()}");
    }

    /**
     * Annule un abonnement
     */
    public function cancelSubscription(Subscription $subscription, string $reason = null): void
    {
        $subscription->cancel($reason);

        // L'organisation reste active jusqu'à la fin de la période payée
        // Mais ne se renouvellera pas

        $this->entityManager->flush();

        $this->logger->info("Abonnement #{$subscription->getId()} annulé pour {$subscription->getOrganization()->getName()}");
    }

    /**
     * Vérifie et met à jour les abonnements expirés
     */
    public function checkAndExpireSubscriptions(): array
    {
        $expiredSubscriptions = $this->entityManager
            ->getRepository(Subscription::class)
            ->findExpired();

        $expired = [];
        foreach ($expiredSubscriptions as $subscription) {
            $subscription->markAsExpired();

            // Suspendre l'organisation
            $organization = $subscription->getOrganization();
            $organization->setStatus('SUSPENDED');
            $organization->setActiveSubscription(null);

            $expired[] = $subscription;

            $this->logger->warning("Abonnement #{$subscription->getId()} expiré pour {$organization->getName()}");
        }

        if (count($expired) > 0) {
            $this->entityManager->flush();
        }

        return $expired;
    }

    /**
     * Envoie des alertes pour les abonnements qui expirent bientôt
     */
    public function sendExpirationAlerts(int $days = 7): array
    {
        $expiring = $this->entityManager
            ->getRepository(Subscription::class)
            ->findExpiringSoon($days);

        $alerted = [];
        foreach ($expiring as $subscription) {
            $organization = $subscription->getOrganization();
            $daysRemaining = $subscription->getDaysRemaining();

            // TODO: Envoyer email/SMS d'alerte
            $this->logger->info("Alerte: Abonnement de {$organization->getName()} expire dans {$daysRemaining} jours");

            $alerted[] = $subscription;
        }

        return $alerted;
    }

    /**
     * Applique les fonctionnalités d'un plan à une organisation
     */
    private function applyPlanFeatures(Organization $organization, Plan $plan): void
    {
        $organization->setFeatures($plan->getFeatures());

        // Stocker les limites
        $organization->setSetting('max_properties', $plan->getMaxProperties());
        $organization->setSetting('max_tenants', $plan->getMaxTenants());
        $organization->setSetting('max_users', $plan->getMaxUsers());
        $organization->setSetting('max_documents', $plan->getMaxDocuments());
    }

    /**
     * Vérifie si une organisation peut ajouter une ressource
     */
    public function canAddResource(Organization $organization, string $resourceType): bool
    {
        $maxKey = 'max_' . $resourceType;
        $max = $organization->getSetting($maxKey);

        if ($max === null) {
            return true; // Illimité
        }

        // Compter les ressources actuelles
        $currentCount = match($resourceType) {
            'properties' => $organization->getProperties()->count(),
            'tenants' => $organization->getTenants()->count(),
            'users' => $organization->getUsers()->count(),
            default => 0
        };

        return $currentCount < $max;
    }

    /**
     * Calcule le revenu total
     */
    public function getTotalRevenue(): float
    {
        $result = $this->entityManager
            ->getRepository(Subscription::class)
            ->createQueryBuilder('s')
            ->select('SUM(s.amount)')
            ->where('s.status = :status')
            ->setParameter('status', 'ACTIVE')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }
}

