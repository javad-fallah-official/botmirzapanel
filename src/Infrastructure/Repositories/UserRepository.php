<?php

declare(strict_types=1);

namespace BotMirzaPanel\Infrastructure\Repositories;

use BotMirzaPanel\Database\BaseRepository;
use BotMirzaPanel\Database\DatabaseManager;
use BotMirzaPanel\Domain\Repositories\UserRepositoryInterface;
use BotMirzaPanel\Domain\ValueObjects\UserId;
use BotMirzaPanel\Domain\ValueObjects\Email;
use BotMirzaPanel\Domain\Entities\User;

/**
 * Infrastructure User Repository
 * Minimal implementation to satisfy DI; methods return stub values for now.
 */
class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(DatabaseManager $db)
    {
        parent::__construct($db, 'users');
        $this->fillable = [
            'id', 'username', 'first_name', 'last_name', 'email', 'phone_number',
            'language_code', 'is_bot', 'balance', 'status', 'state', 'is_admin',
            'is_banned', 'referred_by', 'referral_code', 'created_at', 'updated_at'
        ];
    }

    // Finder methods
    public function findById(UserId $id): ?User
    {
        // TODO: Map database row to User entity once entities are available
        return null;
    }

    public function findByUsername(string $username): ?User
    {
        return null;
    }

    public function findByEmail(Email $email): ?User
    {
        return null;
    }

    public function findByReferralCode(string $referralCode): ?User
    {
        return null;
    }

    public function findByStatus(string $status, int $limit = 100, int $offset = 0): array
    {
        return [];
    }

    public function findByRole(string $role, int $limit = 100, int $offset = 0): array
    {
        return [];
    }

    public function findReferredUsers(UserId $referrerId, int $limit = 100, int $offset = 0): array
    {
        return [];
    }

    public function findActiveUsers(int $limit = 100, int $offset = 0): array
    {
        return [];
    }

    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate, int $limit = 100, int $offset = 0): array
    {
        return [];
    }

    // Persistence methods
    public function save(User $user): User
    {
        // TODO: Implement persistence once mapping is defined
        return $user;
    }

    public function delete(User $user): void
    {
        // TODO: Implement delete logic when entity mapping is ready
    }

    // Existence checks
    public function usernameExists(string $username, ?UserId $excludeId = null): bool
    {
        return false;
    }

    public function emailExists(Email $email, ?UserId $excludeId = null): bool
    {
        return false;
    }

    public function referralCodeExists(string $referralCode, ?UserId $excludeId = null): bool
    {
        return false;
    }

    // Statistics and utilities
    public function getStatistics(): array
    {
        return [];
    }

    public function countByStatus(string $status): int
    {
        return 0;
    }

    public function countReferrals(UserId $userId): int
    {
        return 0;
    }

    public function findUsersWithMinBalance(float $minBalance, string $currency = 'USD'): array
    {
        return [];
    }

    public function search(string $query, int $limit = 50, int $offset = 0): array
    {
        return [];
    }

    public function getTopReferrers(int $limit = 10): array
    {
        return [];
    }

    public function findInactiveUsers(\DateTimeInterface $since): array
    {
        return [];
    }

    public function bulkUpdateStatus(array $userIds, string $status): int
    {
        return 0;
    }

    public function getPaginated(int $page = 1, int $perPage = 20, array $filters = [], array $orderBy = ['created_at' => 'DESC']): array
    {
        return [
            'items' => [],
            'total' => 0,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => 0,
        ];
    }
}