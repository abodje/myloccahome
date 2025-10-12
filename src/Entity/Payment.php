<?php

namespace App\Entity;

use App\Repository\PaymentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
class Payment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lease $lease = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dueDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $paidDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null; // Loyer, Charges, Caution, etc.

    #[ORM\Column(length: 50)]
    private ?string $status = null; // En attente, Payé, En retard, Partiel

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $paymentMethod = null; // Virement, Chèque, Espèces, etc.

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reference = null; // Numéro de chèque, référence virement, etc.

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = 'En attente';
        $this->type = 'Loyer';
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

    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(\DateTimeInterface $dueDate): static
    {
        $this->dueDate = $dueDate;
        return $this;
    }

    public function getPaidDate(): ?\DateTimeInterface
    {
        return $this->paidDate;
    }

    public function setPaidDate(?\DateTimeInterface $paidDate): static
    {
        $this->paidDate = $paidDate;
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
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

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): static
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

    public function isPaid(): bool
    {
        return $this->status === 'Payé';
    }

    public function isOverdue(): bool
    {
        if ($this->isPaid()) {
            return false;
        }

        $now = new \DateTime();
        return $this->dueDate < $now;
    }

    public function getDaysOverdue(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }

        $now = new \DateTime();
        $interval = $this->dueDate->diff($now);
        return $interval->days;
    }

    public function markAsPaid(\DateTimeInterface $paidDate = null, string $paymentMethod = null, string $reference = null): void
    {
        $this->status = 'Payé';
        $this->paidDate = $paidDate ?? new \DateTime();

        if ($paymentMethod) {
            $this->paymentMethod = $paymentMethod;
        }

        if ($reference) {
            $this->reference = $reference;
        }

        $this->updatedAt = new \DateTime();
    }

    public function getProperty(): ?Property
    {
        return $this->lease?->getProperty();
    }

    public function getTenant(): ?Tenant
    {
        return $this->lease?->getTenant();
    }

    public function __toString(): string
    {
        return sprintf(
            '%s - %s - %s € (%s)',
            $this->type,
            $this->dueDate ? $this->dueDate->format('d/m/Y') : 'N/A',
            $this->amount,
            $this->status
        );
    }
}
