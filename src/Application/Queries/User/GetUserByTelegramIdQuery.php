<?php

declare(strict_types=1);

namespace BotMirzaPanel\Application\Queries\User;

use BotMirzaPanel\Application\Queries\QueryInterface;
use BotMirzaPanel\Domain\ValueObjects\User\TelegramId;

/**
 * Query to get user by Telegram ID
 */
class GetUserByTelegramIdQuery implements QueryInterface
{
    private TelegramId $telegramId;

    public function __construct(TelegramId $telegramId)
    {
        $this->telegramId = $telegramId;
    }

    public function getTelegramId(): TelegramId
    {
        return $this->telegramId;
    }

    public function toArray(): array
    {
        return [
            'telegram_id' => $this->telegramId->getValue()
        ];
    }
}