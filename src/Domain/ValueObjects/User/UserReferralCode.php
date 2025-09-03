<?php

declare(strict_types=1);

namespace BotMirzaPanel\Domain\ValueObjects\User;

/**
 * User referral code value object
 */
final readonly class UserReferralCode
{
    private function __construct(
        private string $value
    ) {
        $this->validate();
    }

    public static function fromString(string $code): self
    {
        return new self(strtoupper(trim($code)));
    }

    public static function generate(): self
    {
        // Generate a random 8-character alphanumeric code
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return new self($code);
    }

    public static function generateFromUserId(string $userId): self
    {
        // Generate a deterministic code based on user ID
        $hash = hash('sha256', $userId);
        $code = strtoupper(substr($hash, 0, 8));
        return new self($code);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(UserReferralCode $other): bool
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
            throw new \InvalidArgumentException('Referral code cannot be empty');
        }

        if (strlen($this->value) < 4) {
            throw new \InvalidArgumentException('Referral code must be at least 4 characters long');
        }

        if (strlen($this->value) > 20) {
            throw new \InvalidArgumentException('Referral code cannot be longer than 20 characters');
        }

        if (!preg_match('/^[A-Z0-9]+$/', $this->value)) {
            throw new \InvalidArgumentException('Referral code can only contain uppercase letters and numbers');
        }

        // Avoid confusing characters
        if (preg_match('/[0O1IL]/', $this->value)) {
            throw new \InvalidArgumentException('Referral code cannot contain confusing characters (0, O, 1, I, L)');
        }
    }

    public function getLength(): int
    {
        return strlen($this->value);
    }

    public function isValid(): bool
    {
        try {
            $this->validate();
            return true;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    public function getFormatted(): string
    {
        // Format as groups of 4 characters separated by hyphens
        return implode('-', str_split($this->value, 4));
    }

    public function contains(string $substring): bool
    {
        return str_contains($this->value, strtoupper($substring));
    }

    public function startsWith(string $prefix): bool
    {
        return str_starts_with($this->value, strtoupper($prefix));
    }

    public function endsWith(string $suffix): bool
    {
        return str_ends_with($this->value, strtoupper($suffix));
    }

    public function getChecksum(): string
    {
        return substr(md5($this->value), 0, 4);
    }

    public function withChecksum(): string
    {
        return $this->value . $this->getChecksum();
    }
}