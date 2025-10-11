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
    private ?RentalContract $rentalContract = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $paymentDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dueDate = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null; // pending, paid, overdue, cancelled

    #[ORM\Column(length: 100)]
    private ?string $paymentMethod = null; // virement, chèque, espèces, carte

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reference = null; // numéro de chèque, référence virement, etc.

    #[ORM\Column(length: 20)]
    private ?string $period = null; // Format YYYY-MM pour identifier le mois

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $lateFee = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = 'pending';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRentalContract(): ?RentalContract
    {
        return $this->rentalContract;
    }

    public function setRentalContract(?RentalContract $rentalContract): static
    {
        $this->rentalContract = $rentalContract;

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

    public function getPaymentDate(): ?\DateTimeInterface
    {
        return $this->paymentDate;
    }

    public function setPaymentDate(?\DateTimeInterface $paymentDate): static
    {
        $this->paymentDate = $paymentDate;

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

    public function getPeriod(): ?string
    {
        return $this->period;
    }

    public function setPeriod(string $period): static
    {
        $this->period = $period;

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

    public function getLateFee(): ?string
    {
        return $this->lateFee;
    }

    public function setLateFee(?string $lateFee): static
    {
        $this->lateFee = $lateFee;

        return $this;
    }

    public function getTotalAmount(): float
    {
        return (float)$this->amount + (float)($this->lateFee ?? 0);
    }

    public function isOverdue(): bool
    {
        return $this->status === 'overdue' || 
               ($this->status === 'pending' && $this->dueDate && $this->dueDate < new \DateTime());
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function getDaysLate(): int
    {
        if (!$this->dueDate || $this->isPaid()) {
            return 0;
        }

        $now = new \DateTime();
        if ($now <= $this->dueDate) {
            return 0;
        }

        return $now->diff($this->dueDate)->days;
    }

    public function getFormattedPeriod(): string
    {
        if (!$this->period) {
            return '';
        }

        $date = \DateTime::createFromFormat('Y-m', $this->period);
        return $date ? $date->format('F Y') : $this->period;
    }

    public function markAsPaid(\DateTimeInterface $paymentDate = null, string $paymentMethod = null, string $reference = null): void
    {
        $this->status = 'paid';
        $this->paymentDate = $paymentDate ?? new \DateTime();
        
        if ($paymentMethod) {
            $this->paymentMethod = $paymentMethod;
        }
        
        if ($reference) {
            $this->reference = $reference;
        }
        
        $this->updatedAt = new \DateTime();
    }

    public function __toString(): string
    {
        return sprintf('Paiement %s - %s€', $this->getFormattedPeriod(), $this->amount);
    }
}