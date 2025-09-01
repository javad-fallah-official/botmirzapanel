<?php

namespace BotMirzaPanel\Domain\Entities\Panel;

use BotMirzaPanel\Domain\ValueObjects\Panel\PanelId;
use DateTime;

/**
 * PanelConfiguration Entity
 * 
 * Represents configuration settings for a specific panel.
 * This is a child entity of the Panel aggregate.
 */
class PanelConfiguration
{
    private PanelId $panelId;
    private array $settings;
    private array $defaultUserSettings;
    private array $inboundSettings;
    private array $protocolSettings;
    private bool $autoSync;
    private int $syncInterval; // in minutes
    private bool $enableUserCreation;
    private bool $enableUserDeletion;
    private bool $enableUserModification;
    private bool $enableDataLimitEnforcement;
    private bool $enableExpiryEnforcement;
    private ?int $defaultDataLimit; // in bytes
    private ?int $defaultExpiryDays;
    private DateTime $createdAt;
    private DateTime $updatedAt;

    public function __construct(
        PanelId $panelId,
        array $settings = [],
        array $defaultUserSettings = [],
        array $inboundSettings = [],
        array $protocolSettings = []
    ) {
        $this->panelId = $panelId;
        $this->settings = $settings;
        $this->defaultUserSettings = $defaultUserSettings;
        $this->inboundSettings = $inboundSettings;
        $this->protocolSettings = $protocolSettings;
        $this->autoSync = true;
        $this->syncInterval = 60; // 1 hour
        $this->enableUserCreation = true;
        $this->enableUserDeletion = true;
        $this->enableUserModification = true;
        $this->enableDataLimitEnforcement = true;
        $this->enableExpiryEnforcement = true;
        $this->defaultDataLimit = null;
        $this->defaultExpiryDays = 30;
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    public static function create(
        PanelId $panelId,
        array $settings = [],
        array $defaultUserSettings = [],
        array $inboundSettings = [],
        array $protocolSettings = []
    ): self {
        return new self($panelId, $settings, $defaultUserSettings, $inboundSettings, $protocolSettings);
    }

    public function updateSettings(array $settings): void
    {
        $this->settings = array_merge($this->settings, $settings);
        $this->updatedAt = new DateTime();
    }

    public function updateDefaultUserSettings(array $defaultUserSettings): void
    {
        $this->defaultUserSettings = array_merge($this->defaultUserSettings, $defaultUserSettings);
        $this->updatedAt = new DateTime();
    }

    public function updateInboundSettings(array $inboundSettings): void
    {
        $this->inboundSettings = $inboundSettings;
        $this->updatedAt = new DateTime();
    }

    public function updateProtocolSettings(array $protocolSettings): void
    {
        $this->protocolSettings = $protocolSettings;
        $this->updatedAt = new DateTime();
    }

    public function enableAutoSync(int $intervalMinutes = 60): void
    {
        if ($intervalMinutes < 1) {
            throw new \InvalidArgumentException('Sync interval must be at least 1 minute.');
        }
        
        $this->autoSync = true;
        $this->syncInterval = $intervalMinutes;
        $this->updatedAt = new DateTime();
    }

    public function disableAutoSync(): void
    {
        $this->autoSync = false;
        $this->updatedAt = new DateTime();
    }

    public function enableUserCreation(): void
    {
        $this->enableUserCreation = true;
        $this->updatedAt = new DateTime();
    }

    public function disableUserCreation(): void
    {
        $this->enableUserCreation = false;
        $this->updatedAt = new DateTime();
    }

    public function enableUserDeletion(): void
    {
        $this->enableUserDeletion = true;
        $this->updatedAt = new DateTime();
    }

    public function disableUserDeletion(): void
    {
        $this->enableUserDeletion = false;
        $this->updatedAt = new DateTime();
    }

    public function enableUserModification(): void
    {
        $this->enableUserModification = true;
        $this->updatedAt = new DateTime();
    }

    public function disableUserModification(): void
    {
        $this->enableUserModification = false;
        $this->updatedAt = new DateTime();
    }

    public function enableDataLimitEnforcement(): void
    {
        $this->enableDataLimitEnforcement = true;
        $this->updatedAt = new DateTime();
    }

    public function disableDataLimitEnforcement(): void
    {
        $this->enableDataLimitEnforcement = false;
        $this->updatedAt = new DateTime();
    }

    public function enableExpiryEnforcement(): void
    {
        $this->enableExpiryEnforcement = true;
        $this->updatedAt = new DateTime();
    }

    public function disableExpiryEnforcement(): void
    {
        $this->enableExpiryEnforcement = false;
        $this->updatedAt = new DateTime();
    }

    public function updateDefaultDataLimit(?int $dataLimit): void
    {
        if ($dataLimit !== null && $dataLimit < 0) {
            throw new \InvalidArgumentException('Default data limit cannot be negative.');
        }
        
        $this->defaultDataLimit = $dataLimit;
        $this->updatedAt = new DateTime();
    }

    public function updateDefaultExpiryDays(?int $expiryDays): void
    {
        if ($expiryDays !== null && $expiryDays < 1) {
            throw new \InvalidArgumentException('Default expiry days must be at least 1.');
        }
        
        $this->defaultExpiryDays = $expiryDays;
        $this->updatedAt = new DateTime();
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    public function getDefaultUserSetting(string $key, mixed $default = null): mixed
    {
        return $this->defaultUserSettings[$key] ?? $default;
    }

    public function getInboundSetting(string $key, mixed $default = null): mixed
    {
        return $this->inboundSettings[$key] ?? $default;
    }

    public function getProtocolSetting(string $key, mixed $default = null): mixed
    {
        return $this->protocolSettings[$key] ?? $default;
    }

    public function shouldSync(): bool
    {
        return $this->autoSync;
    }

    // Getters
    public function getPanelId(): PanelId
    {
        return $this->panelId;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function getDefaultUserSettings(): array
    {
        return $this->defaultUserSettings;
    }

    public function getInboundSettings(): array
    {
        return $this->inboundSettings;
    }

    public function getProtocolSettings(): array
    {
        return $this->protocolSettings;
    }

    public function isAutoSyncEnabled(): bool
    {
        return $this->autoSync;
    }

    public function getSyncInterval(): int
    {
        return $this->syncInterval;
    }

    public function isUserCreationEnabled(): bool
    {
        return $this->enableUserCreation;
    }

    public function isUserDeletionEnabled(): bool
    {
        return $this->enableUserDeletion;
    }

    public function isUserModificationEnabled(): bool
    {
        return $this->enableUserModification;
    }

    public function isDataLimitEnforcementEnabled(): bool
    {
        return $this->enableDataLimitEnforcement;
    }

    public function isExpiryEnforcementEnabled(): bool
    {
        return $this->enableExpiryEnforcement;
    }

    public function getDefaultDataLimit(): ?int
    {
        return $this->defaultDataLimit;
    }

    public function getDefaultExpiryDays(): ?int
    {
        return $this->defaultExpiryDays;
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