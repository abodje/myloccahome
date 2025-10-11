<?php

namespace App\Entity;

use App\Repository\MaintenanceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MaintenanceRepository::class)]
class Maintenance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'maintenances')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Property $property = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null; // Réparation, Maintenance préventive, Urgence, Amélioration

    #[ORM\Column(length: 50)]
    private ?string $priority = null; // Basse, Normale, Haute, Urgente

    #[ORM\Column(length: 50)]
    private ?string $status = null; // En attente, En cours, Terminé, Annulé

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $reportedDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $scheduledDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $completedDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contractorName = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $contractorPhone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contractorEmail = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $estimatedCost = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $actualCost = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $workPerformed = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reportedBy = null; // Locataire, Propriétaire, Inspection

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->reportedDate = new \DateTime();
        $this->status = 'pending';
        $this->priority = 'normal';
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

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

    public function getReportedDate(): ?\DateTimeInterface
    {
        return $this->reportedDate;
    }

    public function setReportedDate(\DateTimeInterface $reportedDate): static
    {
        $this->reportedDate = $reportedDate;

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

    public function getContractorName(): ?string
    {
        return $this->contractorName;
    }

    public function setContractorName(?string $contractorName): static
    {
        $this->contractorName = $contractorName;

        return $this;
    }

    public function getContractorPhone(): ?string
    {
        return $this->contractorPhone;
    }

    public function setContractorPhone(?string $contractorPhone): static
    {
        $this->contractorPhone = $contractorPhone;

        return $this;
    }

    public function getContractorEmail(): ?string
    {
        return $this->contractorEmail;
    }

    public function setContractorEmail(?string $contractorEmail): static
    {
        $this->contractorEmail = $contractorEmail;

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

    public function getReportedBy(): ?string
    {
        return $this->reportedBy;
    }

    public function setReportedBy(?string $reportedBy): static
    {
        $this->reportedBy = $reportedBy;

        return $this;
    }

    public function isUrgent(): bool
    {
        return $this->priority === 'urgent';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isOverdue(): bool
    {
        if (!$this->scheduledDate || $this->isCompleted()) {
            return false;
        }

        return $this->scheduledDate < new \DateTime();
    }

    public function getDurationInDays(): ?int
    {
        if (!$this->completedDate || !$this->reportedDate) {
            return null;
        }

        return $this->reportedDate->diff($this->completedDate)->days;
    }

    public function getCostVariance(): ?float
    {
        if (!$this->estimatedCost || !$this->actualCost) {
            return null;
        }

        return (float)$this->actualCost - (float)$this->estimatedCost;
    }

    public function getCostVariancePercentage(): ?float
    {
        $variance = $this->getCostVariance();
        
        if ($variance === null || (float)$this->estimatedCost == 0) {
            return null;
        }

        return ($variance / (float)$this->estimatedCost) * 100;
    }

    public function markAsCompleted(\DateTimeInterface $completedDate = null, string $workPerformed = null, string $actualCost = null): void
    {
        $this->status = 'completed';
        $this->completedDate = $completedDate ?? new \DateTime();
        
        if ($workPerformed) {
            $this->workPerformed = $workPerformed;
        }
        
        if ($actualCost) {
            $this->actualCost = $actualCost;
        }
        
        $this->updatedAt = new \DateTime();
    }

    public function getPriorityBadgeClass(): string
    {
        return match ($this->priority) {
            'low' => 'badge-secondary',
            'normal' => 'badge-primary',
            'high' => 'badge-warning',
            'urgent' => 'badge-danger',
            default => 'badge-secondary'
        };
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            'pending' => 'badge-warning',
            'in_progress' => 'badge-info',
            'completed' => 'badge-success',
            'cancelled' => 'badge-secondary',
            default => 'badge-secondary'
        };
    }

    public function __toString(): string
    {
        return $this->title ?? 'Maintenance #' . $this->id;
    }
}