<?php

namespace BotMirzaPanel\Domain\ValueObjects\User;

/**
 * UserStatus Value Object
 * 
 * Represents the status of a user in the system.
 */
class UserStatus
{
    private const PENDING = 'pending';
    private const ACTIVE = 'active';
    private const INACTIVE = 'inactive';
    private const SUSPENDED = 'suspended';
    private const BANNED = 'banned';
    private const DELETED = 'deleted';

    private const VALID_STATUSES = [
        self::PENDING,
        self::ACTIVE,
        self::INACTIVE,
        self::SUSPENDED,
        self::BANNED,
        self::DELETED,
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

    public static function inactive(): self
    {
        return new self(self::INACTIVE);
    }

    public static function suspended(): self
    {
        return new self(self::SUSPENDED);
    }

    public static function banned(): self
    {
        return new self(self::BANNED);
    }

    public static function deleted(): self
    {
        return new self(self::DELETED);
    }

    public static function fromString(string $status): self
    {
        return new self(strtolower($status));
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(UserStatus $other): bool
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

    public function isInactive(): bool
    {
        return $this->value === self::INACTIVE;
    }

    public function isSuspended(): bool
    {
        return $this->value === self::SUSPENDED;
    }

    public function isBanned(): bool
    {
        return $this->value === self::BANNED;
    }

    public function isDeleted(): bool
    {
        return $this->value === self::DELETED;
    }

    public function canLogin(): bool
    {
        return $this->isActive();
    }

    public function canBeActivated(): bool
    {
        return in_array($this->value, [self::PENDING, self::INACTIVE]);
    }

    public function canBeDeactivated(): bool
    {
        return $this->isActive();
    }

    public function canBeSuspended(): bool
    {
        return in_array($this->value, [self::ACTIVE, self::INACTIVE]);
    }

    public function canBeUnsuspended(): bool
    {
        return $this->isSuspended();
    }

    public function canBeBanned(): bool
    {
        return !in_array($this->value, [self::BANNED, self::DELETED]);
    }

    public function canBeUnbanned(): bool
    {
        return $this->isBanned();
    }

    public function canBeDeleted(): bool
    {
        return !$this->isDeleted();
    }

    public function canBeRestored(): bool
    {
        return $this->isDeleted();
    }

    public function isTemporaryStatus(): bool
    {
        return in_array($this->value, [self::PENDING, self::SUSPENDED]);
    }

    public function isPermanentStatus(): bool
    {
        return in_array($this->value, [self::BANNED, self::DELETED]);
    }

    public function getDisplayName(): string
    {
        return match ($this->value) {
            self::PENDING => 'Pending',
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::SUSPENDED => 'Suspended',
            self::BANNED => 'Banned',
            self::DELETED => 'Deleted',
        };
    }

    public function getDescription(): string
    {
        return match ($this->value) {
            self::PENDING => 'User account is pending activation',
            self::ACTIVE => 'User account is active and can access the system',
            self::INACTIVE => 'User account is inactive but can be reactivated',
            self::SUSPENDED => 'User account is temporarily suspended',
            self::BANNED => 'User account is permanently banned',
            self::DELETED => 'User account has been deleted',
        };
    }

    public function getColor(): string
    {
        return match ($this->value) {
            self::PENDING => 'orange',
            self::ACTIVE => 'green',
            self::INACTIVE => 'gray',
            self::SUSPENDED => 'yellow',
            self::BANNED => 'red',
            self::DELETED => 'black',
        };
    }

    public function getIcon(): string
    {
        return match ($this->value) {
            self::PENDING => 'clock',
            self::ACTIVE => 'check-circle',
            self::INACTIVE => 'pause-circle',
            self::SUSPENDED => 'alert-triangle',
            self::BANNED => 'x-circle',
            self::DELETED => 'trash',
        };
    }

    public static function getAllStatuses(): array
    {
        return self::VALID_STATUSES;
    }

    public static function getActiveStatuses(): array
    {
        return [self::ACTIVE];
    }

    public static function getInactiveStatuses(): array
    {
        return [self::PENDING, self::INACTIVE, self::SUSPENDED, self::BANNED, self::DELETED];
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
            'can_login' => $this->canLogin(),
            'is_temporary' => $this->isTemporaryStatus(),
            'is_permanent' => $this->isPermanentStatus(),
        ];
    }

    private function validate(string $status): void
    {
        if (!in_array($status, self::VALID_STATUSES)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid user status "%s". Valid statuses are: %s',
                    $status,
                    implode(', ', self::VALID_STATUSES)
                )
            );
        }
    }
}