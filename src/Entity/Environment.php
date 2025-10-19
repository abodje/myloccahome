<?php

namespace App\Entity;

use App\Repository\EnvironmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: EnvironmentRepository::class)]
#[ORM\Table(name: 'environment')]
class Environment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $subdomain = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null; // PRODUCTION, STAGING, DEVELOPMENT

    #[ORM\Column(length: 50)]
    private ?string $status = null; // ACTIVE, INACTIVE, SUSPENDED, CREATING, ERROR

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Organization $organization = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $domain = null; // Domaine personnalisé (ex: client.com)

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $configuration = null; // Configuration spécifique à l'environnement

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastDeployedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $deploymentLog = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $version = null; // Version déployée

    #[ORM\Column(nullable: true)]
    private ?bool $sslEnabled = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $environmentVariables = null; // Variables d'environnement

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = 'CREATING';
        $this->configuration = [];
        $this->environmentVariables = [];
        $this->sslEnabled = false;
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

    public function getSubdomain(): ?string
    {
        return $this->subdomain;
    }

    public function setSubdomain(string $subdomain): static
    {
        $this->subdomain = $subdomain;
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function setDomain(?string $domain): static
    {
        $this->domain = $domain;
        return $this;
    }

    public function getConfiguration(): ?array
    {
        return $this->configuration;
    }

    public function setConfiguration(?array $configuration): static
    {
        $this->configuration = $configuration;
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

    public function getLastDeployedAt(): ?\DateTimeInterface
    {
        return $this->lastDeployedAt;
    }

    public function setLastDeployedAt(?\DateTimeInterface $lastDeployedAt): static
    {
        $this->lastDeployedAt = $lastDeployedAt;
        return $this;
    }

    public function getDeploymentLog(): ?string
    {
        return $this->deploymentLog;
    }

    public function setDeploymentLog(?string $deploymentLog): static
    {
        $this->deploymentLog = $deploymentLog;
        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(?string $version): static
    {
        $this->version = $version;
        return $this;
    }

    public function isSslEnabled(): ?bool
    {
        return $this->sslEnabled;
    }

    public function setSslEnabled(?bool $sslEnabled): static
    {
        $this->sslEnabled = $sslEnabled;
        return $this;
    }

    public function getEnvironmentVariables(): ?array
    {
        return $this->environmentVariables;
    }

    public function setEnvironmentVariables(?array $environmentVariables): static
    {
        $this->environmentVariables = $environmentVariables;
        return $this;
    }

    public function getUrl(): string
    {
        if ($this->domain) {
            return ($this->sslEnabled ? 'https' : 'http') . '://' . $this->domain;
        }

        // Le domaine de base sera injecté par le service
        return 'https://' . $this->subdomain . '.mylocca.com';
    }

    public function getUrlWithBaseDomain(string $baseDomain): string
    {
        if ($this->domain) {
            return ($this->sslEnabled ? 'https' : 'http') . '://' . $this->domain;
        }

        return 'https://' . $this->subdomain . '.' . $baseDomain;
    }

    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    public function isProduction(): bool
    {
        return $this->type === 'PRODUCTION';
    }

    public function isStaging(): bool
    {
        return $this->type === 'STAGING';
    }

    public function isDevelopment(): bool
    {
        return $this->type === 'DEVELOPMENT';
    }

    public function __toString(): string
    {
        return $this->name . ' (' . $this->subdomain . ')';
    }
}
