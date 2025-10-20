<?php

namespace App\Entity;

use App\Repository\AccountingConfigurationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccountingConfigurationRepository::class)]
#[ORM\Table(name: 'accounting_configuration')]
class AccountingConfiguration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $operationType = null; // LOYER, CHARGE, TRAVAUX, ASSURANCE, etc.

    #[ORM\Column(length: 20)]
    private ?string $accountNumber = null; // 411000, 706000, etc.

    #[ORM\Column(length: 255)]
    private ?string $accountLabel = null; // "Clients - Loyers", "Produits - Loyers", etc.

    #[ORM\Column(length: 10)]
    private ?string $entryType = null; // CREDIT, DEBIT

    #[ORM\Column(length: 255)]
    private ?string $description = null; // Description de l'opération

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reference = null; // Préfixe de référence (LOYER-, CHARGE-, etc.)

    #[ORM\Column(length: 100)]
    private ?string $category = null; // LOYER, CHARGE, TRAVAUX, etc.

    #[ORM\Column(nullable: true)]
    private ?bool $isActive = true;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->isActive = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOperationType(): ?string
    {
        return $this->operationType;
    }

    public function setOperationType(string $operationType): static
    {
        $this->operationType = $operationType;
        return $this;
    }

    public function getAccountNumber(): ?string
    {
        return $this->accountNumber;
    }

    public function setAccountNumber(string $accountNumber): static
    {
        $this->accountNumber = $accountNumber;
        return $this;
    }

    public function getAccountLabel(): ?string
    {
        return $this->accountLabel;
    }

    public function setAccountLabel(string $accountLabel): static
    {
        $this->accountLabel = $accountLabel;
        return $this;
    }

    public function getEntryType(): ?string
    {
        return $this->entryType;
    }

    public function setEntryType(string $entryType): static
    {
        $this->entryType = $entryType;
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

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;
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

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(?bool $isActive): static
    {
        $this->isActive = $isActive;
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

    public function __toString(): string
    {
        return sprintf('%s - %s (%s)', $this->accountNumber, $this->accountLabel, $this->operationType);
    }
}
