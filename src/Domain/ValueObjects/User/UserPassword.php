<?php

declare(strict_types=1);

namespace Domain\ValueObjects\User;

/**
 * User password value object
 */
final readonly class UserPassword
{
    private function __construct(
        private string $hashedValue
    ) {}

    public static function fromPlainText(string $plainPassword): self
    {
        if (empty($plainPassword)) {
            throw new \InvalidArgumentException('Password cannot be empty');
        }

        if (strlen($plainPassword) < 8) {
            throw new \InvalidArgumentException('Password must be at least 8 characters long');
        }

        if (strlen($plainPassword) > 255) {
            throw new \InvalidArgumentException('Password cannot be longer than 255 characters');
        }

        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
        
        if ($hashedPassword === false) {
            throw new \RuntimeException('Failed to hash password');
        }

        return new self($hashedPassword);
    }

    public static function fromHash(string $hashedPassword): self
    {
        if (empty($hashedPassword)) {
            throw new \InvalidArgumentException('Hashed password cannot be empty');
        }

        return new self($hashedPassword);
    }

    public function getHashedValue(): string
    {
        return $this->hashedValue;
    }

    public function verify(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->hashedValue);
    }

    public function needsRehash(): bool
    {
        return password_needs_rehash($this->hashedValue, PASSWORD_DEFAULT);
    }

    public function rehash(): self
    {
        // This method would need the plain password to rehash
        // In practice, you'd handle this during login when you have the plain password
        throw new \LogicException('Cannot rehash without the plain password');
    }

    public function equals(UserPassword $other): bool
    {
        return $this->hashedValue === $other->hashedValue;
    }

    public function __toString(): string
    {
        return '[PROTECTED]';
    }

    public static function validateStrength(string $plainPassword): array
    {
        $errors = [];

        if (strlen($plainPassword) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }

        if (!preg_match('/[a-z]/', $plainPassword)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }

        if (!preg_match('/[A-Z]/', $plainPassword)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }

        if (!preg_match('/\d/', $plainPassword)) {
            $errors[] = 'Password must contain at least one number';
        }

        if (!preg_match('/[^\w\s]/', $plainPassword)) {
            $errors[] = 'Password must contain at least one special character';
        }

        return $errors;
    }

    public static function isStrong(string $plainPassword): bool
    {
        return empty(self::validateStrength($plainPassword));
    }
}