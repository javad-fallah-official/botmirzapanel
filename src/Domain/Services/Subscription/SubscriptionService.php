<?php

declare(strict_types=1);

namespace BotMirzaPanel\Domain\Services\Subscription;

use BotMirzaPanel\Domain\Entities\Subscription\Subscription;
use BotMirzaPanel\Domain\Entities\Subscription\SubscriptionUsage;
use BotMirzaPanel\Domain\ValueObjects\Subscription\SubscriptionId;
use BotMirzaPanel\Domain\ValueObjects\Subscription\SubscriptionStatus;
use BotMirzaPanel\Domain\ValueObjects\Subscription\SubscriptionType;
use BotMirzaPanel\Domain\ValueObjects\User\UserId;
use BotMirzaPanel\Domain\ValueObjects\Panel\PanelId;
use BotMirzaPanel\Domain\ValueObjects\Common\Money;
use BotMirzaPanel\Domain\ValueObjects\Common\DataLimit;
use BotMirzaPanel\Domain\ValueObjects\Common\DateTimeRange;
use BotMirzaPanel\Domain\Exceptions\ValidationException;
use DateTimeImmutable;
use DateInterval;

/**
 * Subscription domain service for handling subscription-related business logic
 */
class SubscriptionService
{
    /**
     * Create a new subscription with validation
     */
    public function createSubscription(
        UserId $userId,
        PanelId $panelId,
        SubscriptionType $type,
        Money $price,
        DateTimeImmutable $startsAt,
        DateTimeImmutable $expiresAt,
        ?DataLimit $dataLimit = null,
        ?int $deviceLimit = null,
        ?array $features = null
    ): Subscription {
        $this->validateSubscriptionDates($startsAt, $expiresAt);
        $this->validateSubscriptionPrice($price, $type);
        $this->validateSubscriptionLimits($dataLimit, $deviceLimit, $type);
        
        $subscriptionId = SubscriptionId::generate();
        $status = SubscriptionStatus::pending();
        
        return Subscription::create(
            $subscriptionId,
            $userId,
            $panelId,
            $type,
            $status,
            $price,
            $startsAt,
            $expiresAt,
            $dataLimit,
            $deviceLimit,
            $features ?? []
        );
    }
    
    /**
     * Activate a subscription
     */
    public function activateSubscription(Subscription $subscription): Subscription
    {
        if (!$subscription->canBeActivated()) {
            throw new ValidationException(
                'Subscription cannot be activated in current status: ' . $subscription->getStatus()->getValue()
            );
        }
        
        $subscription->activate();
        
        return $subscription;
    }
    
    /**
     * Suspend a subscription
     */
    public function suspendSubscription(Subscription $subscription, string $reason): Subscription
    {
        if (!$subscription->canBeSuspended()) {
            throw new ValidationException(
                'Subscription cannot be suspended in current status: ' . $subscription->getStatus()->getValue()
            );
        }
        
        $subscription->suspend($reason);
        
        return $subscription;
    }
    
    /**
     * Cancel a subscription
     */
    public function cancelSubscription(Subscription $subscription, string $reason): Subscription
    {
        if (!$subscription->canBeCancelled()) {
            throw new ValidationException(
                'Subscription cannot be cancelled in current status: ' . $subscription->getStatus()->getValue()
            );
        }
        
        $subscription->cancel($reason);
        
        return $subscription;
    }
    
    /**
     * Renew a subscription
     */
    public function renewSubscription(
        Subscription $subscription,
        DateTimeImmutable $newExpiresAt,
        ?Money $renewalPrice = null
    ): Subscription {
        if (!$subscription->canBeRenewed()) {
            throw new ValidationException(
                'Subscription cannot be renewed in current status: ' . $subscription->getStatus()->getValue()
            );
        }
        
        $this->validateRenewalDate($subscription, $newExpiresAt);
        
        $subscription->renew($newExpiresAt, $renewalPrice);
        
        return $subscription;
    }
    
    /**
     * Expire a subscription
     */
    public function expireSubscription(Subscription $subscription): Subscription
    {
        if (!$subscription->getStatus()->isActive() && !$subscription->getStatus()->isGracePeriod()) {
            throw new ValidationException(
                'Only active or grace period subscriptions can be expired'
            );
        }
        
        $subscription->expire();
        
        return $subscription;
    }
    
    /**
     * Put subscription in grace period
     */
    public function putInGracePeriod(Subscription $subscription, DateTimeImmutable $gracePeriodEnds): Subscription
    {
        if (!$subscription->getStatus()->isActive()) {
            throw new ValidationException(
                'Only active subscriptions can be put in grace period'
            );
        }
        
        $subscription->putInGracePeriod($gracePeriodEnds);
        
        return $subscription;
    }
    
    /**
     * Record data usage
     */
    public function recordDataUsage(
        Subscription $subscription,
        DataLimit $amount,
        string $source,
        ?string $sourceId = null,
        ?array $metadata = null
    ): SubscriptionUsage {
        if (!$subscription->isUsable()) {
            throw new ValidationException(
                'Cannot record usage for non-usable subscription'
            );
        }
        
        $usage = SubscriptionUsage::forDataUsage(
            $subscription->getId(),
            'bytes',
            $amount->getBytes(),
            $source,
            $sourceId,
            $metadata
        );
        
        $subscription->addUsage($usage);
        
        // Check if data limit exceeded
        if ($this->hasExceededDataLimit($subscription)) {
            $this->suspendSubscription($subscription, 'Data limit exceeded');
        }
        
        return $usage;
    }
    
    /**
     * Record time usage
     */
    public function recordTimeUsage(
        Subscription $subscription,
        int $minutes,
        string $source,
        ?string $sourceId = null,
        ?array $metadata = null
    ): SubscriptionUsage {
        if (!$subscription->isUsable()) {
            throw new ValidationException(
                'Cannot record usage for non-usable subscription'
            );
        }
        
        $usage = SubscriptionUsage::forTimeUsage(
            $subscription->getId(),
            'minutes',
            $minutes,
            $source,
            $sourceId,
            $metadata
        );
        
        $subscription->addUsage($usage);
        
        return $usage;
    }
    
    /**
     * Record feature usage
     */
    public function recordFeatureUsage(
        Subscription $subscription,
        string $feature,
        int $count,
        string $source,
        ?string $sourceId = null,
        ?array $metadata = null
    ): SubscriptionUsage {
        if (!$subscription->isUsable()) {
            throw new ValidationException(
                'Cannot record usage for non-usable subscription'
            );
        }
        
        $usage = SubscriptionUsage::forFeatureUsage(
            $subscription->getId(),
            $feature,
            $count,
            $source,
            $sourceId,
            $metadata
        );
        
        $subscription->addUsage($usage);
        
        return $usage;
    }
    
    /**
     * Check if subscription is expired
     */
    public function isSubscriptionExpired(Subscription $subscription): bool
    {
        return new DateTimeImmutable() > $subscription->getExpiresAt();
    }
    
    /**
     * Check if subscription is expiring soon
     */
    public function isSubscriptionExpiringSoon(Subscription $subscription, int $days = 7): bool
    {
        $warningDate = new DateTimeImmutable("+{$days} days");
        return $subscription->getExpiresAt() <= $warningDate;
    }
    
    /**
     * Check if subscription has exceeded data limit
     */
    public function hasExceededDataLimit(Subscription $subscription): bool
    {
        $dataLimit = $subscription->getDataLimit();
        
        if ($dataLimit === null || $dataLimit->isUnlimited()) {
            return false;
        }
        
        $usedData = $subscription->getUsedData();
        
        return $usedData->isGreaterThanOrEqual($dataLimit);
    }
    
    /**
     * Get subscription usage summary
     */
    public function getUsageSummary(Subscription $subscription, DateTimeRange $period): array
    {
        // This would typically use a repository to fetch usage data
        return [
            'data_usage' => DataLimit::zero(),
            'time_usage' => 0,
            'feature_usage' => [],
            'total_sessions' => 0,
            'average_session_duration' => 0,
            'peak_usage_day' => null,
        ];
    }
    
    /**
     * Calculate subscription renewal price
     */
    public function calculateRenewalPrice(
        Subscription $subscription,
        ?SubscriptionType $newType = null
    ): Money {
        $type = $newType ?? $subscription->getType();
        
        // Base price from subscription type
        $basePrice = $this->getTypeBasePrice($type);
        
        // Apply discounts for loyal customers
        $discount = $this->calculateLoyaltyDiscount($subscription);
        
        return $basePrice->subtract($discount);
    }
    
    /**
     * Get remaining subscription time
     */
    public function getRemainingTime(Subscription $subscription): DateInterval
    {
        $now = new DateTimeImmutable();
        $expiresAt = $subscription->getExpiresAt();
        
        if ($now >= $expiresAt) {
            return new DateInterval('PT0S'); // Zero interval
        }
        
        return $now->diff($expiresAt);
    }
    
    /**
     * Get remaining data allowance
     */
    public function getRemainingData(Subscription $subscription): ?DataLimit
    {
        $dataLimit = $subscription->getDataLimit();
        
        if ($dataLimit === null || $dataLimit->isUnlimited()) {
            return $dataLimit;
        }
        
        $usedData = $subscription->getUsedData();
        
        if ($usedData->isGreaterThanOrEqual($dataLimit)) {
            return DataLimit::zero();
        }
        
        return $dataLimit->subtract($usedData);
    }
    
    /**
     * Validate subscription dates
     */
    private function validateSubscriptionDates(DateTimeImmutable $startsAt, DateTimeImmutable $expiresAt): void
    {
        if ($startsAt >= $expiresAt) {
            throw new SubscriptionValidationException(
                'Subscription start date must be before expiration date'
            );
        }
        
        $minDuration = $startsAt->add(new DateInterval('P1D')); // Minimum 1 day
        if ($expiresAt < $minDuration) {
            throw new SubscriptionValidationException(
                'Subscription duration must be at least 1 day'
            );
        }
        
        $maxDuration = $startsAt->add(new DateInterval('P5Y')); // Maximum 5 years
        if ($expiresAt > $maxDuration) {
            throw new SubscriptionValidationException(
                'Subscription duration cannot exceed 5 years'
            );
        }
    }
    
    /**
     * Validate subscription price
     */
    private function validateSubscriptionPrice(Money $price, SubscriptionType $type): void
    {
        if ($type->isFree() && !$price->isZero()) {
            throw new SubscriptionValidationException(
                'Free subscription type must have zero price'
            );
        }
        
        if ($type->isPaid() && $price->isZero()) {
            throw new SubscriptionValidationException(
                'Paid subscription type must have non-zero price'
            );
        }
        
        if ($price->isNegative()) {
            throw new SubscriptionValidationException(
                'Subscription price cannot be negative'
            );
        }
    }
    
    /**
     * Validate subscription limits
     */
    private function validateSubscriptionLimits(
        ?DataLimit $dataLimit,
        ?int $deviceLimit,
        SubscriptionType $type
    ): void {
        if ($deviceLimit !== null && $deviceLimit < 1) {
            throw new SubscriptionValidationException(
                'Device limit must be at least 1'
            );
        }
        
        if ($deviceLimit !== null && $deviceLimit > 100) {
            throw new SubscriptionValidationException(
                'Device limit cannot exceed 100'
            );
        }
        
        // Validate limits based on subscription type
        $maxDevices = $type->getMaxDevices();
        if ($maxDevices !== null && $deviceLimit !== null && $deviceLimit > $maxDevices) {
            throw new SubscriptionValidationException(
                "Device limit cannot exceed {$maxDevices} for {$type->getValue()} subscription"
            );
        }
    }
    
    /**
     * Validate renewal date
     */
    private function validateRenewalDate(Subscription $subscription, DateTimeImmutable $newExpiresAt): void
    {
        $currentExpiresAt = $subscription->getExpiresAt();
        
        if ($newExpiresAt <= $currentExpiresAt) {
            throw new SubscriptionValidationException(
                'New expiration date must be after current expiration date'
            );
        }
        
        $maxExtension = $currentExpiresAt->add(new DateInterval('P5Y'));
        if ($newExpiresAt > $maxExtension) {
            throw new SubscriptionValidationException(
                'Renewal cannot extend subscription more than 5 years from current expiration'
            );
        }
    }
    
    /**
     * Get base price for subscription type
     */
    private function getTypeBasePrice(SubscriptionType $type): Money
    {
        $prices = [
            'basic' => 10.0,
            'premium' => 25.0,
            'enterprise' => 50.0,
            'trial' => 0.0,
            'custom' => 15.0,
            'unlimited' => 100.0,
            'limited' => 5.0,
            'family' => 40.0,
            'student' => 8.0,
            'business' => 75.0,
        ];
        
        $price = $prices[$type->getValue()] ?? 10.0;
        
        return Money::fromFloat($price, 'USD');
    }
    
    /**
     * Calculate loyalty discount
     */
    private function calculateLoyaltyDiscount(Subscription $subscription): Money
    {
        // This would typically check user's subscription history
        // For now, return zero discount
        return Money::zero($subscription->getPrice()->getCurrency());
    }
}