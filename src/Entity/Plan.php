<?php

namespace App\Entity;

use App\Repository\PlanRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Plan d'abonnement SaaS
 */
#[ORM\Entity(repositoryClass: PlanRepository::class)]
class Plan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null; // Starter, Professional, Enterprise

    #[ORM\Column(length: 100, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $monthlyPrice = null; // Prix mensuel

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $yearlyPrice = null; // Prix annuel (avec réduction)

    #[ORM\Column(length: 10)]
    private ?string $currency = null; // FCFA, EUR, USD

    #[ORM\Column(nullable: true)]
    private ?int $maxProperties = null; // Nombre max de propriétés (null = illimité)

    #[ORM\Column(nullable: true)]
    private ?int $maxTenants = null; // Nombre max de locataires (null = illimité)

    #[ORM\Column(nullable: true)]
    private ?int $maxUsers = null; // Nombre max d'utilisateurs (null = illimité)

    #[ORM\Column(nullable: true)]
    private ?int $maxDocuments = null; // Nombre max de documents (null = illimité)

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $features = null; // Liste des fonctionnalités incluses

    #[ORM\Column]
    private ?int $sortOrder = null; // Ordre d'affichage

    #[ORM\Column]
    private ?bool $isActive = null;

    #[ORM\Column]
    private ?bool $isPopular = null; // Badge "Plus populaire"

    #[ORM\Column]
    private ?bool $isCustom = null; // Plan personnalisé (sur demande)

    #[ORM\Column(nullable: true)]
    private ?int $trialDays = null; // Nombre de jours d'essai gratuit

    #[ORM\OneToMany(targetEntity: Subscription::class, mappedBy: 'plan')]
    private Collection $subscriptions;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->subscriptions = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->isActive = true;
        $this->isPopular = false;
        $this->isCustom = false;
        $this->sortOrder = 0;
        $this->features = [];
        $this->trialDays = 30;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getMonthlyPrice(): ?string
    {
        return $this->monthlyPrice;
    }

    public function setMonthlyPrice(string $monthlyPrice): static
    {
        $this->monthlyPrice = $monthlyPrice;
        return $this;
    }

    public function getYearlyPrice(): ?string
    {
        return $this->yearlyPrice;
    }

    public function setYearlyPrice(?string $yearlyPrice): static
    {
        $this->yearlyPrice = $yearlyPrice;
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    public function getMaxProperties(): ?int
    {
        return $this->maxProperties;
    }

    public function setMaxProperties(?int $maxProperties): static
    {
        $this->maxProperties = $maxProperties;
        return $this;
    }

    public function getMaxTenants(): ?int
    {
        return $this->maxTenants;
    }

    public function setMaxTenants(?int $maxTenants): static
    {
        $this->maxTenants = $maxTenants;
        return $this;
    }

    public function getMaxUsers(): ?int
    {
        return $this->maxUsers;
    }

    public function setMaxUsers(?int $maxUsers): static
    {
        $this->maxUsers = $maxUsers;
        return $this;
    }

    public function getMaxDocuments(): ?int
    {
        return $this->maxDocuments;
    }

    public function setMaxDocuments(?int $maxDocuments): static
    {
        $this->maxDocuments = $maxDocuments;
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

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;
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

    public function isPopular(): ?bool
    {
        return $this->isPopular;
    }

    public function setIsPopular(bool $isPopular): static
    {
        $this->isPopular = $isPopular;
        return $this;
    }

    public function isCustom(): ?bool
    {
        return $this->isCustom;
    }

    public function setIsCustom(bool $isCustom): static
    {
        $this->isCustom = $isCustom;
        return $this;
    }

    public function getTrialDays(): ?int
    {
        return $this->trialDays;
    }

    public function setTrialDays(?int $trialDays): static
    {
        $this->trialDays = $trialDays;
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
            $subscription->setPlan($this);
        }

        return $this;
    }

    public function removeSubscription(Subscription $subscription): static
    {
        if ($this->subscriptions->removeElement($subscription)) {
            if ($subscription->getPlan() === $this) {
                $subscription->setPlan(null);
            }
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

    /**
     * Calcule la réduction annuelle en pourcentage
     */
    public function getYearlyDiscount(): float
    {
        if (!$this->yearlyPrice || !$this->monthlyPrice) {
            return 0;
        }

        $yearlyFromMonthly = (float)$this->monthlyPrice * 12;
        $yearly = (float)$this->yearlyPrice;

        return (($yearlyFromMonthly - $yearly) / $yearlyFromMonthly) * 100;
    }

    /**
     * Vérifie si une limite est atteinte
     */
    public function isLimitReached(string $limitType, int $currentCount): bool
    {
        $limit = match($limitType) {
            'properties' => $this->maxProperties,
            'tenants' => $this->maxTenants,
            'users' => $this->maxUsers,
            'documents' => $this->maxDocuments,
            default => null
        };

        return $limit !== null && $currentCount >= $limit;
    }
}

