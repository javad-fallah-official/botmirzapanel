<?php

namespace BotMirzaPanel\Domain\ValueObjects\Common;

/**
 * Email Value Object
 * 
 * Represents a valid email address with proper validation.
 */
class Email
{
    private string $value;

    public function __construct(string $email)
    {
        $this->validate($email);
        $this->value = strtolower(trim($email));
    }

    public static function fromString(string $email): self
    {
        return new self($email);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getLocalPart(): string
    {
        return explode('@', $this->value)[0];
    }

    public function getDomain(): string
    {
        return explode('@', $this->value)[1];
    }

    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }

    public function isGmail(): bool
    {
        return $this->getDomain() === 'gmail.com';
    }

    public function isYahoo(): bool
    {
        return in_array($this->getDomain(), ['yahoo.com', 'yahoo.co.uk', 'yahoo.fr']);
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
            'throwaway.email',
            'temp-mail.org',
            'yopmail.com'
        ];
        
        return in_array($this->getDomain(), $disposableDomains);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private function validate(string $email): void
    {
        if (empty($email)) {
            throw new \InvalidArgumentException('Email cannot be empty.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format.');
        }

        if (strlen($email) > 254) {
            throw new \InvalidArgumentException('Email is too long (max 254 characters).');
        }

        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            throw new \InvalidArgumentException('Invalid email format.');
        }

        [$localPart, $domain] = $parts;

        if (strlen($localPart) > 64) {
            throw new \InvalidArgumentException('Email local part is too long (max 64 characters).');
        }

        if (strlen($domain) > 253) {
            throw new \InvalidArgumentException('Email domain is too long (max 253 characters).');
        }

        if (empty($localPart) || empty($domain)) {
            throw new \InvalidArgumentException('Email local part and domain cannot be empty.');
        }

        // Check for consecutive dots
        if (strpos($email, '..') !== false) {
            throw new \InvalidArgumentException('Email cannot contain consecutive dots.');
        }

        // Check for leading/trailing dots in local part
        if ($localPart[0] === '.' || $localPart[strlen($localPart) - 1] === '.') {
            throw new \InvalidArgumentException('Email local part cannot start or end with a dot.');
        }
    }
}