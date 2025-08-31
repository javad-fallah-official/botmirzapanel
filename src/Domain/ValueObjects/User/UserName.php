<?php

declare(strict_types=1);

namespace BotMirzaPanel\Domain\ValueObjects\User;

/**
 * Username value object
 */
final readonly class Username
{
    private function __construct(
        private string $value
    ) {
        $this->validate();
    }

    public static function fromString(string $username): self
    {
        return new self(trim($username));
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(Username $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private function validate(): void
    {
        if (empty($this->value)) {
            throw new \InvalidArgumentException('Username cannot be empty');
        }

        if (strlen($this->value) < 3) {
            throw new \InvalidArgumentException('Username must be at least 3 characters long');
        }

        if (strlen($this->value) > 50) {
            throw new \InvalidArgumentException('Username cannot be longer than 50 characters');
        }

        if (!preg_match('/^[a-zA-Z0-9._@-]+$/', $this->value)) {
            throw new \InvalidArgumentException('Username contains invalid characters');
        }
    }

    /**
     * Get lowercase version of username
     */
    public function toLowerCase(): string
    {
        return strtolower($this->value);
    }

    /**
     * Check if username is an email format
     */
    public function isEmail(): bool
    {
        return filter_var($this->value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function getLength(): int
    {
        return mb_strlen($this->value);
    }

    /**
     * Check if username contains a specific string
     */
    public function contains(string $needle): bool
    {
        return str_contains(strtolower($this->value), strtolower($needle));
    }

    /**
     * Check if username starts with a specific string
     */
    public function startsWith(string $prefix): bool
    {
        return str_starts_with(strtolower($this->value), strtolower($prefix));
    }

    /**
     * Check if username ends with a specific string
     */
    public function endsWith(string $suffix): bool
    {
        return str_ends_with(strtolower($this->value), strtolower($suffix));
    }
}