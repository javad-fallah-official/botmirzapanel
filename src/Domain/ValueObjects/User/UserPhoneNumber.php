<?php

declare(strict_types=1);

namespace BotMirzaPanel\Domain\ValueObjects\User;

/**
 * User phone number value object
 */
final readonly class UserPhoneNumber
{
    private function __construct(
        private string $value
    ) {
        $this->validate();
    }

    public static function fromString(string $phoneNumber): self
    {
        // Clean the phone number
        $cleaned = preg_replace('/[^\d+]/', '', $phoneNumber);
        return new self($cleaned);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(UserPhoneNumber $other): bool
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
            throw new \InvalidArgumentException('Phone number cannot be empty');
        }

        // Basic validation for international phone numbers
        if (!preg_match('/^\+?[1-9]\d{1,14}$/', $this->value)) {
            throw new \InvalidArgumentException('Invalid phone number format');
        }

        if (strlen($this->value) < 7) {
            throw new \InvalidArgumentException('Phone number is too short');
        }

        if (strlen($this->value) > 15) {
            throw new \InvalidArgumentException('Phone number is too long');
        }
    }

    public function getCountryCode(): ?string
    {
        if (!$this->hasCountryCode()) {
            return null;
        }

        // Extract country code (simplified logic)
        if (str_starts_with($this->value, '+1')) {
            return '+1';
        }
        if (str_starts_with($this->value, '+44')) {
            return '+44';
        }
        if (str_starts_with($this->value, '+49')) {
            return '+49';
        }
        if (str_starts_with($this->value, '+33')) {
            return '+33';
        }
        if (str_starts_with($this->value, '+98')) {
            return '+98';
        }

        // For other country codes, extract first 1-3 digits after +
        if (preg_match('/^\+(\d{1,3})/', $this->value, $matches)) {
            return '+' . $matches[1];
        }

        return null;
    }

    public function hasCountryCode(): bool
    {
        return str_starts_with($this->value, '+');
    }

    public function getNumberWithoutCountryCode(): string
    {
        $countryCode = $this->getCountryCode();
        if ($countryCode) {
            return substr($this->value, strlen($countryCode));
        }
        return $this->value;
    }

    public function getFormatted(): string
    {
        $number = $this->getNumberWithoutCountryCode();
        $countryCode = $this->getCountryCode();

        // Simple formatting for common patterns
        if (strlen($number) === 10) {
            // Format as (XXX) XXX-XXXX
            return ($countryCode ? $countryCode . ' ' : '') . 
                   '(' . substr($number, 0, 3) . ') ' . 
                   substr($number, 3, 3) . '-' . 
                   substr($number, 6);
        }

        return $this->value;
    }

    public function isIranian(): bool
    {
        return $this->getCountryCode() === '+98' || 
               (strlen($this->value) === 11 && str_starts_with($this->value, '09'));
    }

    public function isUS(): bool
    {
        return $this->getCountryCode() === '+1' || 
               (strlen($this->value) === 10 && !str_starts_with($this->value, '+'));
    }

    public function isMobile(): bool
    {
        // Iranian mobile numbers start with 09
        if ($this->isIranian()) {
            $number = $this->getNumberWithoutCountryCode();
            return str_starts_with($number, '9');
        }

        // For other countries, assume all numbers could be mobile
        return true;
    }
}