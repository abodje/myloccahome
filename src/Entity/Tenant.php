<?php

namespace App\Entity;

use App\Repository\TenantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TenantRepository::class)]
class Tenant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $birthDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $postalCode = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profession = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $monthlyIncome = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $emergencyContactName = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $emergencyContactPhone = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * @var Collection<int, RentalContract>
     */
    #[ORM\OneToMany(targetEntity: RentalContract::class, mappedBy: 'tenant')]
    private Collection $rentalContracts;

    /**
     * @var Collection<int, Payment>
     */
    #[ORM\OneToMany(targetEntity: Payment::class, mappedBy: 'tenant')]
    private Collection $payments;

    public function __construct()
    {
        $this->rentalContracts = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
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

    public function getBirthDate(): ?\DateTimeInterface
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTimeInterface $birthDate): static
    {
        $this->birthDate = $birthDate;
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

    public function getProfession(): ?string
    {
        return $this->profession;
    }

    public function setProfession(?string $profession): static
    {
        $this->profession = $profession;
        return $this;
    }

    public function getMonthlyIncome(): ?string
    {
        return $this->monthlyIncome;
    }

    public function setMonthlyIncome(?string $monthlyIncome): static
    {
        $this->monthlyIncome = $monthlyIncome;
        return $this;
    }

    public function getEmergencyContactName(): ?string
    {
        return $this->emergencyContactName;
    }

    public function setEmergencyContactName(?string $emergencyContactName): static
    {
        $this->emergencyContactName = $emergencyContactName;
        return $this;
    }

    public function getEmergencyContactPhone(): ?string
    {
        return $this->emergencyContactPhone;
    }

    public function setEmergencyContactPhone(?string $emergencyContactPhone): static
    {
        $this->emergencyContactPhone = $emergencyContactPhone;
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

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
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
            $rentalContract->setTenant($this);
        }

        return $this;
    }

    public function removeRentalContract(RentalContract $rentalContract): static
    {
        if ($this->rentalContracts->removeElement($rentalContract)) {
            // set the owning side to null (unless already changed)
            if ($rentalContract->getTenant() === $this) {
                $rentalContract->setTenant(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Payment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): static
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setTenant($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): static
    {
        if ($this->payments->removeElement($payment)) {
            // set the owning side to null (unless already changed)
            if ($payment->getTenant() === $this) {
                $payment->setTenant(null);
            }
        }

        return $this;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getFullAddress(): ?string
    {
        if (!$this->address) {
            return null;
        }
        return $this->address . ', ' . $this->postalCode . ' ' . $this->city . ', ' . $this->country;
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }
}