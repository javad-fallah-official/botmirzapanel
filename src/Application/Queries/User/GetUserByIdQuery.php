<?php

declare(strict_types=1);

namespace BotMirzaPanel\Application\Queries\User;

use BotMirzaPanel\Application\Queries\QueryInterface;

/**
 * Query to get a user by their ID
 */
final readonly class GetUserByIdQuery implements QueryInterface
{
    public function __construct(
        public string $userId
    ) {}

    public function toArray(): array
    {
        return [
            'userId' => $this->userId
        ];
    }
}