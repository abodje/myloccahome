<?php

namespace App\Entity;

use App\Repository\OrganizationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Organisation/Entreprise - Multi-tenant
 */
#[ORM\Entity(repositoryClass: OrganizationRepository::class)]
class Organization
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null; // URL unique: mycompany.mylocca.com

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $postalCode = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $taxNumber = null; // SIRET, SIREN, etc.

    #[ORM\Column(length: 50)]
    private ?string $status = null; // ACTIVE, SUSPENDED, CANCELLED, TRIAL

    #[ORM\ManyToOne(targetEntity: Subscription::class)]
    private ?Subscription $activeSubscription = null;

    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'organization')]
    private Collection $users;

    #[ORM\OneToMany(targetEntity: Property::class, mappedBy: 'organization')]
    private Collection $properties;

    #[ORM\OneToMany(targetEntity: Tenant::class, mappedBy: 'organization')]
    private Collection $tenants;

    #[ORM\OneToMany(targetEntity: Lease::class, mappedBy: 'organization')]
    private Collection $leases;

    #[ORM\OneToMany(targetEntity: Payment::class, mappedBy: 'organization')]
    private Collection $payments;

    #[ORM\OneToMany(targetEntity: Subscription::class, mappedBy: 'organization')]
    private Collection $subscriptions;

    #[ORM\OneToMany(targetEntity: Company::class, mappedBy: 'organization', cascade: ['persist', 'remove'])]
    private Collection $companies;

    #[ORM\OneToMany(targetEntity: Owner::class, mappedBy: 'organization')]
    private Collection $owners;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $settings = null; // Paramètres spécifiques à l'organisation

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $features = null; // Fonctionnalités activées

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $trialEndsAt = null; // Date de fin de période d'essai

    #[ORM\Column]
    private ?bool $isActive = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private ?bool $isDemo = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subdomain = null;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->properties = new ArrayCollection();
        $this->tenants = new ArrayCollection();
        $this->leases = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->subscriptions = new ArrayCollection();
        $this->companies = new ArrayCollection();
        $this->owners = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->isActive = true;
        $this->status = 'TRIAL';
        $this->settings = [];
        $this->features = [];

        // 30 jours de période d'essai par défaut
        $this->trialEndsAt = (new \DateTime())->modify('+30 days');
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;
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

    public function setAddress(?string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): static
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;
        return $this;
    }

    public function getTaxNumber(): ?string
    {
        return $this->taxNumber;
    }

    public function setTaxNumber(?string $taxNumber): static
    {
        $this->taxNumber = $taxNumber;
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

    public function getActiveSubscription(): ?Subscription
    {
        return $this->activeSubscription;
    }

    public function setActiveSubscription(?Subscription $activeSubscription): static
    {
        $this->activeSubscription = $activeSubscription;
        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setOrganization($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            if ($user->getOrganization() === $this) {
                $user->setOrganization(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Property>
     */
    public function getProperties(): Collection
    {
        return $this->properties;
    }

    public function addProperty(Property $property): static
    {
        if (!$this->properties->contains($property)) {
            $this->properties->add($property);
            $property->setOrganization($this);
        }

        return $this;
    }

    public function removeProperty(Property $property): static
    {
        if ($this->properties->removeElement($property)) {
            if ($property->getOrganization() === $this) {
                $property->setOrganization(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Tenant>
     */
    public function getTenants(): Collection
    {
        return $this->tenants;
    }

    public function addTenant(Tenant $tenant): static
    {
        if (!$this->tenants->contains($tenant)) {
            $this->tenants->add($tenant);
            $tenant->setOrganization($this);
        }

        return $this;
    }

    public function removeTenant(Tenant $tenant): static
    {
        if ($this->tenants->removeElement($tenant)) {
            if ($tenant->getOrganization() === $this) {
                $tenant->setOrganization(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Subscription>
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    public function addSubscription(Subscription $subscription): static
    {
        if (!$this->subscriptions->contains($subscription)) {
            $this->subscriptions->add($subscription);
            $subscription->setOrganization($this);
        }

        return $this;
    }

    public function removeSubscription(Subscription $subscription): static
    {
        if ($this->subscriptions->removeElement($subscription)) {
            if ($subscription->getOrganization() === $this) {
                $subscription->setOrganization(null);
            }
        }

        return $this;
    }

    public function getSettings(): ?array
    {
        return $this->settings;
    }

    public function setSettings(?array $settings): static
    {
        $this->settings = $settings;
        return $this;
    }

    public function getSetting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    public function setSetting(string $key, $value): static
    {
        $settings = $this->settings ?? [];
        $settings[$key] = $value;
        $this->settings = $settings;
        return $this;
    }

    public function getFeatures(): ?array
    {
        return $this->features;
    }

    public function setFeatures(?array $features): static
    {
        $this->features = $features;
        return $this;
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    public function addFeature(string $feature): static
    {
        $features = $this->features ?? [];
        if (!in_array($feature, $features)) {
            $features[] = $feature;
            $this->features = $features;
        }
        return $this;
    }

    public function removeFeature(string $feature): static
    {
        $features = $this->features ?? [];
        $key = array_search($feature, $features);
        if ($key !== false) {
            unset($features[$key]);
            $this->features = array_values($features);
        }
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

    public function getTrialEndsAt(): ?\DateTimeInterface
    {
        return $this->trialEndsAt;
    }

    public function setTrialEndsAt(?\DateTimeInterface $trialEndsAt): static
    {
        $this->trialEndsAt = $trialEndsAt;
        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
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

    public function getSubdomain(): ?string
    {
        return $this->subdomain;
    }

    public function setSubdomain(?string $subdomain): static
    {
        $this->subdomain = $subdomain;
        return $this;
    }

    /**
     * Vérifie si l'organisation est en période d'essai
     */
    public function isOnTrial(): bool
    {
        return $this->status === 'TRIAL' &&
               $this->trialEndsAt &&
               $this->trialEndsAt > new \DateTime();
    }

    /**
     * Vérifie si la période d'essai est expirée
     */
    public function isTrialExpired(): bool
    {
        return $this->status === 'TRIAL' &&
               $this->trialEndsAt &&
               $this->trialEndsAt <= new \DateTime();
    }

    /**
     * Vérifie si l'organisation a un abonnement actif
     */
    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription &&
               $this->activeSubscription->isActive();
    }

    /**
     * Vérifie si l'organisation peut utiliser l'application
     */
    public function canUseApp(): bool
    {
        return $this->isActive &&
               ($this->isOnTrial() || $this->hasActiveSubscription());
    }

    public function getLeases(): Collection
    {
        return $this->leases;
    }

    public function addLease(Lease $lease): static
    {
        if (!$this->leases->contains($lease)) {
            $this->leases->add($lease);
            $lease->setOrganization($this);
        }

        return $this;
    }

    public function removeLease(Lease $lease): static
    {
        if ($this->leases->removeElement($lease)) {
            if ($lease->getOrganization() === $this) {
                $lease->setOrganization(null);
            }
        }

        return $this;
    }

    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): static
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setOrganization($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): static
    {
        if ($this->payments->removeElement($payment)) {
            if ($payment->getOrganization() === $this) {
                $payment->setOrganization(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Company>
     */
    public function getCompanies(): Collection
    {
        return $this->companies;
    }

    public function addCompany(Company $company): static
    {
        if (!$this->companies->contains($company)) {
            $this->companies->add($company);
            $company->setOrganization($this);
        }

        return $this;
    }

    public function removeCompany(Company $company): static
    {
        if ($this->companies->removeElement($company)) {
            if ($company->getOrganization() === $this) {
                $company->setOrganization(null);
            }
        }

        return $this;
    }

    /**
     * Retourne la société principale (siège social)
     */
    public function getHeadquarterCompany(): ?Company
    {
        foreach ($this->companies as $company) {
            if ($company->isIsHeadquarter()) {
                return $company;
            }
        }
        return $this->companies->first() ?: null;
    }

    /**
     * @return Collection<int, Owner>
     */
    public function getOwners(): Collection
    {
        return $this->owners;
    }

    public function addOwner(Owner $owner): static
    {
        if (!$this->owners->contains($owner)) {
            $this->owners->add($owner);
            $owner->setOrganization($this);
        }

        return $this;
    }

    public function removeOwner(Owner $owner): static
    {
        if ($this->owners->removeElement($owner)) {
            if ($owner->getOrganization() === $this) {
                $owner->setOrganization(null);
            }
        }

        return $this;
    }
}

