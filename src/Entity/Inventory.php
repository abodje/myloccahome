<?php

namespace App\Entity;

use App\Repository\InventoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InventoryRepository::class)]
class Inventory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Property $property = null;

    #[ORM\ManyToOne]
    private ?Lease $lease = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null; // État des lieux entrant, État des lieux sortant, Inventaire périodique

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $inventoryDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $performedBy = null; // Qui a effectué l'inventaire

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $generalNotes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * @var Collection<int, InventoryItem>
     */
    #[ORM\OneToMany(targetEntity: InventoryItem::class, mappedBy: 'inventory', cascade: ['persist', 'remove'])]
    private Collection $items;

    /**
     * @var Collection<int, Document>
     */
    #[ORM\OneToMany(targetEntity: Document::class, mappedBy: 'inventory')]
    private Collection $documents;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->inventoryDate = new \DateTime();
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

    public function getLease(): ?Lease
    {
        return $this->lease;
    }

    public function setLease(?Lease $lease): static
    {
        $this->lease = $lease;
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

    public function getInventoryDate(): ?\DateTimeInterface
    {
        return $this->inventoryDate;
    }

    public function setInventoryDate(\DateTimeInterface $inventoryDate): static
    {
        $this->inventoryDate = $inventoryDate;
        return $this;
    }

    public function getPerformedBy(): ?string
    {
        return $this->performedBy;
    }

    public function setPerformedBy(?string $performedBy): static
    {
        $this->performedBy = $performedBy;
        return $this;
    }

    public function getGeneralNotes(): ?string
    {
        return $this->generalNotes;
    }

    public function setGeneralNotes(?string $generalNotes): static
    {
        $this->generalNotes = $generalNotes;
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
     * @return Collection<int, InventoryItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(InventoryItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setInventory($this);
        }
        return $this;
    }

    public function removeItem(InventoryItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getInventory() === $this) {
                $item->setInventory(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Document>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(Document $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setInventory($this);
        }
        return $this;
    }

    public function removeDocument(Document $document): static
    {
        if ($this->documents->removeElement($document)) {
            if ($document->getInventory() === $this) {
                $document->setInventory(null);
            }
        }
        return $this;
    }

    public function getItemsByRoom(): array
    {
        $itemsByRoom = [];
        foreach ($this->items as $item) {
            $room = $item->getRoom() ?? 'Non spécifié';
            if (!isset($itemsByRoom[$room])) {
                $itemsByRoom[$room] = [];
            }
            $itemsByRoom[$room][] = $item;
        }
        return $itemsByRoom;
    }

    public function getDamagedItemsCount(): int
    {
        $count = 0;
        foreach ($this->items as $item) {
            if ($item->getCondition() === 'Endommagé' || $item->getCondition() === 'Très mauvais') {
                $count++;
            }
        }
        return $count;
    }

    public function getGoodItemsCount(): int
    {
        $count = 0;
        foreach ($this->items as $item) {
            if ($item->getCondition() === 'Excellent' || $item->getCondition() === 'Bon') {
                $count++;
            }
        }
        return $count;
    }

    public function getTotalItemsCount(): int
    {
        return $this->items->count();
    }

    public function isEntryInventory(): bool
    {
        return $this->type === 'État des lieux entrant';
    }

    public function isExitInventory(): bool
    {
        return $this->type === 'État des lieux sortant';
    }

    public function getTypeColor(): string
    {
        return match($this->type) {
            'État des lieux entrant' => 'success',
            'État des lieux sortant' => 'warning',
            'Inventaire périodique' => 'info',
            default => 'secondary'
        };
    }

    public function __toString(): string
    {
        return sprintf(
            '%s - %s (%s)',
            $this->type,
            $this->property ? $this->property->getFullAddress() : 'N/A',
            $this->inventoryDate ? $this->inventoryDate->format('d/m/Y') : 'N/A'
        );
    }
}
