<?php

declare(strict_types=1);

namespace Application\Commands\User;

use Application\Commands\CommandInterface;

/**
 * Command to delete a user
 */
final readonly class DeleteUserCommand implements CommandInterface
{
    public function __construct(
        public string $userId,
        public bool $softDelete = true
    ) {}

    public function toArray(): array
    {
        return [
            'userId' => $this->userId,
            'softDelete' => $this->softDelete,
        ];
    }
}