<?php

namespace BotMirzaPanel\Domain\Entities\Panel;

use BotMirzaPanel\Domain\ValueObjects\Panel\PanelId;
use BotMirzaPanel\Domain\ValueObjects\Panel\PanelType;
use BotMirzaPanel\Domain\ValueObjects\Panel\ConnectionConfig;
use DateTime;

/**
 * Panel Aggregate Root
 * 
 * Represents a VPN panel integration in the system.
 * This is the main entry point for all panel-related operations.
 */
class Panel
{
    private PanelId $id;
    private string $name;
    private PanelType $type;
    private ConnectionConfig $connectionConfig;
    private bool $isActive;
    private bool $isHealthy;
    private ?DateTime $lastHealthCheck;
    private ?string $lastHealthCheckError;
    private array $capabilities;
    private array $statistics;
    private DateTime $createdAt;
    private DateTime $updatedAt;
    private array $domainEvents = [];
    
    private array $panelUsers = [];
    private ?PanelConfiguration $configuration;

    public function __construct(
        PanelId $id,
        string $name,
        PanelType $type,
        ConnectionConfig $connectionConfig,
        array $capabilities = []
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->type = $type;
        $this->connectionConfig = $connectionConfig;
        $this->isActive = false;
        $this->isHealthy = false;
        $this->capabilities = $capabilities;
        $this->statistics = [];
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    public static function create(
        PanelId $id,
        string $name,
        PanelType $type,
        ConnectionConfig $connectionConfig,
        array $capabilities = []
    ): self {
        if (empty($name)) {
            throw new \InvalidArgumentException('Panel name cannot be empty.');
        }
        
        return new self($id, $name, $type, $connectionConfig, $capabilities);
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new DateTime();
    }

    public function deactivate(): void
    {
        $this->isActive = false;
        $this->updatedAt = new DateTime();
    }

    public function updateConnectionConfig(ConnectionConfig $config): void
    {
        $this->connectionConfig = $config;
        $this->updatedAt = new DateTime();
        
        // Reset health status when connection config changes
        $this->isHealthy = false;
        $this->lastHealthCheck = null;
        $this->lastHealthCheckError = null;
    }

    public function updateHealthStatus(bool $isHealthy, ?string $error = null): void
    {
        $this->isHealthy = $isHealthy;
        $this->lastHealthCheck = new DateTime();
        $this->lastHealthCheckError = $error;
        $this->updatedAt = new DateTime();
    }

    public function updateCapabilities(array $capabilities): void
    {
        $this->capabilities = $capabilities;
        $this->updatedAt = new DateTime();
    }

    public function updateStatistics(array $statistics): void
    {
        $this->statistics = $statistics;
        $this->updatedAt = new DateTime();
    }

    public function setConfiguration(PanelConfiguration $configuration): void
    {
        $this->configuration = $configuration;
        $this->updatedAt = new DateTime();
    }

    public function addPanelUser(PanelUser $panelUser): void
    {
        // Check if user already exists
        foreach ($this->panelUsers as $existingUser) {
            if ($existingUser->getUserId()->equals($panelUser->getUserId())) {
                throw new \DomainException('User already exists in this panel.');
            }
        }
        
        $this->panelUsers[] = $panelUser;
        $this->updatedAt = new DateTime();
    }

    public function removePanelUser(PanelUser $panelUser): void
    {
        $this->panelUsers = array_filter(
            $this->panelUsers,
            fn($user) => !$user->getUserId()->equals($panelUser->getUserId())
        );
        $this->updatedAt = new DateTime();
    }

    public function getPanelUserByUserId($userId): ?PanelUser
    {
        foreach ($this->panelUsers as $panelUser) {
            if ($panelUser->getUserId()->equals($userId)) {
                return $panelUser;
            }
        }
        return null;
    }

    public function hasCapability(string $capability): bool
    {
        return in_array($capability, $this->capabilities);
    }

    public function canCreateUsers(): bool
    {
        return $this->hasCapability('create_user');
    }

    public function canUpdateUsers(): bool
    {
        return $this->hasCapability('update_user');
    }

    public function canDeleteUsers(): bool
    {
        return $this->hasCapability('delete_user');
    }

    public function canGetUserConfig(): bool
    {
        return $this->hasCapability('get_user_config');
    }

    public function canGetUserStats(): bool
    {
        return $this->hasCapability('get_user_stats');
    }

    public function isOperational(): bool
    {
        return $this->isActive && $this->isHealthy;
    }

    public function needsHealthCheck(): bool
    {
        if ($this->lastHealthCheck === null) {
            return true;
        }
        
        // Check if last health check was more than 5 minutes ago
        $fiveMinutesAgo = new DateTime('-5 minutes');
        return $this->lastHealthCheck < $fiveMinutesAgo;
    }

    // Getters
    public function getId(): PanelId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): PanelType
    {
        return $this->type;
    }

    public function getConnectionConfig(): ConnectionConfig
    {
        return $this->connectionConfig;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isHealthy(): bool
    {
        return $this->isHealthy;
    }

    public function getLastHealthCheck(): ?DateTime
    {
        return $this->lastHealthCheck;
    }

    public function getLastHealthCheckError(): ?string
    {
        return $this->lastHealthCheckError;
    }

    public function getCapabilities(): array
    {
        return $this->capabilities;
    }

    public function getStatistics(): array
    {
        return $this->statistics;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function getPanelUsers(): array
    {
        return $this->panelUsers;
    }

    public function getConfiguration(): ?PanelConfiguration
    {
        return $this->configuration;
    }

    // Domain Events
    public function getDomainEvents(): array
    {
        return $this->domainEvents;
    }

    public function clearDomainEvents(): void
    {
        $this->domainEvents = [];
    }

    private function addDomainEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }
}