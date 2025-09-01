<?php

namespace BotMirzaPanel\Domain\ValueObjects\User;

use BotMirzaPanel\Shared\Exceptions\ValidationException;

/**
 * Username value object
 * Represents a user's username with validation
 */
final class Username
{
    private string $value;

    public function __construct(string $value)
    {
        $this->validate($value);
        $this->value = $value;
    }

    private function validate(string $value): void
    {
        if (empty(trim($value))) {
            throw new ValidationException('Username cannot be empty');
        }

        if (strlen($value) < 3) {
            throw new ValidationException('Username must be at least 3 characters long');
        }

        if (strlen($value) > 50) {
            throw new ValidationException('Username cannot be longer than 50 characters');
        }

        if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $value)) {
            throw new ValidationException('Username can only contain letters, numbers, underscores, dots, and hyphens');
        }
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
}