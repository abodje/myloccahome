<?php

namespace App\Entity;

use App\Repository\MaintenanceRequestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MaintenanceRequestRepository::class)]
class MaintenanceRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Property $property = null;

    #[ORM\ManyToOne]
    private ?Tenant $tenant = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(length: 100)]
    private ?string $category = null; // Plomberie, Électricité, Chauffage, etc.

    #[ORM\Column(length: 50)]
    private ?string $priority = null; // Basse, Normale, Haute, Urgente

    #[ORM\Column(length: 50)]
    private ?string $status = null; // Nouvelle, En cours, Terminée, Annulée

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $requestedDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $scheduledDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $completedDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $assignedTo = null; // Nom du prestataire/technicien

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $assignedPhone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $assignedEmail = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $estimatedCost = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $actualCost = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $workPerformed = null; // Description du travail effectué

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * @var Collection<int, Document>
     */
    #[ORM\OneToMany(targetEntity: Document::class, mappedBy: 'maintenanceRequest')]
    private Collection $documents;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->status = 'Nouvelle';
        $this->priority = 'Normale';
        $this->requestedDate = new \DateTime();
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

    public function getTenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function setTenant(?Tenant $tenant): static
    {
        $this->tenant = $tenant;
        return $this;
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

    public function setDescription(string $description): static
    {
        $this->description = $description;
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

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): static
    {
        $this->priority = $priority;
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

    public function getRequestedDate(): ?\DateTimeInterface
    {
        return $this->requestedDate;
    }

    public function setRequestedDate(?\DateTimeInterface $requestedDate): static
    {
        $this->requestedDate = $requestedDate;
        return $this;
    }

    public function getScheduledDate(): ?\DateTimeInterface
    {
        return $this->scheduledDate;
    }

    public function setScheduledDate(?\DateTimeInterface $scheduledDate): static
    {
        $this->scheduledDate = $scheduledDate;
        return $this;
    }

    public function getCompletedDate(): ?\DateTimeInterface
    {
        return $this->completedDate;
    }

    public function setCompletedDate(?\DateTimeInterface $completedDate): static
    {
        $this->completedDate = $completedDate;
        return $this;
    }

    public function getAssignedTo(): ?string
    {
        return $this->assignedTo;
    }

    public function setAssignedTo(?string $assignedTo): static
    {
        $this->assignedTo = $assignedTo;
        return $this;
    }

    public function getAssignedPhone(): ?string
    {
        return $this->assignedPhone;
    }

    public function setAssignedPhone(?string $assignedPhone): static
    {
        $this->assignedPhone = $assignedPhone;
        return $this;
    }

    public function getAssignedEmail(): ?string
    {
        return $this->assignedEmail;
    }

    public function setAssignedEmail(?string $assignedEmail): static
    {
        $this->assignedEmail = $assignedEmail;
        return $this;
    }

    public function getEstimatedCost(): ?string
    {
        return $this->estimatedCost;
    }

    public function setEstimatedCost(?string $estimatedCost): static
    {
        $this->estimatedCost = $estimatedCost;
        return $this;
    }

    public function getActualCost(): ?string
    {
        return $this->actualCost;
    }

    public function setActualCost(?string $actualCost): static
    {
        $this->actualCost = $actualCost;
        return $this;
    }

    public function getWorkPerformed(): ?string
    {
        return $this->workPerformed;
    }

    public function setWorkPerformed(?string $workPerformed): static
    {
        $this->workPerformed = $workPerformed;
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

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
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
            $document->setMaintenanceRequest($this);
        }
        return $this;
    }

    public function removeDocument(Document $document): static
    {
        if ($this->documents->removeElement($document)) {
            if ($document->getMaintenanceRequest() === $this) {
                $document->setMaintenanceRequest(null);
            }
        }
        return $this;
    }

    public function isUrgent(): bool
    {
        return $this->priority === 'Urgente';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'Terminée';
    }

    public function isOverdue(): bool
    {
        if ($this->isCompleted() || !$this->scheduledDate) {
            return false;
        }

        $now = new \DateTime();
        return $this->scheduledDate < $now;
    }

    public function getDaysOverdue(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }

        $now = new \DateTime();
        $interval = $this->scheduledDate->diff($now);
        return $interval->days;
    }

    public function markAsCompleted(\DateTimeInterface $completedDate = null, string $workPerformed = null): void
    {
        $this->status = 'Terminée';
        $this->completedDate = $completedDate ?? new \DateTime();

        if ($workPerformed) {
            $this->workPerformed = $workPerformed;
        }

        $this->updatedAt = new \DateTime();
    }

    public function getPriorityColor(): string
    {
        return match($this->priority) {
            'Basse' => 'success',
            'Normale' => 'info',
            'Haute' => 'warning',
            'Urgente' => 'danger',
            default => 'secondary'
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'Nouvelle' => 'primary',
            'En cours' => 'warning',
            'Terminée' => 'success',
            'Annulée' => 'secondary',
            default => 'info'
        };
    }

    public function __toString(): string
    {
        return sprintf(
            '#%d - %s (%s)',
            $this->id,
            $this->title,
            $this->status
        );
    }
}
