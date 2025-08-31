<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Panel\Panel;
use Domain\Entities\Panel\PanelUser;
use Domain\ValueObjects\Panel\PanelId;
use Domain\ValueObjects\Panel\PanelType;
use Domain\ValueObjects\User\UserId;
use Domain\ValueObjects\Common\Url;
use Domain\ValueObjects\Common\DateTimeRange;

/**
 * Panel repository interface for data persistence operations
 */
interface PanelRepositoryInterface
{
    /**
     * Find panel by ID
     */
    public function findById(PanelId $id): ?Panel;
    
    /**
     * Find panel by name
     */
    public function findByName(string $name): ?Panel;
    
    /**
     * Find panel by URL
     */
    public function findByUrl(Url $url): ?Panel;
    
    /**
     * Find panels by type
     */
    public function findByType(PanelType $type, int $limit = 100, int $offset = 0): array;
    
    /**
     * Find active panels
     */
    public function findActivePanels(int $limit = 100, int $offset = 0): array;
    
    /**
     * Find inactive panels
     */
    public function findInactivePanels(int $limit = 100, int $offset = 0): array;
    
    /**
     * Check if panel name exists
     */
    public function nameExists(string $name): bool;
    
    /**
     * Check if panel URL exists
     */
    public function urlExists(Url $url): bool;
    
    /**
     * Save panel (create or update)
     */
    public function save(Panel $panel): void;
    
    /**
     * Delete panel
     */
    public function delete(PanelId $id): void;
    
    /**
     * Find panel user by ID
     */
    public function findPanelUserById(string $panelUserId): ?PanelUser;
    
    /**
     * Find panel user by panel and user ID
     */
    public function findPanelUserByPanelAndUser(PanelId $panelId, UserId $userId): ?PanelUser;
    
    /**
     * Find panel user by username
     */
    public function findPanelUserByUsername(PanelId $panelId, string $username): ?PanelUser;
    
    /**
     * Find panel users by panel
     */
    public function findPanelUsersByPanel(PanelId $panelId, int $limit = 100, int $offset = 0): array;
    
    /**
     * Find panel users by user
     */
    public function findPanelUsersByUser(UserId $userId, int $limit = 100, int $offset = 0): array;
    
    /**
     * Find active panel users
     */
    public function findActivePanelUsers(PanelId $panelId, int $limit = 100, int $offset = 0): array;
    
    /**
     * Find suspended panel users
     */
    public function findSuspendedPanelUsers(PanelId $panelId, int $limit = 100, int $offset = 0): array;
    
    /**
     * Find expired panel users
     */
    public function findExpiredPanelUsers(PanelId $panelId, \DateTimeImmutable $asOf, int $limit = 100): array;
    
    /**
     * Find panel users expiring soon
     */
    public function findPanelUsersExpiringSoon(PanelId $panelId, \DateTimeImmutable $before, int $limit = 100): array;
    
    /**
     * Check if panel username exists
     */
    public function panelUsernameExists(PanelId $panelId, string $username): bool;
    
    /**
     * Save panel user (create or update)
     */
    public function savePanelUser(PanelUser $panelUser): void;
    
    /**
     * Delete panel user
     */
    public function deletePanelUser(string $panelUserId): void;
    
    /**
     * Count total panels
     */
    public function countTotal(): int;
    
    /**
     * Count panels by type
     */
    public function countByType(PanelType $type): int;
    
    /**
     * Count active panels
     */
    public function countActivePanels(): int;
    
    /**
     * Count panel users by panel
     */
    public function countPanelUsersByPanel(PanelId $panelId): int;
    
    /**
     * Count active panel users by panel
     */
    public function countActivePanelUsersByPanel(PanelId $panelId): int;
    
    /**
     * Count panel users by user
     */
    public function countPanelUsersByUser(UserId $userId): int;
    
    /**
     * Get panel statistics
     */
    public function getPanelStatistics(PanelId $panelId): array;
    
    /**
     * Get panel user statistics
     */
    public function getPanelUserStatistics(PanelId $panelId, DateTimeRange $dateRange): array;
    
    /**
     * Get panel usage summary
     */
    public function getPanelUsageSummary(PanelId $panelId, DateTimeRange $dateRange): array;
    
    /**
     * Find panels for health check
     */
    public function findPanelsForHealthCheck(): array;
    
    /**
     * Update panel last health check
     */
    public function updateLastHealthCheck(PanelId $panelId, \DateTimeImmutable $lastCheck, bool $isHealthy): void;
    
    /**
     * Find panels with failed health checks
     */
    public function findPanelsWithFailedHealthChecks(\DateTimeImmutable $since): array;
    
    /**
     * Search panels by criteria
     */
    public function searchPanels(array $criteria, int $limit = 100, int $offset = 0): array;
    
    /**
     * Search panel users by criteria
     */
    public function searchPanelUsers(array $criteria, int $limit = 100, int $offset = 0): array;
    
    /**
     * Find panels for synchronization
     */
    public function findPanelsForSync(): array;
    
    /**
     * Update panel last sync
     */
    public function updateLastSync(PanelId $panelId, \DateTimeImmutable $lastSync): void;
    
    /**
     * Find panel users for bulk operations
     */
    public function findPanelUsersForBulkOperation(array $panelUserIds): array;
    
    /**
     * Update panel user data usage
     */
    public function updatePanelUserDataUsage(string $panelUserId, int $usedBytes): void;
    
    /**
     * Get panel users with high data usage
     */
    public function findPanelUsersWithHighDataUsage(PanelId $panelId, int $thresholdBytes, int $limit = 100): array;
}