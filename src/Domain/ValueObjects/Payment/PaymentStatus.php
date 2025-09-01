<?php

namespace BotMirzaPanel\Domain\ValueObjects\Payment;

/**
 * PaymentStatus Value Object
 * 
 * Represents the status of a payment in the system.
 */
class PaymentStatus
{
    private const PENDING = 'pending';
    private const PROCESSING = 'processing';
    private const COMPLETED = 'completed';
    private const FAILED = 'failed';
    private const CANCELLED = 'cancelled';
    private const REFUNDED = 'refunded';
    private const PARTIALLY_REFUNDED = 'partially_refunded';
    private const EXPIRED = 'expired';
    private const DISPUTED = 'disputed';
    private const CHARGEBACK = 'chargeback';

    private const VALID_STATUSES = [
        self::PENDING,
        self::PROCESSING,
        self::COMPLETED,
        self::FAILED,
        self::CANCELLED,
        self::REFUNDED,
        self::PARTIALLY_REFUNDED,
        self::EXPIRED,
        self::DISPUTED,
        self::CHARGEBACK,
    ];

    private string $value;

    private function __construct(string $status): void
    {
        $this->validate($status);
        $this->value = $status;
    }

    public static function pending(): self
    {
        return new self(self::PENDING);
    }

    public static function processing(): self
    {
        return new self(self::PROCESSING);
    }

    public static function completed(): self
    {
        return new self(self::COMPLETED);
    }

    public static function failed(): self
    {
        return new self(self::FAILED);
    }

    public static function cancelled(): self
    {
        return new self(self::CANCELLED);
    }

    public static function refunded(): self
    {
        return new self(self::REFUNDED);
    }

    public static function partiallyRefunded(): self
    {
        return new self(self::PARTIALLY_REFUNDED);
    }

    public static function expired(): self
    {
        return new self(self::EXPIRED);
    }

    public static function disputed(): self
    {
        return new self(self::DISPUTED);
    }

    public static function chargeback(): self
    {
        return new self(self::CHARGEBACK);
    }

    public static function fromString(string $status): self
    {
        return new self(strtolower($status));
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(PaymentStatus $other): bool
    {
        return $this->value === $other->value;
    }

    public function isPending(): bool
    {
        return $this->value === self::PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->value === self::PROCESSING;
    }

    public function isCompleted(): bool
    {
        return $this->value === self::COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->value === self::FAILED;
    }

    public function isCancelled(): bool
    {
        return $this->value === self::CANCELLED;
    }

    public function isRefunded(): bool
    {
        return $this->value === self::REFUNDED;
    }

    public function isPartiallyRefunded(): bool
    {
        return $this->value === self::PARTIALLY_REFUNDED;
    }

    public function isExpired(): bool
    {
        return $this->value === self::EXPIRED;
    }

    public function isDisputed(): bool
    {
        return $this->value === self::DISPUTED;
    }

    public function isChargeback(): bool
    {
        return $this->value === self::CHARGEBACK;
    }

    public function isSuccessful(): bool
    {
        return $this->isCompleted();
    }

    public function isUnsuccessful(): bool
    {
        return in_array($this->value, [self::FAILED, self::CANCELLED, self::EXPIRED]);
    }

    public function isFinal(): bool
    {
        return in_array($this->value, [
            self::COMPLETED,
            self::FAILED,
            self::CANCELLED,
            self::REFUNDED,
            self::EXPIRED,
            self::CHARGEBACK,
        ]);
    }

    public function isRefundable(): bool
    {
        return in_array($this->value, [self::COMPLETED, self::PARTIALLY_REFUNDED]);
    }

    public function canBeProcessed(): bool
    {
        return $this->isPending();
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->value, [self::PENDING, self::PROCESSING]);
    }

    public function canBeCompleted(): bool
    {
        return in_array($this->value, [self::PENDING, self::PROCESSING]);
    }

    public function canBeFailed(): bool
    {
        return in_array($this->value, [self::PENDING, self::PROCESSING]);
    }

    public function canBeRefunded(): bool
    {
        return $this->isRefundable();
    }

    public function canBeDisputed(): bool
    {
        return in_array($this->value, [self::COMPLETED, self::PARTIALLY_REFUNDED]);
    }

    public function requiresAction(): bool
    {
        return in_array($this->value, [self::PENDING, self::PROCESSING, self::DISPUTED]);
    }

    public function getDisplayName(): string
    {
        return match ($this->value) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
            self::REFUNDED => 'Refunded',
            self::PARTIALLY_REFUNDED => 'Partially Refunded',
            self::EXPIRED => 'Expired',
            self::DISPUTED => 'Disputed',
            self::CHARGEBACK => 'Chargeback',
        };
    }

    public function getDescription(): string
    {
        return match ($this->value) {
            self::PENDING => 'Payment is waiting to be processed',
            self::PROCESSING => 'Payment is currently being processed',
            self::COMPLETED => 'Payment has been successfully completed',
            self::FAILED => 'Payment has failed',
            self::CANCELLED => 'Payment has been cancelled',
            self::REFUNDED => 'Payment has been fully refunded',
            self::PARTIALLY_REFUNDED => 'Payment has been partially refunded',
            self::EXPIRED => 'Payment has expired',
            self::DISPUTED => 'Payment is under dispute',
            self::CHARGEBACK => 'Payment has been charged back',
        };
    }

    public function getColor(): string
    {
        return match ($this->value) {
            self::PENDING => 'orange',
            self::PROCESSING => 'blue',
            self::COMPLETED => 'green',
            self::FAILED => 'red',
            self::CANCELLED => 'gray',
            self::REFUNDED => 'purple',
            self::PARTIALLY_REFUNDED => 'purple',
            self::EXPIRED => 'red',
            self::DISPUTED => 'yellow',
            self::CHARGEBACK => 'red',
        };
    }

    public function getIcon(): string
    {
        return match ($this->value) {
            self::PENDING => 'clock',
            self::PROCESSING => 'loader',
            self::COMPLETED => 'check-circle',
            self::FAILED => 'x-circle',
            self::CANCELLED => 'slash',
            self::REFUNDED => 'rotate-ccw',
            self::PARTIALLY_REFUNDED => 'rotate-ccw',
            self::EXPIRED => 'clock',
            self::DISPUTED => 'alert-triangle',
            self::CHARGEBACK => 'arrow-left',
        };
    }

    public static function getAllStatuses(): array
    {
        return self::VALID_STATUSES;
    }

    public static function getSuccessfulStatuses(): array
    {
        return [self::COMPLETED];
    }

    public static function getUnsuccessfulStatuses(): array
    {
        return [self::FAILED, self::CANCELLED, self::EXPIRED];
    }

    public static function getFinalStatuses(): array
    {
        return [
            self::COMPLETED,
            self::FAILED,
            self::CANCELLED,
            self::REFUNDED,
            self::EXPIRED,
            self::CHARGEBACK,
        ];
    }

    public static function getRefundableStatuses(): array
    {
        return [self::COMPLETED, self::PARTIALLY_REFUNDED];
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'display_name' => $this->getDisplayName(),
            'description' => $this->getDescription(),
            'color' => $this->getColor(),
            'icon' => $this->getIcon(),
            'is_successful' => $this->isSuccessful(),
            'is_final' => $this->isFinal(),
            'is_refundable' => $this->isRefundable(),
            'requires_action' => $this->requiresAction(),
        ];
    }

    private function validate(string $status): void
    {
        if (!in_array($status, self::VALID_STATUSES)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid payment status "%s". Valid statuses are: %s',
                    $status,
                    implode(', ', self::VALID_STATUSES)
                )
            );
        }
    }
}