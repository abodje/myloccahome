<?php

namespace App\Entity;

use App\Repository\SubscriptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Abonnement d'une organisation à un plan
 */
#[ORM\Entity(repositoryClass: SubscriptionRepository::class)]
class Subscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Organization::class, inversedBy: 'subscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Organization $organization = null;

    #[ORM\ManyToOne(targetEntity: Plan::class, inversedBy: 'subscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Plan $plan = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null; // ACTIVE, CANCELLED, EXPIRED, PENDING

    #[ORM\Column(length: 50)]
    private ?string $billingCycle = null; // MONTHLY, YEARLY

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null; // Montant payé

    #[ORM\Column(length: 10)]
    private ?string $currency = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $endDate = null; // Date de fin de l'abonnement

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $nextBillingDate = null; // Prochaine date de facturation

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $cancelledAt = null; // Date d'annulation

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $trialEndDate = null; // Date de fin de période d'essai

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cancellationReason = null;

    #[ORM\Column(nullable: true)]
    private ?bool $autoRenew = null; // Renouvellement automatique

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $paymentTransactionId = null; // Référence de paiement

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $paymentMethod = null; // CINETPAY, BANK_TRANSFER, etc.

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastPaymentDate = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null; // Données supplémentaires

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = 'PENDING';
        $this->autoRenew = true;
        $this->metadata = [];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): static
    {
        $this->organization = $organization;
        return $this;
    }

    public function getPlan(): ?Plan
    {
        return $this->plan;
    }

    public function setPlan(?Plan $plan): static
    {
        $this->plan = $plan;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getBillingCycle(): ?string
    {
        return $this->billingCycle;
    }

    public function setBillingCycle(string $billingCycle): static
    {
        $this->billingCycle = $billingCycle;
        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getNextBillingDate(): ?\DateTimeInterface
    {
        return $this->nextBillingDate;
    }

    public function setNextBillingDate(?\DateTimeInterface $nextBillingDate): static
    {
        $this->nextBillingDate = $nextBillingDate;
        return $this;
    }

    public function getCancelledAt(): ?\DateTimeInterface
    {
        return $this->cancelledAt;
    }

    public function setCancelledAt(?\DateTimeInterface $cancelledAt): static
    {
        $this->cancelledAt = $cancelledAt;
        return $this;
    }

    public function getTrialEndDate(): ?\DateTimeInterface
    {
        return $this->trialEndDate;
    }

    public function setTrialEndDate(?\DateTimeInterface $trialEndDate): static
    {
        $this->trialEndDate = $trialEndDate;
        return $this;
    }

    public function getCancellationReason(): ?string
    {
        return $this->cancellationReason;
    }

    public function setCancellationReason(?string $cancellationReason): static
    {
        $this->cancellationReason = $cancellationReason;
        return $this;
    }

    public function isAutoRenew(): ?bool
    {
        return $this->autoRenew;
    }

    public function setAutoRenew(?bool $autoRenew): static
    {
        $this->autoRenew = $autoRenew;
        return $this;
    }

    public function getPaymentTransactionId(): ?string
    {
        return $this->paymentTransactionId;
    }

    public function setPaymentTransactionId(?string $paymentTransactionId): static
    {
        $this->paymentTransactionId = $paymentTransactionId;
        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function getLastPaymentDate(): ?\DateTimeInterface
    {
        return $this->lastPaymentDate;
    }

    public function setLastPaymentDate(?\DateTimeInterface $lastPaymentDate): static
    {
        $this->lastPaymentDate = $lastPaymentDate;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    // Cette méthode a été supprimée car elle faisait référence à une propriété inexistante
    // Les abonnements sont gérés via la relation avec Organization

    /**
     * Vérifie si l'abonnement est actif
     */
    public function isActive(): bool
    {
        return $this->status === 'ACTIVE' && 
               $this->endDate && 
               $this->endDate > new \DateTime();
    }

    /**
     * Vérifie si l'abonnement expire bientôt (dans les 7 jours)
     */
    public function isExpiringSoon(): bool
    {
        if (!$this->endDate) {
            return false;
        }

        $now = new \DateTime();
        $in7Days = (clone $now)->modify('+7 days');
        
        return $this->endDate > $now && $this->endDate <= $in7Days;
    }

    /**
     * Vérifie si l'abonnement est expiré
     */
    public function isExpired(): bool
    {
        return $this->endDate && $this->endDate <= new \DateTime();
    }

    /**
     * Calcule le nombre de jours restants
     */
    public function getDaysRemaining(): int
    {
        if (!$this->endDate) {
            return 0;
        }

        $now = new \DateTime();
        $diff = $now->diff($this->endDate);
        
        return $diff->invert ? 0 : $diff->days;
    }

    /**
     * Active l'abonnement
     */
    public function activate(): static
    {
        $this->status = 'ACTIVE';
        $this->startDate = new \DateTime();
        
        // Calculer la date de fin selon le cycle
        if ($this->billingCycle === 'yearly') {
            $startDate = new \DateTime($this->startDate->format('Y-m-d'));
            $this->endDate = $startDate->modify('+1 year');
            $this->nextBillingDate = clone $this->endDate;
        } else {
            $startDate = new \DateTime($this->startDate->format('Y-m-d'));
            $this->endDate = $startDate->modify('+1 month');
            $this->nextBillingDate = clone $this->endDate;
        }

        return $this;
    }

    /**
     * Renouvelle l'abonnement
     */
    public function renew(): static
    {
        if ($this->billingCycle === 'yearly') {
            $endDate = new \DateTime($this->endDate->format('Y-m-d'));
            $this->endDate = $endDate->modify('+1 year');
        } else {
            $endDate = new \DateTime($this->endDate->format('Y-m-d'));
            $this->endDate = $endDate->modify('+1 month');
        }

        $this->nextBillingDate = clone $this->endDate;
        $this->status = 'ACTIVE';
        $this->updatedAt = new \DateTime();

        return $this;
    }

    /**
     * Annule l'abonnement
     */
    public function cancel(string $reason = null): static
    {
        $this->status = 'CANCELLED';
        $this->cancelledAt = new \DateTime();
        $this->cancellationReason = $reason;
        $this->autoRenew = false;
        $this->updatedAt = new \DateTime();

        return $this;
    }

    /**
     * Marque l'abonnement comme expiré
     */
    public function markAsExpired(): static
    {
        $this->status = 'EXPIRED';
        $this->updatedAt = new \DateTime();

        return $this;
    }
}

