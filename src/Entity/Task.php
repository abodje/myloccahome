<?php

namespace App\Entity;

use App\Repository\TaskRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
class Task
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 100)]
    private ?string $type = null; // RENT_RECEIPT, PAYMENT_REMINDER, MAINTENANCE_ALERT, etc.

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 100)]
    private ?string $frequency = null; // DAILY, WEEKLY, MONTHLY, YEARLY, CUSTOM

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $cronExpression = null; // Expression cron personnalisée

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $parameters = null; // Paramètres spécifiques à la tâche

    #[ORM\Column(length: 50)]
    private ?string $status = null; // ACTIVE, INACTIVE, RUNNING, COMPLETED, FAILED

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastRunAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $nextRunAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $runCount = null;

    #[ORM\Column(nullable: true)]
    private ?int $successCount = null;

    #[ORM\Column(nullable: true)]
    private ?int $failureCount = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $lastError = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $result = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = 'ACTIVE';
        $this->runCount = 0;
        $this->successCount = 0;
        $this->failureCount = 0;
        $this->parameters = [];
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
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

    public function getFrequency(): ?string
    {
        return $this->frequency;
    }

    public function setFrequency(string $frequency): static
    {
        $this->frequency = $frequency;
        return $this;
    }

    public function getCronExpression(): ?string
    {
        return $this->cronExpression;
    }

    public function setCronExpression(?string $cronExpression): static
    {
        $this->cronExpression = $cronExpression;
        return $this;
    }

    public function getParameters(): ?array
    {
        return $this->parameters;
    }

    public function setParameters(?array $parameters): static
    {
        $this->parameters = $parameters;
        return $this;
    }

    public function getParameter(string $key, $default = null)
    {
        return $this->parameters[$key] ?? $default;
    }

    public function setParameter(string $key, $value): static
    {
        $params = $this->parameters ?? [];
        $params[$key] = $value;
        $this->parameters = $params;
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

    public function getLastRunAt(): ?\DateTimeInterface
    {
        return $this->lastRunAt;
    }

    public function setLastRunAt(?\DateTimeInterface $lastRunAt): static
    {
        $this->lastRunAt = $lastRunAt;
        return $this;
    }

    public function getNextRunAt(): ?\DateTimeInterface
    {
        return $this->nextRunAt;
    }

    public function setNextRunAt(?\DateTimeInterface $nextRunAt): static
    {
        $this->nextRunAt = $nextRunAt;
        return $this;
    }

    public function getRunCount(): ?int
    {
        return $this->runCount;
    }

    public function setRunCount(int $runCount): static
    {
        $this->runCount = $runCount;
        return $this;
    }

    public function getSuccessCount(): ?int
    {
        return $this->successCount;
    }

    public function setSuccessCount(int $successCount): static
    {
        $this->successCount = $successCount;
        return $this;
    }

    public function getFailureCount(): ?int
    {
        return $this->failureCount;
    }

    public function setFailureCount(int $failureCount): static
    {
        $this->failureCount = $failureCount;
        return $this;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function setLastError(?string $lastError): static
    {
        $this->lastError = $lastError;
        return $this;
    }

    public function getResult(): ?string
    {
        return $this->result;
    }

    public function setResult(?string $result): static
    {
        $this->result = $result;
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

    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    public function isRunning(): bool
    {
        return $this->status === 'RUNNING';
    }

    public function isDue(): bool
    {
        if (!$this->isActive() || !$this->nextRunAt) {
            return false;
        }

        return $this->nextRunAt <= new \DateTime();
    }

    public function calculateNextRun(): void
    {
        $now = new \DateTime();

        switch ($this->frequency) {
            case 'DAILY':
                $this->nextRunAt = (clone $now)->modify('+1 day');
                break;
            case 'WEEKLY':
                $this->nextRunAt = (clone $now)->modify('+1 week');
                break;
            case 'MONTHLY':
                $this->nextRunAt = (clone $now)->modify('+1 month');
                break;
            case 'YEARLY':
                $this->nextRunAt = (clone $now)->modify('+1 year');
                break;
            case 'CUSTOM':
                if ($this->cronExpression) {
                    // Ici vous pourriez utiliser une bibliothèque comme cron-expression
                    // Pour la démo, on ajoute 1 heure
                    $this->nextRunAt = (clone $now)->modify('+1 hour');
                }
                break;
        }
    }

    public function markAsRunning(): void
    {
        $this->status = 'RUNNING';
        $this->updatedAt = new \DateTime();
    }

    public function markAsCompleted(?string $result = null): void
    {
        $this->status = 'ACTIVE';
        $this->lastRunAt = new \DateTime();
        $this->runCount++;
        $this->successCount++;
        $this->lastError = null;
        $this->result = $result;
        $this->calculateNextRun();
        $this->updatedAt = new \DateTime();
    }

    public function markAsFailed(string $error): void
    {
        $this->status = 'ACTIVE';
        $this->lastRunAt = new \DateTime();
        $this->runCount++;
        $this->failureCount++;
        $this->lastError = $error;
        $this->result = null; // Effacer le résultat précédent en cas d'échec
        $this->calculateNextRun();
        $this->updatedAt = new \DateTime();
    }

    public function getSuccessRate(): float
    {
        if ($this->runCount === 0) {
            return 0;
        }

        return ($this->successCount / $this->runCount) * 100;
    }

    public function __toString(): string
    {
        return $this->name ?? 'Tâche #' . $this->id;
    }
}
