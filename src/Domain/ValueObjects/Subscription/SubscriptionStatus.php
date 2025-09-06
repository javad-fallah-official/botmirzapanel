<?php

namespace BotMirzaPanel\Domain\ValueObjects\Subscription;

/**
 * SubscriptionStatus Value Object
 * 
 * Represents the status of a subscription in the system.
 */
class SubscriptionStatus
{
    private const PENDING = 'pending';
    private const ACTIVE = 'active';
    private const SUSPENDED = 'suspended';
    private const CANCELLED = 'cancelled';
    private const EXPIRED = 'expired';
    private const TRIAL = 'trial';
    private const GRACE_PERIOD = 'grace_period';
    private const PAUSED = 'paused';

    private const VALID_STATUSES = [
        self::PENDING,
        self::ACTIVE,
        self::SUSPENDED,
        self::CANCELLED,
        self::EXPIRED,
        self::TRIAL,
        self::GRACE_PERIOD,
        self::PAUSED,
    ];

    private string $value;

    private function __construct(string $status)
    {
        $this->validate($status);
        $this->value = $status;
    }

    public static function pending(): self
    {
        return new self(self::PENDING);
    }

    public static function active(): self
    {
        return new self(self::ACTIVE);
    }

    public static function suspended(): self
    {
        return new self(self::SUSPENDED);
    }

    public static function cancelled(): self
    {
        return new self(self::CANCELLED);
    }

    public static function expired(): self
    {
        return new self(self::EXPIRED);
    }

    public static function trial(): self
    {
        return new self(self::TRIAL);
    }

    public static function gracePeriod(): self
    {
        return new self(self::GRACE_PERIOD);
    }

    public static function paused(): self
    {
        return new self(self::PAUSED);
    }

    public static function fromString(string $status): self
    {
        return new self(strtolower($status));
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(SubscriptionStatus $other): bool
    {
        return $this->value === $other->value;
    }

    public function isPending(): bool
    {
        return $this->value === self::PENDING;
    }

    public function isActive(): bool
    {
        return $this->value === self::ACTIVE;
    }

    public function isSuspended(): bool
    {
        return $this->value === self::SUSPENDED;
    }

    public function isCancelled(): bool
    {
        return $this->value === self::CANCELLED;
    }

    public function isExpired(): bool
    {
        return $this->value === self::EXPIRED;
    }

    public function isTrial(): bool
    {
        return $this->value === self::TRIAL;
    }

    public function isGracePeriod(): bool
    {
        return $this->value === self::GRACE_PERIOD;
    }

    public function isPaused(): bool
    {
        return $this->value === self::PAUSED;
    }

    public function isUsable(): bool
    {
        return in_array($this->value, [
            self::ACTIVE,
            self::TRIAL,
            self::GRACE_PERIOD,
        ]);
    }

    public function canBeActivated(): bool
    {
        return in_array($this->value, [
            self::PENDING,
            self::SUSPENDED,
            self::PAUSED,
        ]);
    }

    public function canBeSuspended(): bool
    {
        return in_array($this->value, [
            self::ACTIVE,
            self::TRIAL,
            self::GRACE_PERIOD,
        ]);
    }

    public function canBeCancelled(): bool
    {
        return !in_array($this->value, [
            self::CANCELLED,
            self::EXPIRED,
        ]);
    }

    public function canBeRenewed(): bool
    {
        return in_array($this->value, [
            self::ACTIVE,
            self::EXPIRED,
            self::GRACE_PERIOD,
        ]);
    }

    public function canBePaused(): bool
    {
        return in_array($this->value, [
            self::ACTIVE,
            self::TRIAL,
        ]);
    }

    public function canBeResumed(): bool
    {
        return in_array($this->value, [
            self::PAUSED,
            self::SUSPENDED,
        ]);
    }

    public function requiresPayment(): bool
    {
        return in_array($this->value, [
            self::PENDING,
            self::EXPIRED,
        ]);
    }

    public function allowsUsage(): bool
    {
        return in_array($this->value, [
            self::ACTIVE,
            self::TRIAL,
            self::GRACE_PERIOD,
        ]);
    }

    public function isTerminal(): bool
    {
        return in_array($this->value, [
            self::CANCELLED,
            self::EXPIRED,
        ]);
    }

    public function getDisplayName(): string
    {
        return match ($this->value) {
            self::PENDING => 'Pending',
            self::ACTIVE => 'Active',
            self::SUSPENDED => 'Suspended',
            self::CANCELLED => 'Cancelled',
            self::EXPIRED => 'Expired',
            self::TRIAL => 'Trial',
            self::GRACE_PERIOD => 'Grace Period',
            self::PAUSED => 'Paused',
        };
    }

    public function getDescription(): string
    {
        return match ($this->value) {
            self::PENDING => 'Subscription is pending activation',
            self::ACTIVE => 'Subscription is active and usable',
            self::SUSPENDED => 'Subscription is temporarily suspended',
            self::CANCELLED => 'Subscription has been cancelled',
            self::EXPIRED => 'Subscription has expired',
            self::TRIAL => 'Subscription is in trial period',
            self::GRACE_PERIOD => 'Subscription is in grace period after expiry',
            self::PAUSED => 'Subscription is temporarily paused',
        };
    }

    public function getColor(): string
    {
        return match ($this->value) {
            self::PENDING => 'yellow',
            self::ACTIVE => 'green',
            self::SUSPENDED => 'orange',
            self::CANCELLED => 'red',
            self::EXPIRED => 'red',
            self::TRIAL => 'blue',
            self::GRACE_PERIOD => 'purple',
            self::PAUSED => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this->value) {
            self::PENDING => 'clock',
            self::ACTIVE => 'check-circle',
            self::SUSPENDED => 'pause-circle',
            self::CANCELLED => 'x-circle',
            self::EXPIRED => 'alert-circle',
            self::TRIAL => 'gift',
            self::GRACE_PERIOD => 'heart',
            self::PAUSED => 'pause',
        };
    }

    public function getPriority(): int
    {
        return match ($this->value) {
            self::ACTIVE => 1,
            self::TRIAL => 2,
            self::GRACE_PERIOD => 3,
            self::PAUSED => 4,
            self::PENDING => 5,
            self::SUSPENDED => 6,
            self::EXPIRED => 7,
            self::CANCELLED => 8,
        };
    }

    public static function getAllStatuses(): array
    {
        return self::VALID_STATUSES;
    }

    public static function getActiveStatuses(): array
    {
        return [self::ACTIVE, self::TRIAL, self::GRACE_PERIOD];
    }

    public static function getInactiveStatuses(): array
    {
        return [self::PENDING, self::SUSPENDED, self::CANCELLED, self::EXPIRED, self::PAUSED];
    }

    public static function getTerminalStatuses(): array
    {
        return [self::CANCELLED, self::EXPIRED];
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
            'priority' => $this->getPriority(),
            'is_usable' => $this->isUsable(),
            'allows_usage' => $this->allowsUsage(),
            'requires_payment' => $this->requiresPayment(),
            'is_terminal' => $this->isTerminal(),
            'can_be_activated' => $this->canBeActivated(),
            'can_be_suspended' => $this->canBeSuspended(),
            'can_be_cancelled' => $this->canBeCancelled(),
            'can_be_renewed' => $this->canBeRenewed(),
            'can_be_paused' => $this->canBePaused(),
            'can_be_resumed' => $this->canBeResumed(),
        ];
    }

    private function validate(string $status): void
    {
        if (!in_array($status, self::VALID_STATUSES)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid subscription status "%s". Valid statuses are: %s',
                    $status,
                    implode(', ', self::VALID_STATUSES)
                )
            );
        }
    }
}