<?php

namespace BotMirzaPanel\Domain\Entities\User;

use BotMirzaPanel\Domain\ValueObjects\User\UserId;
use DateTime;

/**
 * UserPreferences Entity
 * 
 * Represents user preferences and settings.
 * This is a child entity of the User aggregate.
 */
class UserPreferences
{
    private UserId $userId;
    private bool $emailNotifications;
    private bool $telegramNotifications;
    private bool $smsNotifications;
    private bool $pushNotifications;
    private string $theme; // 'light', 'dark', 'auto'
    private string $dateFormat; // 'Y-m-d', 'd/m/Y', 'm/d/Y'
    private string $timeFormat; // '24h', '12h'
    private string $currency; // 'USD', 'EUR', 'IRR', etc.
    private bool $showBalance;
    private bool $showUsage;
    private bool $autoRenewal;
    private int $reminderDays; // Days before expiration to send reminder
    private DateTime $createdAt;
    private DateTime $updatedAt;

    public function __construct(
        UserId $userId,
        bool $emailNotifications = true,
        bool $telegramNotifications = true,
        bool $smsNotifications = false,
        bool $pushNotifications = true,
        string $theme = 'auto',
        string $dateFormat = 'Y-m-d',
        string $timeFormat = '24h',
        string $currency = 'USD',
        bool $showBalance = true,
        bool $showUsage = true,
        bool $autoRenewal = false,
        int $reminderDays = 3
    ) {
        $this->userId = $userId;
        $this->emailNotifications = $emailNotifications;
        $this->telegramNotifications = $telegramNotifications;
        $this->smsNotifications = $smsNotifications;
        $this->pushNotifications = $pushNotifications;
        $this->theme = $theme;
        $this->dateFormat = $dateFormat;
        $this->timeFormat = $timeFormat;
        $this->currency = $currency;
        $this->showBalance = $showBalance;
        $this->showUsage = $showUsage;
        $this->autoRenewal = $autoRenewal;
        $this->reminderDays = $reminderDays;
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    public static function createDefault(UserId $userId): self
    {
        return new self($userId);
    }

    public function enableEmailNotifications(): void
    {
        $this->emailNotifications = true;
        $this->updatedAt = new DateTime();
    }

    public function disableEmailNotifications(): void
    {
        $this->emailNotifications = false;
        $this->updatedAt = new DateTime();
    }

    public function enableTelegramNotifications(): void
    {
        $this->telegramNotifications = true;
        $this->updatedAt = new DateTime();
    }

    public function disableTelegramNotifications(): void
    {
        $this->telegramNotifications = false;
        $this->updatedAt = new DateTime();
    }

    public function enableSmsNotifications(): void
    {
        $this->smsNotifications = true;
        $this->updatedAt = new DateTime();
    }

    public function disableSmsNotifications(): void
    {
        $this->smsNotifications = false;
        $this->updatedAt = new DateTime();
    }

    public function enablePushNotifications(): void
    {
        $this->pushNotifications = true;
        $this->updatedAt = new DateTime();
    }

    public function disablePushNotifications(): void
    {
        $this->pushNotifications = false;
        $this->updatedAt = new DateTime();
    }

    public function updateTheme(string $theme): void
    {
        if (!in_array($theme, ['light', 'dark', 'auto'])) {
            throw new \InvalidArgumentException('Invalid theme. Must be light, dark, or auto.');
        }
        
        $this->theme = $theme;
        $this->updatedAt = new DateTime();
    }

    public function updateDateFormat(string $dateFormat): void
    {
        $this->dateFormat = $dateFormat;
        $this->updatedAt = new DateTime();
    }

    public function updateTimeFormat(string $timeFormat): void
    {
        if (!in_array($timeFormat, ['24h', '12h'])) {
            throw new \InvalidArgumentException('Invalid time format. Must be 24h or 12h.');
        }
        
        $this->timeFormat = $timeFormat;
        $this->updatedAt = new DateTime();
    }

    public function updateCurrency(string $currency): void
    {
        $this->currency = $currency;
        $this->updatedAt = new DateTime();
    }

    public function enableAutoRenewal(): void
    {
        $this->autoRenewal = true;
        $this->updatedAt = new DateTime();
    }

    public function disableAutoRenewal(): void
    {
        $this->autoRenewal = false;
        $this->updatedAt = new DateTime();
    }

    public function updateReminderDays(int $days): void
    {
        if ($days < 0 || $days > 30) {
            throw new \InvalidArgumentException('Reminder days must be between 0 and 30.');
        }
        
        $this->reminderDays = $days;
        $this->updatedAt = new DateTime();
    }

    // Getters
    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function hasEmailNotifications(): bool
    {
        return $this->emailNotifications;
    }

    public function hasTelegramNotifications(): bool
    {
        return $this->telegramNotifications;
    }

    public function hasSmsNotifications(): bool
    {
        return $this->smsNotifications;
    }

    public function hasPushNotifications(): bool
    {
        return $this->pushNotifications;
    }

    public function getTheme(): string
    {
        return $this->theme;
    }

    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    public function getTimeFormat(): string
    {
        return $this->timeFormat;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function shouldShowBalance(): bool
    {
        return $this->showBalance;
    }

    public function shouldShowUsage(): bool
    {
        return $this->showUsage;
    }

    public function hasAutoRenewal(): bool
    {
        return $this->autoRenewal;
    }

    public function getReminderDays(): int
    {
        return $this->reminderDays;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }
}