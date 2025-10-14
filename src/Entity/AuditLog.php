<?php

namespace App\Entity;

use App\Repository\AuditLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\Index(columns: ['created_at'], name: 'idx_audit_created_at')]
#[ORM\Index(columns: ['user_id'], name: 'idx_audit_user')]
#[ORM\Index(columns: ['entity_type'], name: 'idx_audit_entity_type')]
#[ORM\Index(columns: ['action'], name: 'idx_audit_action')]
class AuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(length: 100)]
    private ?string $action = null;

    #[ORM\Column(length: 100)]
    private ?string $entityType = null;

    #[ORM\Column(nullable: true)]
    private ?int $entityId = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $oldValues = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $newValues = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Organization $organization = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Company $company = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    public function getEntityType(): ?string
    {
        return $this->entityType;
    }

    public function setEntityType(string $entityType): static
    {
        $this->entityType = $entityType;
        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(?int $entityId): static
    {
        $this->entityId = $entityId;
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

    public function getOldValues(): ?array
    {
        return $this->oldValues;
    }

    public function setOldValues(?array $oldValues): static
    {
        $this->oldValues = $oldValues;
        return $this;
    }

    public function getNewValues(): ?array
    {
        return $this->newValues;
    }

    public function setNewValues(?array $newValues): static
    {
        $this->newValues = $newValues;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
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

    /**
     * Retourne une description lisible de l'action
     */
    public function getActionLabel(): string
    {
        return match($this->action) {
            'CREATE' => 'Création',
            'UPDATE' => 'Modification',
            'DELETE' => 'Suppression',
            'VIEW' => 'Consultation',
            'LOGIN' => 'Connexion',
            'LOGOUT' => 'Déconnexion',
            'DOWNLOAD' => 'Téléchargement',
            'EXPORT' => 'Export',
            'IMPORT' => 'Import',
            'SEND_EMAIL' => 'Envoi email',
            'SEND_SMS' => 'Envoi SMS',
            default => $this->action
        };
    }

    /**
     * Retourne le badge CSS selon l'action
     */
    public function getActionBadgeClass(): string
    {
        return match($this->action) {
            'CREATE' => 'success',
            'UPDATE' => 'warning',
            'DELETE' => 'danger',
            'VIEW' => 'info',
            'LOGIN' => 'primary',
            'LOGOUT' => 'secondary',
            'DOWNLOAD', 'EXPORT' => 'info',
            'SEND_EMAIL', 'SEND_SMS' => 'primary',
            default => 'secondary'
        };
    }

    /**
     * Retourne l'icône selon l'action
     */
    public function getActionIcon(): string
    {
        return match($this->action) {
            'CREATE' => 'bi-plus-circle',
            'UPDATE' => 'bi-pencil',
            'DELETE' => 'bi-trash',
            'VIEW' => 'bi-eye',
            'LOGIN' => 'bi-box-arrow-in-right',
            'LOGOUT' => 'bi-box-arrow-right',
            'DOWNLOAD' => 'bi-download',
            'EXPORT' => 'bi-file-earmark-arrow-down',
            'IMPORT' => 'bi-file-earmark-arrow-up',
            'SEND_EMAIL' => 'bi-envelope',
            'SEND_SMS' => 'bi-phone',
            default => 'bi-activity'
        };
    }

    /**
     * Retourne le nom d'affichage de l'entité
     */
    public function getEntityTypeLabel(): string
    {
        return match($this->entityType) {
            'Property' => 'Bien',
            'Tenant' => 'Locataire',
            'Lease' => 'Bail',
            'Payment' => 'Paiement',
            'MaintenanceRequest' => 'Maintenance',
            'Document' => 'Document',
            'User' => 'Utilisateur',
            'Expense' => 'Dépense',
            'Currency' => 'Devise',
            'Organization' => 'Organisation',
            'Company' => 'Société',
            default => $this->entityType
        };
    }
}

