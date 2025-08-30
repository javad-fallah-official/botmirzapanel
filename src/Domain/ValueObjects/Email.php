<?php

declare(strict_types=1);

namespace BotMirzaPanel\Domain\ValueObjects;

use BotMirzaPanel\Shared\Constants\AppConstants;

/**
 * Email value object
 * Ensures email addresses are valid and immutable
 */
class Email
{
    private string $value;

    public function __construct(string $value)
    {
        $value = trim(strtolower($value));
        
        if (!$this->isValid($value)) {
            throw new \InvalidArgumentException('Invalid email address format');
        }
        
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getDomain(): string
    {
        return substr($this->value, strpos($this->value, '@') + 1);
    }

    public function getLocalPart(): string
    {
        return substr($this->value, 0, strpos($this->value, '@'));
    }

    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Validate email format
     */
    private function isValid(string $email): bool
    {
        // Basic format validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        // Additional regex validation
        if (!preg_match(AppConstants::EMAIL_REGEX, $email)) {
            return false;
        }
        
        // Check length constraints
        if (strlen($email) > 254) {
            return false;
        }
        
        // Check local part length
        $localPart = substr($email, 0, strpos($email, '@'));
        if (strlen($localPart) > 64) {
            return false;
        }
        
        return true;
    }

    /**
     * Create from string with validation
     */
    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * Check if email is from a disposable email provider
     */
    public function isDisposable(): bool
    {
        $disposableDomains = [
            '10minutemail.com',
            'guerrillamail.com',
            'mailinator.com',
            'tempmail.org',
            'throwaway.email',
            'temp-mail.org'
        ];
        
        return in_array($this->getDomain(), $disposableDomains, true);
    }

    /**
     * Mask email for display purposes
     */
    public function mask(): string
    {
        $localPart = $this->getLocalPart();
        $domain = $this->getDomain();
        
        if (strlen($localPart) <= 2) {
            return str_repeat('*', strlen($localPart)) . '@' . $domain;
        }
        
        $maskedLocal = substr($localPart, 0, 2) . str_repeat('*', strlen($localPart) - 2);
        return $maskedLocal . '@' . $domain;
    }
}