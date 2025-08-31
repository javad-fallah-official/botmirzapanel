<?php

declare(strict_types=1);

namespace BotMirzaPanel\Infrastructure\External\Panel;

use BotMirzaPanel\Domain\Entities\Panel\User as PanelUser;
use BotMirzaPanel\Domain\ValueObjects\Panel\PanelConfig;
use BotMirzaPanel\Domain\ValueObjects\Panel\TrafficUsage;

/**
 * Panel Adapter Interface
 * 
 * Defines the contract for all panel adapter implementations
 */
interface PanelAdapterInterface
{
    /**
     * Get the adapter name
     */
    public function getName(): string;
    
    /**
     * Get the adapter display name
     */
    public function getDisplayName(): string;
    
    /**
     * Get the adapter description
     */
    public function getDescription(): string;
    
    /**
     * Check if the adapter is properly configured
     */
    public function isConfigured(): bool;
    
    /**
     * Test connection to the panel
     */
    public function testConnection(): array;
    
    /**
     * Get configuration requirements for this adapter
     */
    public function getConfigurationFields(): array;
    
    /**
     * Validate adapter configuration
     */
    public function validateConfiguration(array $config): bool;
    
    /**
     * Create a new user on the panel
     */
    public function createUser(PanelUser $user): array;
    
    /**
     * Update an existing user on the panel
     */
    public function updateUser(PanelUser $user): array;
    
    /**
     * Delete a user from the panel
     */
    public function deleteUser(string $username): array;
    
    /**
     * Get user information from the panel
     */
    public function getUser(string $username): array;
    
    /**
     * Get all users from the panel
     */
    public function getAllUsers(): array;
    
    /**
     * Enable a user on the panel
     */
    public function enableUser(string $username): array;
    
    /**
     * Disable a user on the panel
     */
    public function disableUser(string $username): array;
    
    /**
     * Reset user traffic usage
     */
    public function resetUserTraffic(string $username): array;
    
    /**
     * Get user traffic usage
     */
    public function getUserTraffic(string $username): TrafficUsage;
    
    /**
     * Get panel system information
     */
    public function getSystemInfo(): array;
    
    /**
     * Get panel statistics
     */
    public function getStatistics(): array;
    
    /**
     * Get available inbounds/protocols
     */
    public function getInbounds(): array;
    
    /**
     * Create a new inbound
     */
    public function createInbound(array $inboundData): array;
    
    /**
     * Update an existing inbound
     */
    public function updateInbound(int $inboundId, array $inboundData): array;
    
    /**
     * Delete an inbound
     */
    public function deleteInbound(int $inboundId): array;
    
    /**
     * Get panel configuration
     */
    public function getPanelConfig(): PanelConfig;
    
    /**
     * Update panel configuration
     */
    public function updatePanelConfig(PanelConfig $config): array;
    
    /**
     * Generate user configuration (subscription link, QR code, etc.)
     */
    public function generateUserConfig(string $username, array $options = []): array;
    
    /**
     * Get user subscription link
     */
    public function getUserSubscriptionLink(string $username): string;
    
    /**
     * Backup panel data
     */
    public function backup(): array;
    
    /**
     * Restore panel data from backup
     */
    public function restore(string $backupData): array;
    
    /**
     * Get panel logs
     */
    public function getLogs(int $limit = 100): array;
    
    /**
     * Clear panel logs
     */
    public function clearLogs(): array;
    
    /**
     * Restart panel service
     */
    public function restartPanel(): array;
    
    /**
     * Update panel software
     */
    public function updatePanel(): array;
    
    /**
     * Get supported features by this adapter
     */
    public function getSupportedFeatures(): array;
    
    /**
     * Check if a specific feature is supported
     */
    public function supportsFeature(string $feature): bool;
}