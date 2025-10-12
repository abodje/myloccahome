<?php

namespace App\Entity;

use App\Repository\SettingsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SettingsRepository::class)]
class Settings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $settingKey = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $settingValue = null;

    #[ORM\Column(length: 50)]
    private ?string $category = null; // GENERAL, EMAIL, PAYMENT, MAINTENANCE, etc.

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    private ?string $dataType = null; // STRING, INTEGER, BOOLEAN, JSON, etc.

    #[ORM\Column]
    private ?bool $isEditable = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->isEditable = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSettingKey(): ?string
    {
        return $this->settingKey;
    }

    public function setSettingKey(string $settingKey): static
    {
        $this->settingKey = $settingKey;
        return $this;
    }

    public function getSettingValue(): ?string
    {
        return $this->settingValue;
    }

    public function setSettingValue(?string $settingValue): static
    {
        $this->settingValue = $settingValue;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDataType(): ?string
    {
        return $this->dataType;
    }

    public function setDataType(string $dataType): static
    {
        $this->dataType = $dataType;
        return $this;
    }

    public function isEditable(): ?bool
    {
        return $this->isEditable;
    }

    public function setEditable(bool $isEditable): static
    {
        $this->isEditable = $isEditable;
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

    public function getParsedValue()
    {
        return match($this->dataType) {
            'BOOLEAN' => filter_var($this->settingValue, FILTER_VALIDATE_BOOLEAN),
            'INTEGER' => (int)$this->settingValue,
            'FLOAT' => (float)$this->settingValue,
            'JSON' => json_decode($this->settingValue, true),
            default => $this->settingValue
        };
    }

    public function setValue($value): static
    {
        $this->settingValue = match($this->dataType) {
            'BOOLEAN' => $value ? '1' : '0',
            'JSON' => json_encode($value),
            default => (string)$value
        };
        return $this;
    }

    public function __toString(): string
    {
        return $this->settingKey . ' = ' . $this->settingValue;
    }
}
