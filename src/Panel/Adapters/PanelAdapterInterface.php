<?php

namespace BotMirzaPanel\Panel\Adapters;

/**
 * Interface for all panel adapters
 * Defines the contract that all panel integrations must implement
 */
interface PanelAdapterInterface
{
    /**
     * Configure the adapter with panel settings
     */
    public function configure(array $config): void;

    /**
     * Test connection to the panel
     */
    public function testConnection(): bool;

    /**
     * Get panel information and status
     */
    public function getPanelInfo(): array;

    /**
     * Get adapter capabilities
     */
    public function getCapabilities(): array;

    /**
     * Create a new user on the panel
     */
    public function createUser(array $userData): array;

    /**
     * Update an existing user
     */
    public function updateUser(string $username, array $userData): array;

    /**
     * Delete a user from the panel
     */
    public function deleteUser(string $username): bool;

    /**
     * Get user information
     */
    public function getUser(string $username): ?array;

    /**
     * Get all users from the panel
     */
    public function getAllUsers(): array;

    /**
     * Get user configuration file/settings
     */
    public function getUserConfig(string $username): ?string;

    /**
     * Get user statistics (data usage, connections, etc.)
     */
    public function getUserStats(string $username): ?array;

    /**
     * Enable a user
     */
    public function enableUser(string $username): bool;

    /**
     * Disable a user
     */
    public function disableUser(string $username): bool;

    /**
     * Reset user data usage
     */
    public function resetUserData(string $username): bool;

    /**
     * Get available inbounds/protocols
     */
    public function getInbounds(): array;

    /**
     * Get system statistics
     */
    public function getSystemStats(): array;
}