<?php

namespace App\Entity;

use App\Repository\TenantApplicationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Candidature de locataire avec informations détaillées
 */
#[ORM\Entity(repositoryClass: TenantApplicationRepository::class)]
class TenantApplication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Property::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Property $property = null;

    #[ORM\OneToOne(targetEntity: Visit::class, inversedBy: 'application')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Visit $visit = null;

    // Informations personnelles
    #[ORM\Column(length: 100)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    private ?string $lastName = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $birthDate = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column(length: 20)]
    private ?string $phone = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $currentAddress = null;

    // Situation professionnelle
    #[ORM\Column(length: 50)]
    private ?string $employmentStatus = null; // employed, self_employed, student, retired, unemployed

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $employer = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $jobTitle = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $monthlyIncome = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $contractType = null; // cdi, cdd, interim, freelance

    // Garanties
    #[ORM\Column]
    private ?bool $hasGuarantor = false;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $guarantorName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $guarantorRelation = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $guarantorIncome = null;

    // Composition du foyer
    #[ORM\Column]
    private ?int $numberOfOccupants = 1;

    #[ORM\Column]
    private ?int $numberOfChildren = 0;

    #[ORM\Column]
    private ?bool $hasPets = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $petDetails = null;

    // Informations complémentaires
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $desiredMoveInDate = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $desiredLeaseDuration = 12; // en mois

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $additionalInfo = null;

    // Documents fournis
    #[ORM\Column(type: Types::JSON)]
    private array $documents = [];

    // Scoring et statut
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $score = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $scoreDetails = null;

    #[ORM\Column(length: 20)]
    private ?string $status = 'pending'; // pending, under_review, approved, rejected, withdrawn

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reviewNotes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $reviewedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $reviewedBy = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Organization $organization = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
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

    public function getVisit(): ?Visit
    {
        return $this->visit;
    }

    public function setVisit(?Visit $visit): static
    {
        $this->visit = $visit;
        return $this;
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

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getBirthDate(): ?\DateTimeInterface
    {
        return $this->birthDate;
    }

    public function setBirthDate(\DateTimeInterface $birthDate): static
    {
        $this->birthDate = $birthDate;
        return $this;
    }

    public function getAge(): ?int
    {
        if (!$this->birthDate) {
            return null;
        }
        return $this->birthDate->diff(new \DateTime())->y;
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

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getCurrentAddress(): ?string
    {
        return $this->currentAddress;
    }

    public function setCurrentAddress(?string $currentAddress): static
    {
        $this->currentAddress = $currentAddress;
        return $this;
    }

    public function getEmploymentStatus(): ?string
    {
        return $this->employmentStatus;
    }

    public function setEmploymentStatus(string $employmentStatus): static
    {
        $this->employmentStatus = $employmentStatus;
        return $this;
    }

    public function getEmployer(): ?string
    {
        return $this->employer;
    }

    public function setEmployer(?string $employer): static
    {
        $this->employer = $employer;
        return $this;
    }

    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function setJobTitle(?string $jobTitle): static
    {
        $this->jobTitle = $jobTitle;
        return $this;
    }

    public function getMonthlyIncome(): ?string
    {
        return $this->monthlyIncome;
    }

    public function setMonthlyIncome(string $monthlyIncome): static
    {
        $this->monthlyIncome = $monthlyIncome;
        return $this;
    }

    public function getContractType(): ?string
    {
        return $this->contractType;
    }

    public function setContractType(?string $contractType): static
    {
        $this->contractType = $contractType;
        return $this;
    }

    public function isHasGuarantor(): ?bool
    {
        return $this->hasGuarantor;
    }

    public function setHasGuarantor(bool $hasGuarantor): static
    {
        $this->hasGuarantor = $hasGuarantor;
        return $this;
    }

    public function getGuarantorName(): ?string
    {
        return $this->guarantorName;
    }

    public function setGuarantorName(?string $guarantorName): static
    {
        $this->guarantorName = $guarantorName;
        return $this;
    }

    public function getGuarantorRelation(): ?string
    {
        return $this->guarantorRelation;
    }

    public function setGuarantorRelation(?string $guarantorRelation): static
    {
        $this->guarantorRelation = $guarantorRelation;
        return $this;
    }

    public function getGuarantorIncome(): ?string
    {
        return $this->guarantorIncome;
    }

    public function setGuarantorIncome(?string $guarantorIncome): static
    {
        $this->guarantorIncome = $guarantorIncome;
        return $this;
    }

    public function getNumberOfOccupants(): ?int
    {
        return $this->numberOfOccupants;
    }

    public function setNumberOfOccupants(int $numberOfOccupants): static
    {
        $this->numberOfOccupants = $numberOfOccupants;
        return $this;
    }

    public function getNumberOfChildren(): ?int
    {
        return $this->numberOfChildren;
    }

    public function setNumberOfChildren(int $numberOfChildren): static
    {
        $this->numberOfChildren = $numberOfChildren;
        return $this;
    }

    public function isHasPets(): ?bool
    {
        return $this->hasPets;
    }

    public function setHasPets(bool $hasPets): static
    {
        $this->hasPets = $hasPets;
        return $this;
    }

    public function getPetDetails(): ?string
    {
        return $this->petDetails;
    }

    public function setPetDetails(?string $petDetails): static
    {
        $this->petDetails = $petDetails;
        return $this;
    }

    public function getDesiredMoveInDate(): ?\DateTimeInterface
    {
        return $this->desiredMoveInDate;
    }

    public function setDesiredMoveInDate(\DateTimeInterface $desiredMoveInDate): static
    {
        $this->desiredMoveInDate = $desiredMoveInDate;
        return $this;
    }

    public function getDesiredLeaseDuration(): ?int
    {
        return $this->desiredLeaseDuration;
    }

    public function setDesiredLeaseDuration(?int $desiredLeaseDuration): static
    {
        $this->desiredLeaseDuration = $desiredLeaseDuration;
        return $this;
    }

    public function getAdditionalInfo(): ?string
    {
        return $this->additionalInfo;
    }

    public function setAdditionalInfo(?string $additionalInfo): static
    {
        $this->additionalInfo = $additionalInfo;
        return $this;
    }

    public function getDocuments(): array
    {
        return $this->documents;
    }

    public function setDocuments(array $documents): static
    {
        $this->documents = $documents;
        return $this;
    }

    public function getScore(): ?string
    {
        return $this->score;
    }

    public function setScore(?string $score): static
    {
        $this->score = $score;
        return $this;
    }

    public function getScoreDetails(): ?array
    {
        return $this->scoreDetails;
    }

    public function setScoreDetails(?array $scoreDetails): static
    {
        $this->scoreDetails = $scoreDetails;
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

    public function getReviewNotes(): ?string
    {
        return $this->reviewNotes;
    }

    public function setReviewNotes(?string $reviewNotes): static
    {
        $this->reviewNotes = $reviewNotes;
        return $this;
    }

    public function getReviewedAt(): ?\DateTimeInterface
    {
        return $this->reviewedAt;
    }

    public function setReviewedAt(?\DateTimeInterface $reviewedAt): static
    {
        $this->reviewedAt = $reviewedAt;
        return $this;
    }

    public function getReviewedBy(): ?User
    {
        return $this->reviewedBy;
    }

    public function setReviewedBy(?User $reviewedBy): static
    {
        $this->reviewedBy = $reviewedBy;
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

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): static
    {
        $this->organization = $organization;
        return $this;
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'under_review' => 'En cours d\'examen',
            'approved' => 'Approuvée',
            'rejected' => 'Rejetée',
            'withdrawn' => 'Retirée',
            default => 'Inconnu'
        };
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'under_review' => 'info',
            'approved' => 'success',
            'rejected' => 'danger',
            'withdrawn' => 'secondary',
            default => 'light'
        };
    }

    public function getIncomeToRentRatio(): ?float
    {
        if (!$this->monthlyIncome || !$this->property || !$this->property->getMonthlyRent()) {
            return null;
        }

        return (float)$this->monthlyIncome / (float)$this->property->getMonthlyRent();
    }
}
