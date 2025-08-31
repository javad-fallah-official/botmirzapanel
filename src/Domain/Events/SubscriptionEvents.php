<?php

declare(strict_types=1);

namespace BotMirzaPanel\Domain\Events;

use BotMirzaPanel\Domain\ValueObjects\Money;

/**
 * Subscription created event
 */
class SubscriptionCreated extends AbstractDomainEvent
{
    public function getEventName(): string
    {
        return 'subscription.created';
    }

    public function getAggregateType(): string
    {
        return 'subscription';
    }

    public static function create(
        string $subscriptionId,
        string $userId,
        string $planId,
        Money $amount,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        string $status = 'active'
    ): self {
        $event = new self();
        $event->aggregateId = $subscriptionId;
        $event->payload = [
            'user_id' => $userId,
            'plan_id' => $planId,
            'amount' => $amount->toArray(),
            'start_date' => $startDate->format('Y-m-d H:i:s'),
            'end_date' => $endDate->format('Y-m-d H:i:s'),
            'status' => $status,
        ];
        return $event;
    }
}

/**
 * Subscription renewed event
 */
class SubscriptionRenewed extends AbstractDomainEvent
{
    public function getEventName(): string
    {
        return 'subscription.renewed';
    }

    public function getAggregateType(): string
    {
        return 'subscription';
    }

    public static function create(
        string $subscriptionId,
        string $userId,
        \DateTimeImmutable $newEndDate,
        Money $renewalAmount,
        string $paymentId
    ): self {
        $event = new self();
        $event->aggregateId = $subscriptionId;
        $event->payload = [
            'user_id' => $userId,
            'new_end_date' => $newEndDate->format('Y-m-d H:i:s'),
            'renewal_amount' => $renewalAmount->toArray(),
            'payment_id' => $paymentId,
        ];
        return $event;
    }
}

/**
 * Subscription cancelled event
 */
class SubscriptionCancelled extends AbstractDomainEvent
{
    public function getEventName(): string
    {
        return 'subscription.cancelled';
    }

    public function getAggregateType(): string
    {
        return 'subscription';
    }

    public static function create(
        string $subscriptionId,
        string $userId,
        string $reason,
        \DateTimeImmutable $cancelledAt,
        bool $refundIssued = false
    ): self {
        $event = new self();
        $event->aggregateId = $subscriptionId;
        $event->payload = [
            'user_id' => $userId,
            'reason' => $reason,
            'cancelled_at' => $cancelledAt->format('Y-m-d H:i:s'),
            'refund_issued' => $refundIssued,
        ];
        return $event;
    }
}

/**
 * Subscription expired event
 */
class SubscriptionExpired extends AbstractDomainEvent
{
    public function getEventName(): string
    {
        return 'subscription.expired';
    }

    public function getAggregateType(): string
    {
        return 'subscription';
    }

    public static function create(
        string $subscriptionId,
        string $userId,
        \DateTimeImmutable $expiredAt
    ): self {
        $event = new self();
        $event->aggregateId = $subscriptionId;
        $event->payload = [
            'user_id' => $userId,
            'expired_at' => $expiredAt->format('Y-m-d H:i:s'),
        ];
        return $event;
    }
}

/**
 * Subscription suspended event
 */
class SubscriptionSuspended extends AbstractDomainEvent
{
    public function getEventName(): string
    {
        return 'subscription.suspended';
    }

    public function getAggregateType(): string
    {
        return 'subscription';
    }

    public static function create(
        string $subscriptionId,
        string $userId,
        string $reason,
        \DateTimeImmutable $suspendedAt,
        ?\DateTimeImmutable $suspendedUntil = null
    ): self {
        $event = new self();
        $event->aggregateId = $subscriptionId;
        $event->payload = [
            'user_id' => $userId,
            'reason' => $reason,
            'suspended_at' => $suspendedAt->format('Y-m-d H:i:s'),
            'suspended_until' => $suspendedUntil?->format('Y-m-d H:i:s'),
        ];
        return $event;
    }
}

/**
 * Subscription reactivated event
 */
class SubscriptionReactivated extends AbstractDomainEvent
{
    public function getEventName(): string
    {
        return 'subscription.reactivated';
    }

    public function getAggregateType(): string
    {
        return 'subscription';
    }

    public static function create(
        string $subscriptionId,
        string $userId,
        \DateTimeImmutable $reactivatedAt,
        ?string $paymentId = null
    ): self {
        $event = new self();
        $event->aggregateId = $subscriptionId;
        $event->payload = [
            'user_id' => $userId,
            'reactivated_at' => $reactivatedAt->format('Y-m-d H:i:s'),
            'payment_id' => $paymentId,
        ];
        return $event;
    }
}