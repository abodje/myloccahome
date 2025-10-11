<?php

namespace App\Entity;

use App\Repository\PropertyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PropertyRepository::class)]
class Property
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $address = null;

    #[ORM\Column(length: 10)]
    private ?string $postalCode = null;

    #[ORM\Column(length: 100)]
    private ?string $city = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null; // Appartement, Maison, Studio, etc.

    #[ORM\Column]
    private ?float $surface = null; // m²

    #[ORM\Column]
    private ?int $rooms = null;

    #[ORM\Column]
    private ?int $bedrooms = null;

    #[ORM\Column]
    private ?int $bathrooms = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $rentAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $charges = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $deposit = null; // Caution

    #[ORM\Column(length: 50)]
    private ?string $status = null; // Disponible, Occupé, En travaux, etc.

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column]
    private ?bool $furnished = null;

    #[ORM\Column]
    private ?bool $garage = null;

    #[ORM\Column]
    private ?bool $balcony = null;

    #[ORM\Column]
    private ?bool $elevator = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $energyRating = null;

    /**
     * @var Collection<int, RentalContract>
     */
    #[ORM\OneToMany(targetEntity: RentalContract::class, mappedBy: 'property')]
    private Collection $rentalContracts;

    /**
     * @var Collection<int, Maintenance>
     */
    #[ORM\OneToMany(targetEntity: Maintenance::class, mappedBy: 'property')]
    private Collection $maintenances;

    public function __construct()
    {
        $this->rentalContracts = new ArrayCollection();
        $this->maintenances = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

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

    public function getSurface(): ?float
    {
        return $this->surface;
    }

    public function setSurface(float $surface): static
    {
        $this->surface = $surface;

        return $this;
    }

    public function getRooms(): ?int
    {
        return $this->rooms;
    }

    public function setRooms(int $rooms): static
    {
        $this->rooms = $rooms;

        return $this;
    }

    public function getBedrooms(): ?int
    {
        return $this->bedrooms;
    }

    public function setBedrooms(int $bedrooms): static
    {
        $this->bedrooms = $bedrooms;

        return $this;
    }

    public function getBathrooms(): ?int
    {
        return $this->bathrooms;
    }

    public function setBathrooms(int $bathrooms): static
    {
        $this->bathrooms = $bathrooms;

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

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

    public function isFurnished(): ?bool
    {
        return $this->furnished;
    }

    public function setFurnished(bool $furnished): static
    {
        $this->furnished = $furnished;

        return $this;
    }

    public function hasGarage(): ?bool
    {
        return $this->garage;
    }

    public function setGarage(bool $garage): static
    {
        $this->garage = $garage;

        return $this;
    }

    public function hasBalcony(): ?bool
    {
        return $this->balcony;
    }

    public function setBalcony(bool $balcony): static
    {
        $this->balcony = $balcony;

        return $this;
    }

    public function hasElevator(): ?bool
    {
        return $this->elevator;
    }

    public function setElevator(bool $elevator): static
    {
        $this->elevator = $elevator;

        return $this;
    }

    public function getEnergyRating(): ?string
    {
        return $this->energyRating;
    }

    public function setEnergyRating(?string $energyRating): static
    {
        $this->energyRating = $energyRating;

        return $this;
    }

    /**
     * @return Collection<int, RentalContract>
     */
    public function getRentalContracts(): Collection
    {
        return $this->rentalContracts;
    }

    public function addRentalContract(RentalContract $rentalContract): static
    {
        if (!$this->rentalContracts->contains($rentalContract)) {
            $this->rentalContracts->add($rentalContract);
            $rentalContract->setProperty($this);
        }

        return $this;
    }

    public function removeRentalContract(RentalContract $rentalContract): static
    {
        if ($this->rentalContracts->removeElement($rentalContract)) {
            // set the owning side to null (unless already changed)
            if ($rentalContract->getProperty() === $this) {
                $rentalContract->setProperty(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Maintenance>
     */
    public function getMaintenances(): Collection
    {
        return $this->maintenances;
    }

    public function addMaintenance(Maintenance $maintenance): static
    {
        if (!$this->maintenances->contains($maintenance)) {
            $this->maintenances->add($maintenance);
            $maintenance->setProperty($this);
        }

        return $this;
    }

    public function removeMaintenance(Maintenance $maintenance): static
    {
        if ($this->maintenances->removeElement($maintenance)) {
            // set the owning side to null (unless already changed)
            if ($maintenance->getProperty() === $this) {
                $maintenance->setProperty(null);
            }
        }

        return $this;
    }

    public function getCurrentContract(): ?RentalContract
    {
        $activeContracts = $this->rentalContracts->filter(function (RentalContract $contract) {
            return $contract->getStatus() === 'active';
        });

        return $activeContracts->first() ?: null;
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    public function getFullAddress(): string
    {
        return $this->address . ', ' . $this->postalCode . ' ' . $this->city;
    }

    public function getTotalMonthlyAmount(): float
    {
        return (float)$this->rentAmount + (float)$this->charges;
    }

    public function __toString(): string
    {
        return $this->title ?? 'Propriété #' . $this->id;
    }
}