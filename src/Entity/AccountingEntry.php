<?php

namespace App\Entity;

use App\Repository\AccountingEntryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccountingEntryRepository::class)]
class AccountingEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $entryDate = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null; // CREDIT, DEBIT

    #[ORM\Column(length: 100)]
    private ?string $category = null; // LOYER, CHARGES, TRAVAUX, ASSURANCE, etc.

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reference = null; // Numéro de transaction, chèque, etc.

    #[ORM\ManyToOne]
    private ?Property $property = null;

    #[ORM\ManyToOne]
    private ?Owner $owner = null;

    #[ORM\ManyToOne]
    private ?Payment $payment = null;

    #[ORM\ManyToOne]
    private ?Expense $expense = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $runningBalance = null; // Solde courant après cette écriture

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->entryDate = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntryDate(): ?\DateTimeInterface
    {
        return $this->entryDate;
    }

    public function setEntryDate(\DateTimeInterface $entryDate): static
    {
        $this->entryDate = $entryDate;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
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

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;
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

    public function getProperty(): ?Property
    {
        return $this->property;
    }

    public function setProperty(?Property $property): static
    {
        $this->property = $property;
        return $this;
    }

    public function getOwner(): ?Owner
    {
        return $this->owner;
    }

    public function setOwner(?Owner $owner): static
    {
        $this->owner = $owner;
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

    public function getExpense(): ?Expense
    {
        return $this->expense;
    }

    public function setExpense(?Expense $expense): static
    {
        $this->expense = $expense;
        return $this;
    }

    public function getRunningBalance(): ?string
    {
        return $this->runningBalance;
    }

    public function setRunningBalance(?string $runningBalance): static
    {
        $this->runningBalance = $runningBalance;
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

    public function isCredit(): bool
    {
        return $this->type === 'CREDIT';
    }

    public function isDebit(): bool
    {
        return $this->type === 'DEBIT';
    }

    public function getSignedAmount(): float
    {
        $amount = (float)$this->amount;
        return $this->isCredit() ? $amount : -$amount;
    }

    public function getFormattedAmount(): string
    {
        $amount = number_format((float)$this->amount, 2, ',', ' ');
        return $this->isCredit() ? "+{$amount} €" : "-{$amount} €";
    }

    public function getAmountColor(): string
    {
        return $this->isCredit() ? 'success' : 'danger';
    }

    public function __toString(): string
    {
        return sprintf(
            '%s - %s (%s)',
            $this->entryDate ? $this->entryDate->format('d/m/Y') : 'N/A',
            $this->description,
            $this->getFormattedAmount()
        );
    }
}
