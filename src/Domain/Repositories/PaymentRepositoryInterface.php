<?php
// WARNING: SQL injection vulnerability detected in this file
// Please review and use prepared statements or secure_* functions


declare(strict_types=1);

namespace BotMirzaPanel\Domain\Repositories;

use BotMirzaPanel\Domain\Entities\Payment;
use BotMirzaPanel\Domain\ValueObjects\UserId;
use BotMirzaPanel\Domain\ValueObjects\Money;
use BotMirzaPanel\Shared\Contracts\RepositoryInterface;

/**
 * Payment repository interface
 * Defines payment-specific repository operations
 */
interface PaymentRepositoryInterface extends RepositoryInterface
{
    /**
     * Find payment by ID
     */
    public function findById(int $id): ?Payment;

    /**
     * Find payment by order ID
     */
    public function findByOrderId(string $orderId): ?Payment;

    /**
     * Find payment by gateway transaction ID
     */
    public function findByGatewayTransactionId(string $transactionId): ?Payment;

    /**
     * Find payments by user ID
     */
    public function findByUserId(UserId $userId, int $limit = 100, int $offset = 0): array;

    /**
     * Find payments by status
     */
    public function findByStatus(string $status, int $limit = 100, int $offset = 0): array;

    /**
     * Find payments by gateway
     */
    public function findByGateway(string $gateway, int $limit = 100, int $offset = 0): array;

    /**
     * Find payments within date range
     */
    public function findByDateRange(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        int $limit = 100,
        int $offset = 0
    ): array;

    /**
     * Find payments by amount range
     */
    public function findByAmountRange(
        Money $minAmount,
        Money $maxAmount,
        int $limit = 100,
        int $offset = 0
    ): array;

    /**
     * Find expired pending payments
     */
    public function findExpiredPendingPayments(): array;

    /**
     * Find recent payments for user
     */
    public function findRecentByUserId(UserId $userId, int $limit = 10): array;

    /**
     * Save payment entity
     */
    public function save(Payment $payment): Payment;

    /**
     * Delete payment
     */
    public function delete(Payment $payment): void;

    /**
     * Get payment statistics
     */
    public function getStatistics(): array;

    /**
     * Get revenue statistics by date range
     */
    public function getRevenueStatistics(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        string $groupBy = 'day' // day, week, month
    ): array;

    /**
     * Count payments by status
     */
    public function countByStatus(string $status): int;

    /**
     * Count payments by gateway
     */
    public function countByGateway(string $gateway): int;

    /**
     * Get total amount by status
     */
    public function getTotalAmountByStatus(string $status, string $currency = 'USD'): Money;

    /**
     * Get total amount by user
     */
    public function getTotalAmountByUser(UserId $userId, string $currency = 'USD'): Money;

    /**
     * Get successful payments total for user
     */
    public function getSuccessfulPaymentsTotal(UserId $userId, string $currency = 'USD'): Money;

    /**
     * Find payments needing verification
     */
    public function findPaymentsNeedingVerification(): array;

    /**
     * Get gateway performance statistics
     */
    public function getGatewayPerformance(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array;

    /**
     * Find duplicate payments (same user, amount, gateway within timeframe)
     */
    public function findDuplicatePayments(\DateInterval $timeframe): array;

    /**
     * Get top paying users
     */
    public function getTopPayingUsers(int $limit = 10, string $currency = 'USD'): array;

    /**
     * Search payments
     */
    public function search(
        string $query,
        array $filters = [],
        int $limit = 50,
        int $offset = 0
    ): array;

    /**
     * Get payments paginated
     */
    public function getPaginated(
        int $page = 1,
        int $perPage = 20,
        array $filters = [],
        array $orderBy = ['created_at' => 'DESC']
    ): array;

    /**
     * Get failed payments for retry
     */
    public function getFailedPaymentsForRetry(
        \DateTimeInterface $since,
        int $maxRetries = 3
    ): array;

    /**
     * Update payment status in bulk
     */
    public function bulkUpdateStatus(array $paymentIds, string $status): int;

    /**
     * Get payment conversion rates by gateway
     */
    public function getConversionRates(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array;

    /**
     * Find payments by multiple criteria
     */
    public function findByCriteria(array $criteria, array $orderBy = [], int $limit = 100, int $offset = 0): array;

    /**
     * Get average payment amount by gateway
     */
    public function getAverageAmountByGateway(string $currency = 'USD'): array;

    /**
     * Find payments requiring manual review
     */
    public function findPaymentsRequiringReview(): array;
}