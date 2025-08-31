<?php

declare(strict_types=1);

namespace Domain\ValueObjects\User;

/**
 * User Telegram chat ID value object
 */
final readonly class UserTelegramChatId
{
    private function __construct(
        private string $value
    ) {
        $this->validate();
    }

    public static function fromString(string $chatId): self
    {
        return new self(trim($chatId));
    }

    public static function fromInt(int $chatId): self
    {
        return new self((string) $chatId);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getIntValue(): int
    {
        return (int) $this->value;
    }

    public function equals(UserTelegramChatId $other): bool
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
            throw new \InvalidArgumentException('Telegram chat ID cannot be empty');
        }

        // Telegram chat IDs are integers (can be negative for groups/channels)
        if (!preg_match('/^-?\d+$/', $this->value)) {
            throw new \InvalidArgumentException('Invalid Telegram chat ID format');
        }

        // Telegram chat IDs are typically within certain ranges
        $intValue = (int) $this->value;
        if (abs($intValue) > 9999999999999) { // 13 digits max
            throw new \InvalidArgumentException('Telegram chat ID is out of valid range');
        }
    }

    public function isPrivateChat(): bool
    {
        return $this->getIntValue() > 0;
    }

    public function isGroupChat(): bool
    {
        $intValue = $this->getIntValue();
        return $intValue < 0 && $intValue > -1000000000000;
    }

    public function isChannelChat(): bool
    {
        return $this->getIntValue() <= -1000000000000;
    }

    public function isSupergroup(): bool
    {
        $intValue = $this->getIntValue();
        return $intValue < -1000000000000;
    }

    public function getChatType(): string
    {
        if ($this->isPrivateChat()) {
            return 'private';
        }
        if ($this->isChannelChat()) {
            return 'channel';
        }
        if ($this->isSupergroup()) {
            return 'supergroup';
        }
        if ($this->isGroupChat()) {
            return 'group';
        }
        return 'unknown';
    }

    public function getSupergroupId(): ?int
    {
        if ($this->isSupergroup()) {
            // Convert supergroup chat_id to supergroup_id
            return abs($this->getIntValue()) - 1000000000000;
        }
        return null;
    }

    public function getChannelId(): ?int
    {
        if ($this->isChannelChat()) {
            // Convert channel chat_id to channel_id
            return abs($this->getIntValue()) - 1000000000000;
        }
        return null;
    }
}