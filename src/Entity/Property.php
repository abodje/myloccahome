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

    #[ORM\ManyToOne(targetEntity: Organization::class, inversedBy: 'properties')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Organization $organization = null;

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'properties')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Company $company = null;

    #[ORM\Column(length: 255)]
    private ?string $address = null;

    #[ORM\Column(length: 100)]
    private ?string $city = null;

    #[ORM\Column(length: 10)]
    private ?string $postalCode = null;

    #[ORM\Column(length: 50)]
    private ?string $propertyType = null; // Appartement, Maison, Bureau, etc.

    #[ORM\Column]
    private ?float $surface = null; // en m²

    #[ORM\Column]
    private ?int $rooms = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $monthlyRent = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $charges = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $deposit = null; // Caution

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null; // Libre, Occupé, En travaux, etc.

    // Nouveaux champs pour enrichir l'entité
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $region = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $district = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 6, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 6, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(nullable: true)]
    private ?int $floor = null; // Étage

    #[ORM\Column(nullable: true)]
    private ?int $totalFloors = null; // Nombre total d'étages dans l'immeuble

    #[ORM\Column(nullable: true)]
    private ?int $bedrooms = null; // Chambres

    #[ORM\Column(nullable: true)]
    private ?int $bathrooms = null; // Salles de bain

    #[ORM\Column(nullable: true)]
    private ?int $toilets = null; // WC séparés

    #[ORM\Column(nullable: true)]
    private ?int $balconies = null; // Nombre de balcons

    #[ORM\Column(name: 'terrace_surface', nullable: true)]
    private ?int $terraceSurface = null; // Surface terrasse en m²

    #[ORM\Column(name: 'garden_surface', nullable: true)]
    private ?int $gardenSurface = null; // Surface jardin en m²

    #[ORM\Column(name: 'parking_spaces', nullable: true)]
    private ?int $parkingSpaces = null; // Nombre de places de parking

    #[ORM\Column(name: 'garage_spaces', nullable: true)]
    private ?int $garageSpaces = null; // Nombre de garages

    #[ORM\Column(name: 'cellar_surface', nullable: true)]
    private ?int $cellarSurface = null; // Surface cave en m²

    #[ORM\Column(name: 'attic_surface', nullable: true)]
    private ?int $atticSurface = null; // Surface grenier en m²

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $landSurface = null; // Surface du terrain en m²

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $constructionYear = null; // Année de construction

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $renovationYear = null; // Année de dernière rénovation

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $heatingType = null; // Type de chauffage

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $hotWaterType = null; // Type d'eau chaude

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $energyClass = null; // Classe énergétique (A, B, C, etc.)

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $energyConsumption = null; // Consommation énergétique

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $orientation = null; // Orientation (Nord, Sud, Est, Ouest)

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $equipment = null; // Équipements (climatisation, ascenseur, etc.)

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $proximity = null; // Proximité (transports, commerces, écoles)

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $restrictions = null; // Restrictions (animaux, fumeurs, etc.)

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null; // Notes internes

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $photos = null; // Photos de la propriété

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $purchasePrice = null; // Prix d'achat

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $purchaseDate = null; // Date d'achat

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $estimatedValue = null; // Valeur estimée actuelle

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $monthlyCharges = null; // Charges mensuelles

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $propertyTax = null; // Taxe foncière annuelle

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $insurance = null; // Assurance annuelle

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $maintenanceBudget = null; // Budget maintenance annuel

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $keyLocation = null; // Localisation des clés

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $accessCode = null; // Code d'accès

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $intercom = null; // Code interphone

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $furnished = null; // Meublé ou non

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $petsAllowed = null; // Animaux autorisés

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $smokingAllowed = null; // Fumeurs autorisés

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $elevator = null; // Ascenseur

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $hasBalcony = null; // Présence de balcon

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $hasParking = null; // Présence de parking

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $airConditioning = null; // Climatisation

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $heating = null; // Chauffage

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $hotWater = null; // Eau chaude

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $internet = null; // Internet

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $cable = null; // Câble/TV

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $dishwasher = null; // Lave-vaisselle

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $washingMachine = null; // Machine à laver

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $dryer = null; // Sèche-linge

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $refrigerator = null; // Réfrigérateur

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $oven = null; // Four

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $microwave = null; // Micro-ondes

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $stove = null; // Cuisinière

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private ?bool $isDemo = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'properties')]
    private ?Owner $owner = null;

    /**
     * @var Collection<int, Lease>
     */
    #[ORM\OneToMany(targetEntity: Lease::class, mappedBy: 'property')]
    private Collection $leases;

    /**
     * @var Collection<int, MaintenanceRequest>
     */
    #[ORM\OneToMany(targetEntity: MaintenanceRequest::class, mappedBy: 'property')]
    private Collection $maintenanceRequests;

    public function __construct()
    {
        $this->leases = new ArrayCollection();
        $this->maintenanceRequests = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->status = 'Libre';
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;
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

    public function getPropertyType(): ?string
    {
        return $this->propertyType;
    }

    public function setPropertyType(string $propertyType): static
    {
        $this->propertyType = $propertyType;
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

    public function getMonthlyRent(): ?string
    {
        return $this->monthlyRent;
    }

    public function setMonthlyRent(string $monthlyRent): static
    {
        $this->monthlyRent = $monthlyRent;
        return $this;
    }

    public function getCharges(): ?string
    {
        return $this->charges;
    }

    public function setCharges(?string $charges): static
    {
        $this->charges = $charges;
        return $this;
    }

    public function getDeposit(): ?string
    {
        return $this->deposit;
    }

    public function setDeposit(?string $deposit): static
    {
        $this->deposit = $deposit;
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

    /**
     * @return Collection<int, Lease>
     */
    public function getLeases(): Collection
    {
        return $this->leases;
    }

    public function addLease(Lease $lease): static
    {
        if (!$this->leases->contains($lease)) {
            $this->leases->add($lease);
            $lease->setProperty($this);
        }
        return $this;
    }

    public function removeLease(Lease $lease): static
    {
        if ($this->leases->removeElement($lease)) {
            if ($lease->getProperty() === $this) {
                $lease->setProperty(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, MaintenanceRequest>
     */
    public function getMaintenanceRequests(): Collection
    {
        return $this->maintenanceRequests;
    }

    public function addMaintenanceRequest(MaintenanceRequest $maintenanceRequest): static
    {
        if (!$this->maintenanceRequests->contains($maintenanceRequest)) {
            $this->maintenanceRequests->add($maintenanceRequest);
            $maintenanceRequest->setProperty($this);
        }
        return $this;
    }

    public function removeMaintenanceRequest(MaintenanceRequest $maintenanceRequest): static
    {
        if ($this->maintenanceRequests->removeElement($maintenanceRequest)) {
            if ($maintenanceRequest->getProperty() === $this) {
                $maintenanceRequest->setProperty(null);
            }
        }
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

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): static
    {
        $this->organization = $organization;
        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;
        return $this;
    }

    public function getFullAddress(): string
    {
        return $this->address . ', ' . $this->postalCode . ' ' . $this->city;
    }

    public function getCurrentLease(): ?Lease
    {
        $now = new \DateTime();
        foreach ($this->leases as $lease) {
            if ($lease->getStartDate() <= $now &&
                ($lease->getEndDate() === null || $lease->getEndDate() >= $now)) {
                return $lease;
            }
        }
        return null;
    }

    // Getters et setters pour les nouveaux champs géographiques
    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;
        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): static
    {
        $this->region = $region;
        return $this;
    }

    public function getDistrict(): ?string
    {
        return $this->district;
    }

    public function setDistrict(?string $district): static
    {
        $this->district = $district;
        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): static
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): static
    {
        $this->longitude = $longitude;
        return $this;
    }

    // Getters et setters pour les caractéristiques physiques
    public function getFloor(): ?int
    {
        return $this->floor;
    }

    public function setFloor(?int $floor): static
    {
        $this->floor = $floor;
        return $this;
    }

    public function getTotalFloors(): ?int
    {
        return $this->totalFloors;
    }

    public function setTotalFloors(?int $totalFloors): static
    {
        $this->totalFloors = $totalFloors;
        return $this;
    }

    public function getBedrooms(): ?int
    {
        return $this->bedrooms;
    }

    public function setBedrooms(?int $bedrooms): static
    {
        $this->bedrooms = $bedrooms;
        return $this;
    }

    public function getBathrooms(): ?int
    {
        return $this->bathrooms;
    }

    public function setBathrooms(?int $bathrooms): static
    {
        $this->bathrooms = $bathrooms;
        return $this;
    }

    public function getToilets(): ?int
    {
        return $this->toilets;
    }

    public function setToilets(?int $toilets): static
    {
        $this->toilets = $toilets;
        return $this;
    }

    public function getBalconies(): ?int
    {
        return $this->balconies;
    }

    public function setBalconies(?int $balconies): static
    {
        $this->balconies = $balconies;
        return $this;
    }

    public function getTerraceSurface(): ?int
    {
        return $this->terraceSurface;
    }

    public function setTerraceSurface(?int $terraceSurface): static
    {
        $this->terraceSurface = $terraceSurface;
        return $this;
    }

    public function getGardenSurface(): ?int
    {
        return $this->gardenSurface;
    }

    public function setGardenSurface(?int $gardenSurface): static
    {
        $this->gardenSurface = $gardenSurface;
        return $this;
    }

    public function getParkingSpaces(): ?int
    {
        return $this->parkingSpaces;
    }

    public function setParkingSpaces(?int $parkingSpaces): static
    {
        $this->parkingSpaces = $parkingSpaces;
        return $this;
    }

    public function getGarageSpaces(): ?int
    {
        return $this->garageSpaces;
    }

    public function setGarageSpaces(?int $garageSpaces): static
    {
        $this->garageSpaces = $garageSpaces;
        return $this;
    }

    public function getCellarSurface(): ?int
    {
        return $this->cellarSurface;
    }

    public function setCellarSurface(?int $cellarSurface): static
    {
        $this->cellarSurface = $cellarSurface;
        return $this;
    }

    public function getAtticSurface(): ?int
    {
        return $this->atticSurface;
    }

    public function setAtticSurface(?int $atticSurface): static
    {
        $this->atticSurface = $atticSurface;
        return $this;
    }

    public function getLandSurface(): ?string
    {
        return $this->landSurface;
    }

    public function setLandSurface(?string $landSurface): static
    {
        $this->landSurface = $landSurface;
        return $this;
    }

    // Getters et setters pour les informations de construction
    public function getConstructionYear(): ?string
    {
        return $this->constructionYear;
    }

    public function setConstructionYear(?string $constructionYear): static
    {
        $this->constructionYear = $constructionYear;
        return $this;
    }

    public function getRenovationYear(): ?string
    {
        return $this->renovationYear;
    }

    public function setRenovationYear(?string $renovationYear): static
    {
        $this->renovationYear = $renovationYear;
        return $this;
    }

    public function getHeatingType(): ?string
    {
        return $this->heatingType;
    }

    public function setHeatingType(?string $heatingType): static
    {
        $this->heatingType = $heatingType;
        return $this;
    }

    public function getHotWaterType(): ?string
    {
        return $this->hotWaterType;
    }

    public function setHotWaterType(?string $hotWaterType): static
    {
        $this->hotWaterType = $hotWaterType;
        return $this;
    }

    public function getEnergyClass(): ?string
    {
        return $this->energyClass;
    }

    public function setEnergyClass(?string $energyClass): static
    {
        $this->energyClass = $energyClass;
        return $this;
    }

    public function getEnergyConsumption(): ?string
    {
        return $this->energyConsumption;
    }

    public function setEnergyConsumption(?string $energyConsumption): static
    {
        $this->energyConsumption = $energyConsumption;
        return $this;
    }

    public function getOrientation(): ?string
    {
        return $this->orientation;
    }

    public function setOrientation(?string $orientation): static
    {
        $this->orientation = $orientation;
        return $this;
    }

    // Getters et setters pour les descriptions
    public function getEquipment(): ?string
    {
        return $this->equipment;
    }

    public function setEquipment(?string $equipment): static
    {
        $this->equipment = $equipment;
        return $this;
    }

    public function getProximity(): ?string
    {
        return $this->proximity;
    }

    public function setProximity(?string $proximity): static
    {
        $this->proximity = $proximity;
        return $this;
    }

    public function getRestrictions(): ?string
    {
        return $this->restrictions;
    }

    public function setRestrictions(?string $restrictions): static
    {
        $this->restrictions = $restrictions;
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

    public function getPhotos(): ?array
    {
        return $this->photos;
    }

    public function setPhotos(?array $photos): static
    {
        $this->photos = $photos;
        return $this;
    }

    public function addPhoto(string $photo): static
    {
        if (!in_array($photo, $this->photos ?? [])) {
            $photos = $this->photos ?? [];
            $photos[] = $photo;
            $this->photos = $photos;
        }
        return $this;
    }

    public function removePhoto(string $photo): static
    {
        $photos = $this->photos ?? [];
        $key = array_search($photo, $photos);
        if ($key !== false) {
            unset($photos[$key]);
            $this->photos = array_values($photos);
        }
        return $this;
    }

    // Getters et setters pour les informations financières
    public function getPurchasePrice(): ?string
    {
        return $this->purchasePrice;
    }

    public function setPurchasePrice(?string $purchasePrice): static
    {
        $this->purchasePrice = $purchasePrice;
        return $this;
    }

    public function getPurchaseDate(): ?\DateTimeInterface
    {
        return $this->purchaseDate;
    }

    public function setPurchaseDate(?\DateTimeInterface $purchaseDate): static
    {
        $this->purchaseDate = $purchaseDate;
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

    public function getMonthlyCharges(): ?string
    {
        return $this->monthlyCharges;
    }

    public function setMonthlyCharges(?string $monthlyCharges): static
    {
        $this->monthlyCharges = $monthlyCharges;
        return $this;
    }

    public function getPropertyTax(): ?string
    {
        return $this->propertyTax;
    }

    public function setPropertyTax(?string $propertyTax): static
    {
        $this->propertyTax = $propertyTax;
        return $this;
    }

    public function getInsurance(): ?string
    {
        return $this->insurance;
    }

    public function setInsurance(?string $insurance): static
    {
        $this->insurance = $insurance;
        return $this;
    }

    public function getMaintenanceBudget(): ?string
    {
        return $this->maintenanceBudget;
    }

    public function setMaintenanceBudget(?string $maintenanceBudget): static
    {
        $this->maintenanceBudget = $maintenanceBudget;
        return $this;
    }

    // Getters et setters pour les informations d'accès
    public function getKeyLocation(): ?string
    {
        return $this->keyLocation;
    }

    public function setKeyLocation(?string $keyLocation): static
    {
        $this->keyLocation = $keyLocation;
        return $this;
    }

    public function getAccessCode(): ?string
    {
        return $this->accessCode;
    }

    public function setAccessCode(?string $accessCode): static
    {
        $this->accessCode = $accessCode;
        return $this;
    }

    public function getIntercom(): ?string
    {
        return $this->intercom;
    }

    public function setIntercom(?string $intercom): static
    {
        $this->intercom = $intercom;
        return $this;
    }

    // Getters et setters pour les caractéristiques booléennes
    public function isFurnished(): ?bool
    {
        return $this->furnished;
    }

    public function setFurnished(?bool $furnished): static
    {
        $this->furnished = $furnished;
        return $this;
    }

    public function isPetsAllowed(): ?bool
    {
        return $this->petsAllowed;
    }

    public function setPetsAllowed(?bool $petsAllowed): static
    {
        $this->petsAllowed = $petsAllowed;
        return $this;
    }

    public function isSmokingAllowed(): ?bool
    {
        return $this->smokingAllowed;
    }

    public function setSmokingAllowed(?bool $smokingAllowed): static
    {
        $this->smokingAllowed = $smokingAllowed;
        return $this;
    }

    public function isElevator(): ?bool
    {
        return $this->elevator;
    }

    public function setElevator(?bool $elevator): static
    {
        $this->elevator = $elevator;
        return $this;
    }

    public function isHasBalcony(): ?bool
    {
        return $this->hasBalcony;
    }

    public function setHasBalcony(?bool $hasBalcony): static
    {
        $this->hasBalcony = $hasBalcony;
        return $this;
    }

    public function isHasParking(): ?bool
    {
        return $this->hasParking;
    }

    public function setHasParking(?bool $hasParking): static
    {
        $this->hasParking = $hasParking;
        return $this;
    }

    public function isAirConditioning(): ?bool
    {
        return $this->airConditioning;
    }

    public function setAirConditioning(?bool $airConditioning): static
    {
        $this->airConditioning = $airConditioning;
        return $this;
    }

    public function isHeating(): ?bool
    {
        return $this->heating;
    }

    public function setHeating(?bool $heating): static
    {
        $this->heating = $heating;
        return $this;
    }

    public function isHotWater(): ?bool
    {
        return $this->hotWater;
    }

    public function setHotWater(?bool $hotWater): static
    {
        $this->hotWater = $hotWater;
        return $this;
    }

    public function isInternet(): ?bool
    {
        return $this->internet;
    }

    public function setInternet(?bool $internet): static
    {
        $this->internet = $internet;
        return $this;
    }

    public function isCable(): ?bool
    {
        return $this->cable;
    }

    public function setCable(?bool $cable): static
    {
        $this->cable = $cable;
        return $this;
    }

    public function isDishwasher(): ?bool
    {
        return $this->dishwasher;
    }

    public function setDishwasher(?bool $dishwasher): static
    {
        $this->dishwasher = $dishwasher;
        return $this;
    }

    public function isWashingMachine(): ?bool
    {
        return $this->washingMachine;
    }

    public function setWashingMachine(?bool $washingMachine): static
    {
        $this->washingMachine = $washingMachine;
        return $this;
    }

    public function isDryer(): ?bool
    {
        return $this->dryer;
    }

    public function setDryer(?bool $dryer): static
    {
        $this->dryer = $dryer;
        return $this;
    }

    public function isRefrigerator(): ?bool
    {
        return $this->refrigerator;
    }

    public function setRefrigerator(?bool $refrigerator): static
    {
        $this->refrigerator = $refrigerator;
        return $this;
    }

    public function isOven(): ?bool
    {
        return $this->oven;
    }

    public function setOven(?bool $oven): static
    {
        $this->oven = $oven;
        return $this;
    }

    public function isMicrowave(): ?bool
    {
        return $this->microwave;
    }

    public function setMicrowave(?bool $microwave): static
    {
        $this->microwave = $microwave;
        return $this;
    }

    public function isStove(): ?bool
    {
        return $this->stove;
    }

    public function setStove(?bool $stove): static
    {
        $this->stove = $stove;
        return $this;
    }

    // Méthodes utilitaires
    public function getFullLocation(): string
    {
        $location = $this->getFullAddress();
        if ($this->country) {
            $location .= ', ' . $this->country;
        }
        return $location;
    }

    public function getTotalSurface(): float
    {
        $total = $this->surface ?? 0;
        if ($this->terraceSurface) {
            $total += $this->terraceSurface;
        }
        if ($this->gardenSurface) {
            $total += $this->gardenSurface;
        }
        if ($this->cellarSurface) {
            $total += $this->cellarSurface;
        }
        if ($this->atticSurface) {
            $total += $this->atticSurface;
        }
        return $total;
    }

    public function getRentWithCharges(): float
    {
        $rent = (float) ($this->monthlyRent ?? 0);
        $charges = (float) ($this->charges ?? 0);
        return $rent + $charges;
    }

    public function getTotalRooms(): int
    {
        $total = $this->rooms ?? 0;
        if ($this->bedrooms) {
            $total += $this->bedrooms;
        }
        if ($this->bathrooms) {
            $total += $this->bathrooms;
        }
        if ($this->toilets) {
            $total += $this->toilets;
        }
        return $total;
    }

    public function getEquipmentList(): array
    {
        $equipment = [];
        if ($this->isFurnished()) $equipment[] = 'Meublé';
        if ($this->isElevator()) $equipment[] = 'Ascenseur';
        if ($this->isHasBalcony()) $equipment[] = 'Balcon';
        if ($this->getTerraceSurface() > 0) $equipment[] = 'Terrasse (' . $this->getTerraceSurface() . ' m²)';
        if ($this->getGardenSurface() > 0) $equipment[] = 'Jardin (' . $this->getGardenSurface() . ' m²)';
        if ($this->isHasParking()) $equipment[] = 'Parking';
        if ($this->getGarageSpaces() > 0) $equipment[] = 'Garage (' . $this->getGarageSpaces() . ' places)';
        if ($this->getCellarSurface() > 0) $equipment[] = 'Cave (' . $this->getCellarSurface() . ' m²)';
        if ($this->getAtticSurface() > 0) $equipment[] = 'Grenier (' . $this->getAtticSurface() . ' m²)';
        if ($this->isAirConditioning()) $equipment[] = 'Climatisation';
        if ($this->isHeating()) $equipment[] = 'Chauffage';
        if ($this->isHotWater()) $equipment[] = 'Eau chaude';
        if ($this->isInternet()) $equipment[] = 'Internet';
        if ($this->isCable()) $equipment[] = 'Câble/TV';
        if ($this->isDishwasher()) $equipment[] = 'Lave-vaisselle';
        if ($this->isWashingMachine()) $equipment[] = 'Machine à laver';
        if ($this->isDryer()) $equipment[] = 'Sèche-linge';
        if ($this->isRefrigerator()) $equipment[] = 'Réfrigérateur';
        if ($this->isOven()) $equipment[] = 'Four';
        if ($this->isMicrowave()) $equipment[] = 'Micro-ondes';
        if ($this->isStove()) $equipment[] = 'Cuisinière';
        return $equipment;
    }

    public function __toString(): string
    {
        return $this->getFullAddress();
    }

    public function getIsDemo(): ?bool
    {
        return $this->isDemo;
    }

    public function setIsDemo(bool $isDemo): static
    {
        $this->isDemo = $isDemo;
        return $this;
    }
}
