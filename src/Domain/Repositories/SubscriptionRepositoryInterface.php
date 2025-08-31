<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Subscription\Subscription;
use Domain\Entities\Subscription\SubscriptionUsage;
use Domain\ValueObjects\Subscription\SubscriptionId;
use Domain\ValueObjects\Subscription\SubscriptionStatus;
use Domain\ValueObjects\Subscription\SubscriptionType;
use Domain\ValueObjects\User\UserId;
use Domain\ValueObjects\Panel\PanelId;
use Domain\ValueObjects\Common\DateTimeRange;
use Domain\ValueObjects\Common\Money;

/**
 * Subscription repository interface for data persistence operations
 */
interface SubscriptionRepositoryInterface
{
    /**
     * Find subscription by ID
     */
    public function findById(SubscriptionId $id): ?Subscription;
    
    /**
     * Find subscriptions by user
     */
    public function findByUser(UserId $userId, int $limit = 100, int $offset = 0): array;
    
    /**
     * Find subscriptions by panel
     */
    public function findByPanel(PanelId $panelId, int $limit = 100, int $offset = 0): array;
    
    /**
     * Find subscriptions by status
     */
    public function findByStatus(SubscriptionStatus $status, int $limit = 100, int $offset = 0): array;
    
    /**
     * Find subscriptions by type
     */
    public function findByType(SubscriptionType $type, int $limit = 100, int $offset = 0): array;
    
    /**
     * Find active subscription for user and panel
     */
    public function findActiveByUserAndPanel(UserId $userId, PanelId $panelId): ?Subscription;
    
    /**
     * Find active subscriptions for user
     */
    public function findActiveByUser(UserId $userId, int $limit = 100, int $offset = 0): array;
    
    /**
     * Find expired subscriptions
     */
    public function findExpiredSubscriptions(\DateTimeImmutable $asOf, int $limit = 100): array;
    
    /**
     * Find subscriptions expiring soon
     */
    public function findSubscriptionsExpiringSoon(\DateTimeImmutable $before, int $limit = 100): array;
    
    /**
     * Find subscriptions in grace period
     */
    public function findSubscriptionsInGracePeriod(int $limit = 100, int $offset = 0): array;
    
    /**
     * Find subscriptions created in date range
     */
    public function findByCreatedDateRange(DateTimeRange $dateRange, int $limit = 100, int $offset = 0): array;
    
    /**
     * Find subscriptions expiring in date range
     */
    public function findByExpirationDateRange(DateTimeRange $dateRange, int $limit = 100, int $offset = 0): array;
    
    /**
     * Save subscription (create or update)
     */
    public function save(Subscription $subscription): void;
    
    /**
     * Delete subscription
     */
    public function delete(SubscriptionId $id): void;
    
    /**
     * Find subscription usage by subscription
     */
    public function findUsageBySubscription(SubscriptionId $subscriptionId, int $limit = 100, int $offset = 0): array;
    
    /**
     * Find subscription usage by date range
     */
    public function findUsageByDateRange(SubscriptionId $subscriptionId, DateTimeRange $dateRange, int $limit = 100): array;
    
    /**
     * Find subscription usage by type
     */
    public function findUsageByType(SubscriptionId $subscriptionId, string $type, int $limit = 100): array;
    
    /**
     * Save subscription usage
     */
    public function saveUsage(SubscriptionUsage $usage): void;
    
    /**
     * Delete subscription usage
     */
    public function deleteUsage(string $usageId): void;
    
    /**
     * Count total subscriptions
     */
    public function countTotal(): int;
    
    /**
     * Count subscriptions by status
     */
    public function countByStatus(SubscriptionStatus $status): int;
    
    /**
     * Count subscriptions by type
     */
    public function countByType(SubscriptionType $type): int;
    
    /**
     * Count subscriptions by user
     */
    public function countByUser(UserId $userId): int;
    
    /**
     * Count subscriptions by panel
     */
    public function countByPanel(PanelId $panelId): int;
    
    /**
     * Count active subscriptions
     */
    public function countActiveSubscriptions(): int;
    
    /**
     * Count expired subscriptions
     */
    public function countExpiredSubscriptions(\DateTimeImmutable $asOf): int;
    
    /**
     * Get subscription statistics
     */
    public function getSubscriptionStatistics(): array;
    
    /**
     * Get subscription revenue statistics
     */
    public function getRevenueStatistics(DateTimeRange $dateRange): array;
    
    /**
     * Get subscription usage statistics
     */
    public function getUsageStatistics(SubscriptionId $subscriptionId, DateTimeRange $dateRange): array;
    
    /**
     * Get user subscription history
     */
    public function getUserSubscriptionHistory(UserId $userId, int $limit = 100): array;
    
    /**
     * Get panel subscription statistics
     */
    public function getPanelSubscriptionStatistics(PanelId $panelId, DateTimeRange $dateRange): array;
    
    /**
     * Find subscriptions for renewal
     */
    public function findSubscriptionsForRenewal(\DateTimeImmutable $before, int $limit = 100): array;
    
    /**
     * Find subscriptions for cancellation
     */
    public function findSubscriptionsForCancellation(array $criteria, int $limit = 100): array;
    
    /**
     * Search subscriptions by criteria
     */
    public function searchSubscriptions(array $criteria, int $limit = 100, int $offset = 0): array;
    
    /**
     * Find subscriptions with high usage
     */
    public function findSubscriptionsWithHighUsage(float $usageThreshold, int $limit = 100): array;
    
    /**
     * Find subscriptions with low usage
     */
    public function findSubscriptionsWithLowUsage(float $usageThreshold, int $limit = 100): array;
    
    /**
     * Get subscription churn analysis
     */
    public function getChurnAnalysis(DateTimeRange $dateRange): array;
    
    /**
     * Get subscription retention analysis
     */
    public function getRetentionAnalysis(DateTimeRange $dateRange): array;
    
    /**
     * Find subscriptions for bulk operations
     */
    public function findSubscriptionsForBulkOperation(array $subscriptionIds): array;
    
    /**
     * Update subscription last activity
     */
    public function updateLastActivity(SubscriptionId $subscriptionId, \DateTimeImmutable $lastActivity): void;
    
    /**
     * Get most popular subscription types
     */
    public function getMostPopularTypes(DateTimeRange $dateRange, int $limit = 10): array;
    
    /**
     * Get average subscription duration
     */
    public function getAverageSubscriptionDuration(SubscriptionType $type): ?\DateInterval;
    
    /**
     * Find subscriptions by price range
     */
    public function findByPriceRange(Money $minPrice, Money $maxPrice, int $limit = 100, int $offset = 0): array;
    
    /**
     * Get subscription conversion funnel
     */
    public function getConversionFunnel(DateTimeRange $dateRange): array;
}