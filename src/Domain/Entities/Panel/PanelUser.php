<?php

namespace BotMirzaPanel\Domain\Entities\Panel;

use BotMirzaPanel\Domain\ValueObjects\Panel\PanelId;
use BotMirzaPanel\Domain\ValueObjects\User\UserId;
use DateTime;

/**
 * PanelUser Entity
 * 
 * Represents a user within a specific panel.
 * This is a child entity of the Panel aggregate.
 */
class PanelUser
{
    private PanelId $panelId;
    private UserId $userId;
    private string $panelUsername;
    private ?string $panelUserId;
    private bool $isEnabled;
    private ?int $dataLimit; // in bytes
    private ?int $dataUsed; // in bytes
    private ?DateTime $expiryDate;
    private ?string $configUrl;
    private ?string $subscriptionUrl;
    private array $inbounds;
    private array $statistics;
    private ?DateTime $lastSyncAt;
    private DateTime $createdAt;
    private DateTime $updatedAt;

    public function __construct(
        PanelId $panelId,
        UserId $userId,
        string $panelUsername,
        ?string $panelUserId = null,
        ?int $dataLimit = null,
        ?DateTime $expiryDate = null
    ) {
        $this->panelId = $panelId;
        $this->userId = $userId;
        $this->panelUsername = $panelUsername;
        $this->panelUserId = $panelUserId;
        $this->isEnabled = true;
        $this->dataLimit = $dataLimit;
        $this->dataUsed = 0;
        $this->expiryDate = $expiryDate;
        $this->inbounds = [];
        $this->statistics = [];
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    public static function create(
        PanelId $panelId,
        UserId $userId,
        string $panelUsername,
        ?string $panelUserId = null,
        ?int $dataLimit = null,
        ?DateTime $expiryDate = null
    ): self {
        if (empty($panelUsername)) {
            throw new \InvalidArgumentException('Panel username cannot be empty.');
        }
        
        if ($dataLimit !== null && $dataLimit < 0) {
            throw new \InvalidArgumentException('Data limit cannot be negative.');
        }
        
        return new self($panelId, $userId, $panelUsername, $panelUserId, $dataLimit, $expiryDate);
    }

    public function enable(): void
    {
        $this->isEnabled = true;
        $this->updatedAt = new DateTime();
    }

    public function disable(): void
    {
        $this->isEnabled = false;
        $this->updatedAt = new DateTime();
    }

    public function updatePanelUserId(string $panelUserId): void
    {
        $this->panelUserId = $panelUserId;
        $this->updatedAt = new DateTime();
    }

    public function updateDataLimit(?int $dataLimit): void
    {
        if ($dataLimit !== null && $dataLimit < 0) {
            throw new \InvalidArgumentException('Data limit cannot be negative.');
        }
        
        $this->dataLimit = $dataLimit;
        $this->updatedAt = new DateTime();
    }

    public function updateDataUsed(int $dataUsed): void
    {
        if ($dataUsed < 0) {
            throw new \InvalidArgumentException('Data used cannot be negative.');
        }
        
        $this->dataUsed = $dataUsed;
        $this->updatedAt = new DateTime();
    }

    public function updateExpiryDate(?DateTime $expiryDate): void
    {
        $this->expiryDate = $expiryDate;
        $this->updatedAt = new DateTime();
    }

    public function updateConfigUrl(string $configUrl): void
    {
        $this->configUrl = $configUrl;
        $this->updatedAt = new DateTime();
    }

    public function updateSubscriptionUrl(string $subscriptionUrl): void
    {
        $this->subscriptionUrl = $subscriptionUrl;
        $this->updatedAt = new DateTime();
    }

    public function updateInbounds(array $inbounds): void
    {
        $this->inbounds = $inbounds;
        $this->updatedAt = new DateTime();
    }

    public function updateStatistics(array $statistics): void
    {
        $this->statistics = $statistics;
        $this->lastSyncAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    public function resetData(): void
    {
        $this->dataUsed = 0;
        $this->updatedAt = new DateTime();
    }

    public function isExpired(): bool
    {
        if ($this->expiryDate === null) {
            return false;
        }
        
        return $this->expiryDate < new DateTime();
    }

    public function isDataLimitExceeded(): bool
    {
        if ($this->dataLimit === null) {
            return false;
        }
        
        return $this->dataUsed >= $this->dataLimit;
    }

    public function isActive(): bool
    {
        return $this->isEnabled && !$this->isExpired() && !$this->isDataLimitExceeded();
    }

    public function getRemainingData(): ?int
    {
        if ($this->dataLimit === null) {
            return null;
        }
        
        return max(0, $this->dataLimit - $this->dataUsed);
    }

    public function getDataUsagePercentage(): ?float
    {
        if ($this->dataLimit === null || $this->dataLimit === 0) {
            return null;
        }
        
        return min(100.0, ($this->dataUsed / $this->dataLimit) * 100);
    }

    public function getDaysUntilExpiry(): ?int
    {
        if ($this->expiryDate === null) {
            return null;
        }
        
        $now = new DateTime();
        $diff = $now->diff($this->expiryDate);
        
        if ($this->expiryDate < $now) {
            return -$diff->days;
        }
        
        return $diff->days;
    }

    // Getters
    public function getPanelId(): PanelId
    {
        return $this->panelId;
    }

    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function getPanelUsername(): string
    {
        return $this->panelUsername;
    }

    public function getPanelUserId(): ?string
    {
        return $this->panelUserId;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function getDataLimit(): ?int
    {
        return $this->dataLimit;
    }

    public function getDataUsed(): int
    {
        return $this->dataUsed;
    }

    public function getExpiryDate(): ?DateTime
    {
        return $this->expiryDate;
    }

    public function getConfigUrl(): ?string
    {
        return $this->configUrl;
    }

    public function getSubscriptionUrl(): ?string
    {
        return $this->subscriptionUrl;
    }

    public function getInbounds(): array
    {
        return $this->inbounds;
    }

    public function getStatistics(): array
    {
        return $this->statistics;
    }

    public function getLastSyncAt(): ?DateTime
    {
        return $this->lastSyncAt;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }
}