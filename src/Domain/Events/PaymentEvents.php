<?php

declare(strict_types=1);

namespace App\Domain\Events;

use App\Domain\ValueObjects\Money;

/**
 * Payment created event
 */
class PaymentCreated extends AbstractDomainEvent
{
    public function getEventName(): string
    {
        return 'payment.created';
    }

    public static function create(
        string $paymentId,
        string $userId,
        string $orderId,
        Money $amount,
        string $gateway,
        ?string $description = null
    ): self {
        return new self($paymentId, [
            'user_id' => $userId,
            'order_id' => $orderId,
            'amount' => $amount->toArray(),
            'gateway' => $gateway,
            'description' => $description,
        ]);
    }
}

/**
 * Payment completed event
 */
class PaymentCompleted extends AbstractDomainEvent
{
    public function getEventName(): string
    {
        return 'payment.completed';
    }

    public static function create(
        string $paymentId,
        string $userId,
        Money $amount,
        string $transactionId,
        string $gateway,
        ?array $gatewayResponse = null
    ): self {
        return new self($paymentId, [
            'user_id' => $userId,
            'amount' => $amount->toArray(),
            'transaction_id' => $transactionId,
            'gateway' => $gateway,
            'gateway_response' => $gatewayResponse,
        ]);
    }
}

/**
 * Payment failed event
 */
class PaymentFailed extends AbstractDomainEvent
{
    public function getEventName(): string
    {
        return 'payment.failed';
    }

    public static function create(
        string $paymentId,
        string $userId,
        Money $amount,
        string $gateway,
        string $reason,
        ?array $gatewayResponse = null
    ): self {
        return new self($paymentId, [
            'user_id' => $userId,
            'amount' => $amount->toArray(),
            'gateway' => $gateway,
            'reason' => $reason,
            'gateway_response' => $gatewayResponse,
        ]);
    }
}

/**
 * Payment refunded event
 */
class PaymentRefunded extends AbstractDomainEvent
{
    public function getEventName(): string
    {
        return 'payment.refunded';
    }

    public static function create(
        string $paymentId,
        string $userId,
        Money $originalAmount,
        Money $refundAmount,
        string $reason,
        ?string $refundedBy = null,
        ?array $gatewayResponse = null
    ): self {
        return new self($paymentId, [
            'user_id' => $userId,
            'original_amount' => $originalAmount->toArray(),
            'refund_amount' => $refundAmount->toArray(),
            'reason' => $reason,
            'refunded_by' => $refundedBy,
            'gateway_response' => $gatewayResponse,
        ]);
    }
}

/**
 * Payment cancelled event
 */
class PaymentCancelled extends AbstractDomainEvent
{
    public function getEventName(): string
    {
        return 'payment.cancelled';
    }

    public static function create(
        string $paymentId,
        string $userId,
        Money $amount,
        string $reason,
        ?string $cancelledBy = null
    ): self {
        return new self($paymentId, [
            'user_id' => $userId,
            'amount' => $amount->toArray(),
            'reason' => $reason,
            'cancelled_by' => $cancelledBy,
        ]);
    }
}

/**
 * Payment expired event
 */
class PaymentExpired extends AbstractDomainEvent
{
    public function getEventName(): string
    {
        return 'payment.expired';
    }

    public static function create(
        string $paymentId,
        string $userId,
        Money $amount,
        string $gateway,
        \DateTimeInterface $expiresAt
    ): self {
        return new self($paymentId, [
            'user_id' => $userId,
            'amount' => $amount->toArray(),
            'gateway' => $gateway,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ]);
    }
}

/**
 * Payment gateway changed event
 */
class PaymentGatewayChanged extends AbstractDomainEvent
{
    public function getEventName(): string
    {
        return 'payment.gateway_changed';
    }

    public static function create(
        string $paymentId,
        string $userId,
        string $oldGateway,
        string $newGateway,
        ?string $reason = null
    ): self {
        return new self($paymentId, [
            'user_id' => $userId,
            'old_gateway' => $oldGateway,
            'new_gateway' => $newGateway,
            'reason' => $reason,
        ]);
    }
}

/**
 * Payment verification started event
 */
class PaymentVerificationStarted extends AbstractDomainEvent
{
    public function getEventName(): string
    {
        return 'payment.verification_started';
    }

    public static function create(
        string $paymentId,
        string $userId,
        string $gateway,
        ?string $transactionId = null
    ): self {
        return new self($paymentId, [
            'user_id' => $userId,
            'gateway' => $gateway,
            'transaction_id' => $transactionId,
        ]);
    }
}

/**
 * Payment verification failed event
 */
class PaymentVerificationFailed extends AbstractDomainEvent
{
    public function getEventName(): string
    {
        return 'payment.verification_failed';
    }

    public static function create(
        string $paymentId,
        string $userId,
        string $gateway,
        string $reason,
        ?array $gatewayResponse = null
    ): self {
        return new self($paymentId, [
            'user_id' => $userId,
            'gateway' => $gateway,
            'reason' => $reason,
            'gateway_response' => $gatewayResponse,
        ]);
    }
}

/**
 * Suspicious payment detected event
 */
class SuspiciousPaymentDetected extends AbstractDomainEvent
{
    public function getEventName(): string
    {
        return 'payment.suspicious_detected';
    }

    public static function create(
        string $paymentId,
        string $userId,
        Money $amount,
        array $suspiciousPatterns,
        float $riskScore
    ): self {
        return new self($paymentId, [
            'user_id' => $userId,
            'amount' => $amount->toArray(),
            'suspicious_patterns' => $suspiciousPatterns,
            'risk_score' => $riskScore,
        ]);
    }
}

/**
 * Payment limit exceeded event
 */
class PaymentLimitExceeded extends AbstractDomainEvent
{
    public function getEventName(): string
    {
        return 'payment.limit_exceeded';
    }

    public static function create(
        string $paymentId,
        string $userId,
        Money $attemptedAmount,
        Money $dailyLimit,
        Money $currentDailyTotal
    ): self {
        return new self($paymentId, [
            'user_id' => $userId,
            'attempted_amount' => $attemptedAmount->toArray(),
            'daily_limit' => $dailyLimit->toArray(),
            'current_daily_total' => $currentDailyTotal->toArray(),
        ]);
    }
}