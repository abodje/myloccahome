<?php

namespace App\Entity;

use App\Repository\CurrencyRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CurrencyRepository::class)]
class Currency
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 3, unique: true)]
    private ?string $code = null; // EUR, USD, GBP, etc.

    #[ORM\Column(length: 100)]
    private ?string $name = null; // Euro, Dollar américain, etc.

    #[ORM\Column(length: 10)]
    private ?string $symbol = null; // €, $, £, etc.

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 6)]
    private ?string $exchangeRate = null; // Taux de change par rapport à l'EUR

    #[ORM\Column]
    private ?int $decimalPlaces = null; // Nombre de décimales pour l'affichage

    #[ORM\Column]
    private ?bool $isDefault = null; // Devise par défaut

    #[ORM\Column]
    private ?bool $isActive = null; // Devise active

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastRateUpdate = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->isActive = true;
        $this->isDefault = false;
        $this->exchangeRate = '1.000000'; // Par défaut 1:1
        $this->decimalPlaces = 2; // Par défaut 2 décimales
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
        $this->code = strtoupper($code);
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

    public function getSymbol(): ?string
    {
        return $this->symbol;
    }

    public function setSymbol(string $symbol): static
    {
        $this->symbol = $symbol;
        return $this;
    }

    public function getExchangeRate(): ?string
    {
        return $this->exchangeRate;
    }

    public function setExchangeRate(string $exchangeRate): static
    {
        $this->exchangeRate = $exchangeRate;
        $this->lastRateUpdate = new \DateTime();
        return $this;
    }

    public function getDecimalPlaces(): ?int
    {
        return $this->decimalPlaces;
    }

    public function setDecimalPlaces(int $decimalPlaces): static
    {
        $this->decimalPlaces = $decimalPlaces;
        return $this;
    }

    public function isDefault(): ?bool
    {
        return $this->isDefault;
    }

    public function getIsDefault(): ?bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): static
    {
        $this->isDefault = $isDefault;
        return $this;
    }

    public function setDefault(bool $isDefault): static
    {
        $this->isDefault = $isDefault;
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

    public function setActive(bool $isActive): static
    {
        $this->isActive = $isActive;
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

    public function getLastRateUpdate(): ?\DateTimeInterface
    {
        return $this->lastRateUpdate;
    }

    public function setLastRateUpdate(?\DateTimeInterface $lastRateUpdate): static
    {
        $this->lastRateUpdate = $lastRateUpdate;
        return $this;
    }

    public function convertAmount(float $amount, Currency $targetCurrency): float
    {
        if ($this->code === $targetCurrency->getCode()) {
            return $amount;
        }

        // Conversion via EUR comme devise de référence
        $amountInEur = $amount / (float)$this->exchangeRate;
        return $amountInEur * (float)$targetCurrency->getExchangeRate();
    }

    public function formatAmount(float $amount, bool $showSymbol = true): string
    {
        $decimals = $this->decimalPlaces ?? 2;
        $formatted = number_format($amount, $decimals, ',', ' ');

        if ($showSymbol) {
            return $formatted . ' ' . $this->symbol;
        }

        return $formatted;
    }

    public function getDisplayName(): string
    {
        return $this->name . ' (' . $this->code . ')';
    }

    public function __toString(): string
    {
        return $this->code . ' - ' . $this->name;
    }
}
