<?php

namespace App\Entity;

use App\Repository\OnlinePaymentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Représente une transaction de paiement en ligne via CinetPay
 * Lié à un Payment (loyer) ou AdvancePayment (acompte)
 */
#[ORM\Entity(repositoryClass: OnlinePaymentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class OnlinePayment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $transactionId = null;

    #[ORM\Column(length: 20)]
    private ?string $paymentType = null; // 'rent' ou 'advance'

    #[ORM\ManyToOne]
    private ?Lease $lease = null;

    #[ORM\ManyToOne]
    private ?Payment $payment = null; // Si c'est un paiement de loyer

    #[ORM\ManyToOne]
    private ?AdvancePayment $advancePayment = null; // Si c'est un acompte

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(length: 10)]
    private ?string $currency = null;

    #[ORM\Column(length: 50)]
    private ?string $provider = null; // CinetPay

    #[ORM\Column(length: 50)]
    private ?string $paymentMethod = null; // ORANGE_MONEY, MTN_MONEY, MOOV_MONEY, WAVE, CARD

    #[ORM\Column(length: 30)]
    private ?string $status = null; // pending, completed, failed, cancelled

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customerName = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $customerPhone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customerEmail = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $paymentUrl = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $cinetpayResponse = null; // JSON de la réponse CinetPay

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notificationData = null; // JSON de la notification reçue

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $paidAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = 'pending';
        $this->provider = 'CinetPay';
        $this->currency = 'XOF';
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function setTransactionId(string $transactionId): static
    {
        $this->transactionId = $transactionId;
        return $this;
    }

    public function getPaymentType(): ?string
    {
        return $this->paymentType;
    }

    public function setPaymentType(string $paymentType): static
    {
        $this->paymentType = $paymentType;
        return $this;
    }

    public function getLease(): ?Lease
    {
        return $this->lease;
    }

    public function setLease(?Lease $lease): static
    {
        $this->lease = $lease;
        return $this;
    }

    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    public function setPayment(?Payment $payment): static
    {
        $this->payment = $payment;
        return $this;
    }

    public function getAdvancePayment(): ?AdvancePayment
    {
        return $this->advancePayment;
    }

    public function setAdvancePayment(?AdvancePayment $advancePayment): static
    {
        $this->advancePayment = $advancePayment;
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

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(string $provider): static
    {
        $this->provider = $provider;
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getCustomerName(): ?string
    {
        return $this->customerName;
    }

    public function setCustomerName(?string $customerName): static
    {
        $this->customerName = $customerName;
        return $this;
    }

    public function getCustomerPhone(): ?string
    {
        return $this->customerPhone;
    }

    public function setCustomerPhone(?string $customerPhone): static
    {
        $this->customerPhone = $customerPhone;
        return $this;
    }

    public function getCustomerEmail(): ?string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(?string $customerEmail): static
    {
        $this->customerEmail = $customerEmail;
        return $this;
    }

    public function getPaymentUrl(): ?string
    {
        return $this->paymentUrl;
    }

    public function setPaymentUrl(?string $paymentUrl): static
    {
        $this->paymentUrl = $paymentUrl;
        return $this;
    }

    public function getCinetpayResponse(): ?string
    {
        return $this->cinetpayResponse;
    }

    public function setCinetpayResponse(?string $cinetpayResponse): static
    {
        $this->cinetpayResponse = $cinetpayResponse;
        return $this;
    }

    public function getNotificationData(): ?string
    {
        return $this->notificationData;
    }

    public function setNotificationData(?string $notificationData): static
    {
        $this->notificationData = $notificationData;
        return $this;
    }

    public function getPaidAt(): ?\DateTimeInterface
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeInterface $paidAt): static
    {
        $this->paidAt = $paidAt;
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

    /**
     * Marque la transaction comme complétée
     */
    public function markAsCompleted(?string $paymentMethod = null): void
    {
        $this->status = 'completed';
        $this->paidAt = new \DateTime();

        if ($paymentMethod) {
            $this->paymentMethod = $paymentMethod;
        }
    }

    /**
     * Marque la transaction comme échouée
     */
    public function markAsFailed(): void
    {
        $this->status = 'failed';
    }

    /**
     * Vérifie si la transaction est complétée
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Vérifie si la transaction est en attente
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Retourne le locataire concerné
     */
    public function getTenant(): ?Tenant
    {
        return $this->lease?->getTenant();
    }

    /**
     * Retourne une description lisible du statut
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => '⏳ En attente',
            'completed' => '✅ Complété',
            'failed' => '❌ Échoué',
            'cancelled' => '🚫 Annulé',
            default => $this->status,
        };
    }

    /**
     * Retourne une description lisible du mode de paiement
     */
    public function getPaymentMethodLabel(): string
    {
        if (!$this->paymentMethod) {
            return 'Non défini';
        }

        return match ($this->paymentMethod) {
            'ORANGE_MONEY', 'OM' => '🍊 Orange Money',
            'MTN_MONEY', 'MOMO' => '💛 MTN Money',
            'MOOV_MONEY' => '💙 Moov Money',
            'WAVE' => '💚 Wave',
            'CARD', 'VISA', 'MASTERCARD' => '💳 Carte Bancaire',
            default => $this->paymentMethod,
        };
    }

    public function __toString(): string
    {
        return sprintf(
            'Transaction #%s - %s - %s',
            $this->transactionId,
            number_format((float) $this->amount, 0) . ' ' . $this->currency,
            $this->getStatusLabel()
        );
    }
}

