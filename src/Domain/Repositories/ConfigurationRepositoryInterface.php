<?php

declare(strict_types=1);

namespace Domain\Repositories;

/**
 * Configuration repository interface for application settings persistence
 */
interface ConfigurationRepositoryInterface
{
    /**
     * Get configuration value by key
     */
    public function get(string $key, mixed $default = null): mixed;
    
    /**
     * Set configuration value
     */
    public function set(string $key, mixed $value): void;
    
    /**
     * Check if configuration key exists
     */
    public function has(string $key): bool;
    
    /**
     * Delete configuration by key
     */
    public function delete(string $key): void;
    
    /**
     * Get all configurations
     */
    public function all(): array;
    
    /**
     * Get configurations by prefix
     */
    public function getByPrefix(string $prefix): array;
    
    /**
     * Set multiple configurations
     */
    public function setMultiple(array $configurations): void;
    
    /**
     * Delete multiple configurations
     */
    public function deleteMultiple(array $keys): void;
    
    /**
     * Clear all configurations
     */
    public function clear(): void;
    
    /**
     * Get configuration with type casting
     */
    public function getString(string $key, string $default = ''): string;
    
    /**
     * Get integer configuration
     */
    public function getInt(string $key, int $default = 0): int;
    
    /**
     * Get float configuration
     */
    public function getFloat(string $key, float $default = 0.0): float;
    
    /**
     * Get boolean configuration
     */
    public function getBool(string $key, bool $default = false): bool;
    
    /**
     * Get array configuration
     */
    public function getArray(string $key, array $default = []): array;
    
    /**
     * Get JSON configuration as array
     */
    public function getJson(string $key, array $default = []): array;
    
    /**
     * Set JSON configuration from array
     */
    public function setJson(string $key, array $value): void;
    
    /**
     * Increment numeric configuration
     */
    public function increment(string $key, int $amount = 1): int;
    
    /**
     * Decrement numeric configuration
     */
    public function decrement(string $key, int $amount = 1): int;
    
    /**
     * Get configuration with expiration
     */
    public function getWithExpiration(string $key, mixed $default = null): mixed;
    
    /**
     * Set configuration with expiration
     */
    public function setWithExpiration(string $key, mixed $value, \DateTimeImmutable $expiresAt): void;
    
    /**
     * Check if configuration is expired
     */
    public function isExpired(string $key): bool;
    
    /**
     * Remove expired configurations
     */
    public function removeExpired(): int;
    
    /**
     * Get configuration metadata
     */
    public function getMetadata(string $key): ?array;
    
    /**
     * Set configuration with metadata
     */
    public function setWithMetadata(string $key, mixed $value, array $metadata): void;
    
    /**
     * Search configurations by pattern
     */
    public function search(string $pattern): array;
    
    /**
     * Get configuration history
     */
    public function getHistory(string $key, int $limit = 10): array;
    
    /**
     * Backup configurations
     */
    public function backup(): string;
    
    /**
     * Restore configurations from backup
     */
    public function restore(string $backupData): void;
    
    /**
     * Validate configuration value
     */
    public function validate(string $key, mixed $value): bool;
    
    /**
     * Get configuration schema
     */
    public function getSchema(string $key): ?array;
    
    /**
     * Set configuration schema
     */
    public function setSchema(string $key, array $schema): void;
    
    /**
     * Get default configurations
     */
    public function getDefaults(): array;
    
    /**
     * Reset configuration to default
     */
    public function resetToDefault(string $key): void;
    
    /**
     * Reset all configurations to defaults
     */
    public function resetAllToDefaults(): void;
    
    /**
     * Get configuration categories
     */
    public function getCategories(): array;
    
    /**
     * Get configurations by category
     */
    public function getByCategory(string $category): array;
    
    /**
     * Set configuration category
     */
    public function setCategory(string $key, string $category): void;
    
    /**
     * Get configuration last modified time
     */
    public function getLastModified(string $key): ?\DateTimeImmutable;
    
    /**
     * Get configurations modified since
     */
    public function getModifiedSince(\DateTimeImmutable $since): array;
    
    /**
     * Lock configuration for editing
     */
    public function lock(string $key, string $lockId): bool;
    
    /**
     * Unlock configuration
     */
    public function unlock(string $key, string $lockId): bool;
    
    /**
     * Check if configuration is locked
     */
    public function isLocked(string $key): bool;
    
    /**
     * Get configuration lock info
     */
    public function getLockInfo(string $key): ?array;
}