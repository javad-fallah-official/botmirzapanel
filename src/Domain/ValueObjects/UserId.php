<?php

declare(strict_types=1);

namespace BotMirzaPanel\Domain\ValueObjects;

/**
 * User ID value object
 * Ensures user IDs are valid and immutable
 */
class UserId
{
    private int $value;

    public function __construct()
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException('User ID must be a positive integer');
        }
        
        $this->value = $value;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function equals(UserId $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }

    /**
     * Create from string representation
     */
    public static function fromString(string $value): self
    {
        $intValue = filter_var($value, FILTER_VALIDATE_INT);
        
        if ($intValue === false) {
            throw new \InvalidArgumentException('Invalid user ID format');
        }
        
        return new self($intValue);
    }
}