<?php

namespace App\Entity;

use App\Repository\RentalContractRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RentalContractRepository::class)]
class RentalContract
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'rentalContracts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Property $property = null;

    #[ORM\ManyToOne(inversedBy: 'rentalContracts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tenant $tenant = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $rentAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $charges = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $deposit = null;

    #[ORM\Column]
    private ?int $rentDueDay = null; // Jour du mois où le loyer est dû

    #[ORM\Column(length: 50)]
    private ?string $status = null; // active, terminated, expired

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $specialConditions = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(length: 100)]
    private ?string $contractNumber = null;

    /**
     * @var Collection<int, Payment>
     */
    #[ORM\OneToMany(targetEntity: Payment::class, mappedBy: 'rentalContract')]
    private Collection $payments;

    public function __construct()
    {
        $this->payments = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->status = 'active';
        $this->rentDueDay = 1; // Par défaut, le 1er du mois
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProperty(): ?Property
    {
        return $this->property;
    }

    public function setProperty(?Property $property): static
    {
        $this->property = $property;

        return $this;
    }

    public function getTenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function setTenant(?Tenant $tenant): static
    {
        $this->tenant = $tenant;

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

    public function setEndDate(?\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getRentAmount(): ?string
    {
        return $this->rentAmount;
    }

    public function setRentAmount(string $rentAmount): static
    {
        $this->rentAmount = $rentAmount;

        return $this;
    }

    public function getCharges(): ?string
    {
        return $this->charges;
    }

    public function setCharges(string $charges): static
    {
        $this->charges = $charges;

        return $this;
    }

    public function getDeposit(): ?string
    {
        return $this->deposit;
    }

    public function setDeposit(string $deposit): static
    {
        $this->deposit = $deposit;

        return $this;
    }

    public function getRentDueDay(): ?int
    {
        return $this->rentDueDay;
    }

    public function setRentDueDay(int $rentDueDay): static
    {
        $this->rentDueDay = $rentDueDay;

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

    public function getSpecialConditions(): ?string
    {
        return $this->specialConditions;
    }

    public function setSpecialConditions(?string $specialConditions): static
    {
        $this->specialConditions = $specialConditions;

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

    public function getContractNumber(): ?string
    {
        return $this->contractNumber;
    }

    public function setContractNumber(string $contractNumber): static
    {
        $this->contractNumber = $contractNumber;

        return $this;
    }

    /**
     * @return Collection<int, Payment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): static
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setRentalContract($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): static
    {
        if ($this->payments->removeElement($payment)) {
            // set the owning side to null (unless already changed)
            if ($payment->getRentalContract() === $this) {
                $payment->setRentalContract(null);
            }
        }

        return $this;
    }

    public function getTotalMonthlyAmount(): float
    {
        return (float)$this->rentAmount + (float)$this->charges;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getDuration(): ?int
    {
        if (!$this->startDate || !$this->endDate) {
            return null;
        }

        return $this->startDate->diff($this->endDate)->days;
    }

    public function getRemainingDays(): ?int
    {
        if (!$this->endDate || $this->status !== 'active') {
            return null;
        }

        $now = new \DateTime();
        if ($now > $this->endDate) {
            return 0;
        }

        return $now->diff($this->endDate)->days;
    }

    public function getNextRentDueDate(): ?\DateTime
    {
        if (!$this->isActive()) {
            return null;
        }

        $now = new \DateTime();
        $dueDate = new \DateTime($now->format('Y-m-') . str_pad($this->rentDueDay, 2, '0', STR_PAD_LEFT));
        
        // Si la date est déjà passée ce mois, prendre le mois suivant
        if ($dueDate <= $now) {
            $dueDate->modify('+1 month');
        }

        return $dueDate;
    }

    public function getPaymentStatus(): string
    {
        $now = new \DateTime();
        $thisMonth = $now->format('Y-m');
        
        $currentMonthPayments = $this->payments->filter(function (Payment $payment) use ($thisMonth) {
            return $payment->getPeriod() === $thisMonth && $payment->getStatus() === 'paid';
        });

        return $currentMonthPayments->count() > 0 ? 'paid' : 'pending';
    }

    public function __toString(): string
    {
        return 'Contrat #' . $this->contractNumber . ' - ' . $this->tenant?->getFullName() ?? '';
    }
}