<?php

declare(strict_types=1);

namespace Domain\ValueObjects\User;

/**
 * User role value object
 */
final readonly class UserRole
{
    public const ADMIN = 'admin';
    public const MODERATOR = 'moderator';
    public const USER = 'user';
    public const GUEST = 'guest';
    public const BANNED = 'banned';

    private const VALID_ROLES = [
        self::ADMIN,
        self::MODERATOR,
        self::USER,
        self::GUEST,
        self::BANNED,
    ];

    private const ROLE_HIERARCHY = [
        self::ADMIN => 100,
        self::MODERATOR => 50,
        self::USER => 10,
        self::GUEST => 5,
        self::BANNED => 0,
    ];

    private function __construct(
        private string $value
    ) {
        $this->validate();
    }

    public static function fromString(string $role): self
    {
        return new self(strtolower(trim($role)));
    }

    public static function admin(): self
    {
        return new self(self::ADMIN);
    }

    public static function moderator(): self
    {
        return new self(self::MODERATOR);
    }

    public static function user(): self
    {
        return new self(self::USER);
    }

    public static function guest(): self
    {
        return new self(self::GUEST);
    }

    public static function banned(): self
    {
        return new self(self::BANNED);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(UserRole $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private function validate(): void
    {
        if (!in_array($this->value, self::VALID_ROLES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid role "%s". Valid roles are: %s', $this->value, implode(', ', self::VALID_ROLES))
            );
        }
    }

    public function isAdmin(): bool
    {
        return $this->value === self::ADMIN;
    }

    public function isModerator(): bool
    {
        return $this->value === self::MODERATOR;
    }

    public function isUser(): bool
    {
        return $this->value === self::USER;
    }

    public function isGuest(): bool
    {
        return $this->value === self::GUEST;
    }

    public function isBanned(): bool
    {
        return $this->value === self::BANNED;
    }

    public function isStaff(): bool
    {
        return $this->isAdmin() || $this->isModerator();
    }

    public function isActive(): bool
    {
        return !$this->isBanned();
    }

    public function canManageUsers(): bool
    {
        return $this->isAdmin() || $this->isModerator();
    }

    public function canManageSystem(): bool
    {
        return $this->isAdmin();
    }

    public function canAccessAdminPanel(): bool
    {
        return $this->isStaff();
    }

    public function canCreateContent(): bool
    {
        return $this->isActive() && !$this->isGuest();
    }

    public function canModerateContent(): bool
    {
        return $this->isStaff();
    }

    public function getLevel(): int
    {
        return self::ROLE_HIERARCHY[$this->value];
    }

    public function isHigherThan(UserRole $other): bool
    {
        return $this->getLevel() > $other->getLevel();
    }

    public function isLowerThan(UserRole $other): bool
    {
        return $this->getLevel() < $other->getLevel();
    }

    public function isSameLevel(UserRole $other): bool
    {
        return $this->getLevel() === $other->getLevel();
    }

    public function getDisplayName(): string
    {
        return match ($this->value) {
            self::ADMIN => 'Administrator',
            self::MODERATOR => 'Moderator',
            self::USER => 'User',
            self::GUEST => 'Guest',
            self::BANNED => 'Banned',
        };
    }

    public function getDescription(): string
    {
        return match ($this->value) {
            self::ADMIN => 'Full system access and management capabilities',
            self::MODERATOR => 'User management and content moderation capabilities',
            self::USER => 'Standard user with basic access',
            self::GUEST => 'Limited access guest user',
            self::BANNED => 'Banned user with no access',
        };
    }

    public function getColor(): string
    {
        return match ($this->value) {
            self::ADMIN => '#dc2626', // red
            self::MODERATOR => '#ea580c', // orange
            self::USER => '#059669', // green
            self::GUEST => '#6b7280', // gray
            self::BANNED => '#374151', // dark gray
        };
    }

    public function getIcon(): string
    {
        return match ($this->value) {
            self::ADMIN => '👑',
            self::MODERATOR => '🛡️',
            self::USER => '👤',
            self::GUEST => '👥',
            self::BANNED => '🚫',
        };
    }

    public static function getAllRoles(): array
    {
        return self::VALID_ROLES;
    }

    public static function getStaffRoles(): array
    {
        return [self::ADMIN, self::MODERATOR];
    }

    public static function getActiveRoles(): array
    {
        return array_filter(self::VALID_ROLES, fn($role) => $role !== self::BANNED);
    }
}