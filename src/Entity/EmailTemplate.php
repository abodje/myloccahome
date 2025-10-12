<?php

namespace App\Entity;

use App\Repository\EmailTemplateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmailTemplateRepository::class)]
class EmailTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $code = null; // RENT_RECEIPT, PAYMENT_REMINDER, etc.

    #[ORM\Column(length: 255)]
    private ?string $name = null; // Nom descriptif

    #[ORM\Column(length: 255)]
    private ?string $subject = null; // Sujet de l'email

    #[ORM\Column(type: Types::TEXT)]
    private ?string $htmlContent = null; // Contenu HTML

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $textContent = null; // Version texte (optionnel)

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $availableVariables = null; // Liste des variables disponibles

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    #[ORM\Column]
    private ?bool $isSystem = null; // Template système (non supprimable)

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastUsedAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $usageCount = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->isActive = true;
        $this->isSystem = false;
        $this->usageCount = 0;
        $this->availableVariables = [];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function getHtmlContent(): ?string
    {
        return $this->htmlContent;
    }

    public function setHtmlContent(string $htmlContent): static
    {
        $this->htmlContent = $htmlContent;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getTextContent(): ?string
    {
        return $this->textContent;
    }

    public function setTextContent(?string $textContent): static
    {
        $this->textContent = $textContent;
        return $this;
    }

    public function getAvailableVariables(): ?array
    {
        return $this->availableVariables;
    }

    public function setAvailableVariables(?array $availableVariables): static
    {
        $this->availableVariables = $availableVariables;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function isSystem(): ?bool
    {
        return $this->isSystem;
    }

    public function getIsSystem(): ?bool
    {
        return $this->isSystem;
    }

    public function setIsSystem(bool $isSystem): static
    {
        $this->isSystem = $isSystem;
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

    public function getLastUsedAt(): ?\DateTimeInterface
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(?\DateTimeInterface $lastUsedAt): static
    {
        $this->lastUsedAt = $lastUsedAt;
        return $this;
    }

    public function getUsageCount(): ?int
    {
        return $this->usageCount;
    }

    public function setUsageCount(int $usageCount): static
    {
        $this->usageCount = $usageCount;
        return $this;
    }

    public function incrementUsageCount(): static
    {
        $this->usageCount++;
        $this->lastUsedAt = new \DateTime();
        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? 'Template #' . $this->id;
    }
}

