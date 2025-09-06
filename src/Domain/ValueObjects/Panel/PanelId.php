<?php

namespace BotMirzaPanel\Domain\ValueObjects\Panel;

/**
 * PanelId Value Object
 * 
 * Represents a unique identifier for a Panel entity.
 */
class PanelId
{
    private string $value;

    public function __construct()
    {
        $this->validate($id);
        $this->value = $id;
    }

    public static function generate(): self
    {
        return new self(self::generateUuid());
    }

    public static function fromString(string $id): self
    {
        return new self($id);
    }

    public static function fromInt(int $id): self
    {
        return new self((string) $id);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(PanelId $other): bool
    {
        return $this->value === $other->value;
    }

    public function isUuid(): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $this->value) === 1;
    }

    public function isNumeric(): bool
    {
        return is_numeric($this->value);
    }

    public function toInt(): ?int
    {
        return $this->isNumeric() ? (int) $this->value : null;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private function validate(string $id): void
    {
        if (empty($id)) {
            throw new \InvalidArgumentException('Panel ID cannot be empty.');
        }

        if (strlen($id) > 255) {
            throw new \InvalidArgumentException('Panel ID is too long (max 255 characters).');
        }

        // Allow UUIDs, numeric IDs, or alphanumeric strings
        if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $id)) {
            throw new \InvalidArgumentException('Panel ID contains invalid characters.');
        }
    }

    private static function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}