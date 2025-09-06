<?php
// WARNING: SQL injection vulnerability detected in this file
// Please review and use prepared statements or secure_* functions


declare(strict_types=1);

namespace BotMirzaPanel\Domain\Repositories;

use BotMirzaPanel\Domain\Entities\User\User;
use BotMirzaPanel\Domain\ValueObjects\User\UserId;
use BotMirzaPanel\Domain\ValueObjects\User\TelegramId;
use BotMirzaPanel\Domain\ValueObjects\Common\Email;
use BotMirzaPanel\Shared\Contracts\RepositoryInterface;

/**
 * User repository interface
 * Defines user-specific repository operations
 */
interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * Find user by ID
     */
    public function findById(UserId $id): ?User;

    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?User;

    /**
     * Find user by email
     */
    public function findByEmail(Email $email): ?User;

    /**
     * Find user by referral code
     */
    public function findByReferralCode(string $referralCode): ?User;

    /**
     * Find user by Telegram ID
     */
    public function findByTelegramId(TelegramId $telegramId): ?User;

    /**
     * Find users by status
     */
    public function findByStatus(string $status, int $limit = 100, int $offset = 0): array;

    /**
     * Find users with role
     */
    public function findByRole(string $role, int $limit = 100, int $offset = 0): array;

    /**
     * Find users referred by a specific user
     */
    public function findReferredUsers(UserId $referrerId, int $limit = 100, int $offset = 0): array;

    /**
     * Find active users
     */
    public function findActiveUsers(int $limit = 100, int $offset = 0): array;

    /**
     * Find users created within date range
     */
    public function findByDateRange(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        int $limit = 100,
        int $offset = 0
    ): array;

    /**
     * Save user entity
     */
    public function save(User $user): User;

    /**
     * Delete user
     */
    public function delete(User $user): void;

    /**
     * Check if username exists
     */
    public function usernameExists(string $username, ?UserId $excludeId = null): bool;

    /**
     * Check if email exists
     */
    public function emailExists(Email $email, ?UserId $excludeId = null): bool;

    /**
     * Check if referral code exists
     */
    public function referralCodeExists(string $referralCode, ?UserId $excludeId = null): bool;

    /**
     * Get user statistics
     */
    public function getStatistics(): array;

    /**
     * Count users by status
     */
    public function countByStatus(string $status): int;

    /**
     * Count total referrals for a user
     */
    public function countReferrals(UserId $userId): int;

    /**
     * Find users with balance greater than amount
     */
    public function findUsersWithMinBalance(float $minBalance, string $currency = 'USD'): array;

    /**
     * Search users by name or username
     */
    public function search(string $query, int $limit = 50, int $offset = 0): array;

    /**
     * Get top referrers
     */
    public function getTopReferrers(int $limit = 10): array;

    /**
     * Find users who haven't been active since date
     */
    public function findInactiveUsers(\DateTimeInterface $since): array;

    /**
     * Bulk update user status
     */
    public function bulkUpdateStatus(array $userIds, string $status): int;

    /**
     * Get users paginated
     */
    public function getPaginated(
        int $page = 1,
        int $perPage = 20,
        array $filters = [],
        array $orderBy = ['created_at' => 'DESC']
    ): array;
}