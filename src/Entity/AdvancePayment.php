<?php

namespace App\Entity;

use App\Repository\AdvancePaymentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Représente un paiement anticipé (acompte) versé par un locataire
 * Ces acomptes sont ensuite utilisés pour payer les loyers futurs
 */
#[ORM\Entity(repositoryClass: AdvancePaymentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class AdvancePayment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lease $lease = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $remainingBalance = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $paidDate = null;

    #[ORM\Column(length: 50)]
    private ?string $paymentMethod = null; // Virement, Chèque, Espèces, CB

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reference = null; // Référence bancaire, numéro de chèque, etc.

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null; // Disponible, Utilisé partiellement, Utilisé, Remboursé

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = 'Disponible';
        $this->paidDate = new \DateTime();
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

    public function getLease(): ?Lease
    {
        return $this->lease;
    }

    public function setLease(?Lease $lease): static
    {
        $this->lease = $lease;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

        // Si pas de solde défini, initialiser avec le montant total
        if ($this->remainingBalance === null) {
            $this->remainingBalance = $amount;
        }

        return $this;
    }

    public function getRemainingBalance(): ?string
    {
        return $this->remainingBalance;
    }

    public function setRemainingBalance(string $remainingBalance): static
    {
        $this->remainingBalance = $remainingBalance;

        // Mettre à jour le statut automatiquement
        $this->updateStatus();

        return $this;
    }

    /**
     * Utilise une partie du solde pour un paiement
     */
    public function useBalance(float $amountToUse): float
    {
        $remaining = (float) $this->remainingBalance;

        if ($amountToUse > $remaining) {
            $amountToUse = $remaining;
        }

        $this->remainingBalance = (string) ($remaining - $amountToUse);
        $this->updateStatus();

        return $amountToUse;
    }

    /**
     * Met à jour le statut en fonction du solde restant
     */
    private function updateStatus(): void
    {
        $remaining = (float) $this->remainingBalance;
        $total = (float) $this->amount;

        if ($remaining <= 0) {
            $this->status = 'Utilisé';
        } elseif ($remaining < $total) {
            $this->status = 'Utilisé partiellement';
        } else {
            $this->status = 'Disponible';
        }
    }

    public function getPaidDate(): ?\DateTimeInterface
    {
        return $this->paidDate;
    }

    public function setPaidDate(\DateTimeInterface $paidDate): static
    {
        $this->paidDate = $paidDate;

        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

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
     * Retourne le montant utilisé
     */
    public function getUsedAmount(): float
    {
        return (float) $this->amount - (float) $this->remainingBalance;
    }

    /**
     * Retourne le pourcentage utilisé
     */
    public function getUsedPercentage(): float
    {
        $total = (float) $this->amount;
        if ($total <= 0) {
            return 0;
        }

        return (($total - (float) $this->remainingBalance) / $total) * 100;
    }

    /**
     * Vérifie si l'acompte a encore du solde disponible
     */
    public function hasAvailableBalance(): bool
    {
        return (float) $this->remainingBalance > 0;
    }

    /**
     * Retourne une description lisible du statut
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'Disponible' => '💰 Disponible',
            'Utilisé partiellement' => '⚡ Utilisé partiellement',
            'Utilisé' => '✅ Entièrement utilisé',
            'Remboursé' => '💸 Remboursé',
            default => $this->status,
        };
    }

    /**
     * Retourne le locataire concerné
     */
    public function getTenant(): ?Tenant
    {
        return $this->lease?->getTenant();
    }

    /**
     * Retourne la propriété concernée
     */
    public function getProperty(): ?Property
    {
        return $this->lease?->getProperty();
    }

    public function __toString(): string
    {
        return sprintf(
            'Acompte #%d - %s (Solde: %s)',
            $this->id,
            $this->getTenant()?->getFullName() ?? 'N/A',
            number_format((float) $this->remainingBalance, 2) . '€'
        );
    }
}

