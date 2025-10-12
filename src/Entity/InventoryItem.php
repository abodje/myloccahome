<?php

namespace App\Entity;

use App\Repository\InventoryItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InventoryItemRepository::class)]
class InventoryItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Inventory $inventory = null;

    #[ORM\Column(length: 100)]
    private ?string $room = null; // Salon, Cuisine, Chambre 1, etc.

    #[ORM\Column(length: 100)]
    private ?string $category = null; // Mobilier, Électroménager, Revêtement, etc.

    #[ORM\Column(length: 255)]
    private ?string $item = null; // Description de l'élément

    #[ORM\Column(length: 50)]
    private ?string $condition = null; // Excellent, Bon, Correct, Mauvais, Très mauvais, Endommagé

    #[ORM\Column(nullable: true)]
    private ?int $quantity = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null; // Observations particulières

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $estimatedValue = null; // Valeur estimée pour assurance

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->quantity = 1;
        $this->condition = 'Bon';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInventory(): ?Inventory
    {
        return $this->inventory;
    }

    public function setInventory(?Inventory $inventory): static
    {
        $this->inventory = $inventory;
        return $this;
    }

    public function getRoom(): ?string
    {
        return $this->room;
    }

    public function setRoom(string $room): static
    {
        $this->room = $room;
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

    public function getItem(): ?string
    {
        return $this->item;
    }

    public function setItem(string $item): static
    {
        $this->item = $item;
        return $this;
    }

    public function getCondition(): ?string
    {
        return $this->condition;
    }

    public function setCondition(string $condition): static
    {
        $this->condition = $condition;
        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): static
    {
        $this->quantity = $quantity;
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

    public function getEstimatedValue(): ?string
    {
        return $this->estimatedValue;
    }

    public function setEstimatedValue(?string $estimatedValue): static
    {
        $this->estimatedValue = $estimatedValue;
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

    public function getConditionColor(): string
    {
        return match($this->condition) {
            'Excellent' => 'success',
            'Bon' => 'info',
            'Correct' => 'warning',
            'Mauvais' => 'danger',
            'Très mauvais' => 'danger',
            'Endommagé' => 'danger',
            default => 'secondary'
        };
    }

    public function isDamaged(): bool
    {
        return in_array($this->condition, ['Endommagé', 'Très mauvais', 'Mauvais']);
    }

    public function isInGoodCondition(): bool
    {
        return in_array($this->condition, ['Excellent', 'Bon']);
    }

    public function getTotalValue(): float
    {
        if (!$this->estimatedValue || !$this->quantity) {
            return 0;
        }
        return (float)$this->estimatedValue * $this->quantity;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s - %s (%s)',
            $this->room,
            $this->item,
            $this->condition
        );
    }
}
