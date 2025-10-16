<?php

namespace App\Entity;

use App\Repository\MenuItemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MenuItemRepository::class)]
class MenuItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $label = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $menuKey = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $route = null;

    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    #[ORM\Column]
    private ?int $displayOrder = 0;

    #[ORM\Column]
    private ?bool $isActive = true;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $type = null; // 'menu', 'divider', 'submenu'

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'subMenuItems')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?self $parent = null;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    #[ORM\OrderBy(['displayOrder' => 'ASC'])]
    private Collection $subMenuItems;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $badgeType = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->subMenuItems = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->isActive = true;
        $this->type = 'menu';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function getMenuKey(): ?string
    {
        return $this->menuKey;
    }

    public function setMenuKey(string $menuKey): static
    {
        $this->menuKey = $menuKey;
        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function setRoute(?string $route): static
    {
        $this->route = $route;
        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getDisplayOrder(): ?int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): static
    {
        $this->displayOrder = $displayOrder;
        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getSubMenuItems(): Collection
    {
        return $this->subMenuItems;
    }

    public function addSubMenuItem(self $subMenuItem): static
    {
        if (!$this->subMenuItems->contains($subMenuItem)) {
            $this->subMenuItems->add($subMenuItem);
            $subMenuItem->setParent($this);
        }

        return $this;
    }

    public function removeSubMenuItem(self $subMenuItem): static
    {
        if ($this->subMenuItems->removeElement($subMenuItem)) {
            if ($subMenuItem->getParent() === $this) {
                $subMenuItem->setParent(null);
            }
        }

        return $this;
    }

    public function getBadgeType(): ?string
    {
        return $this->badgeType;
    }

    public function setBadgeType(?string $badgeType): static
    {
        $this->badgeType = $badgeType;
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
        return $this->label ?? 'Menu Item';
    }
}

