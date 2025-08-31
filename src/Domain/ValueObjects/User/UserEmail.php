<?php

declare(strict_types=1);

namespace Domain\ValueObjects\User;

/**
 * User email value object
 */
final readonly class UserEmail
{
    private function __construct(
        private string $value
    ) {
        $this->validate();
    }

    public static function fromString(string $email): self
    {
        return new self($email);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(UserEmail $other): bool
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
            throw new \InvalidArgumentException('Email cannot be empty');
        }

        if (!filter_var($this->value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }

        if (strlen($this->value) > 255) {
            throw new \InvalidArgumentException('Email cannot be longer than 255 characters');
        }
    }

    public function getDomain(): string
    {
        return substr($this->value, strpos($this->value, '@') + 1);
    }

    public function getLocalPart(): string
    {
        return substr($this->value, 0, strpos($this->value, '@'));
    }

    public function isGmail(): bool
    {
        return $this->getDomain() === 'gmail.com';
    }

    public function isYahoo(): bool
    {
        return $this->getDomain() === 'yahoo.com';
    }

    public function isOutlook(): bool
    {
        return in_array($this->getDomain(), ['outlook.com', 'hotmail.com', 'live.com']);
    }

    public function isDisposable(): bool
    {
        $disposableDomains = [
            '10minutemail.com',
            'guerrillamail.com',
            'mailinator.com',
            'tempmail.org',
            'throwaway.email'
        ];

        return in_array($this->getDomain(), $disposableDomains);
    }
}