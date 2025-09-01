<?php

declare(strict_types=1);

namespace BotMirzaPanel\Domain\ValueObjects\User;

use BotMirzaPanel\Domain\ValueObjects\ValueObject;
use BotMirzaPanel\Shared\Exceptions\DomainException;

/**
 * Telegram ID value object
 */
class TelegramId extends ValueObject
{
    private int $value;

    public function __construct(int $value): void
    {
        $this->validate($value);
        $this->value = $value;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self && $this->value === $other->value;
    }

    public function toString(): string
    {
        return (string) $this->value;
    }

    public function toArray(): array
    {
        return ['telegram_id' => $this->value];
    }

    private function validate(int $value): void
    {
        if ($value <= 0) {
            throw DomainException::businessRuleViolation(
                'telegram_id_positive',
                'Telegram ID must be a positive integer',
                'User',
                ['provided_value' => $value]
            );
        }

        // Telegram user IDs are typically between 1 and 2^63-1
        if ($value > PHP_INT_MAX) {
            throw DomainException::businessRuleViolation(
                'telegram_id_too_large',
                'Telegram ID is too large',
                'User',
                ['provided_value' => $value]
            );
        }
    }

    /**
     * Create from string
     */
    public static function fromString(string $value): self
    {
        $intValue = filter_var($value, FILTER_VALIDATE_INT);
        
        if ($intValue === false) {
            throw DomainException::businessRuleViolation(
                'telegram_id_invalid_format',
                'Telegram ID must be a valid integer',
                'User',
                ['provided_value' => $value]
            );
        }

        return new self($intValue);
    }
}