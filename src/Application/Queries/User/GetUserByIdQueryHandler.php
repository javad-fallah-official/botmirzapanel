<?php

declare(strict_types=1);

namespace BotMirzaPanel\Application\Queries\User;

use BotMirzaPanel\Application\Queries\QueryHandlerInterface;
use BotMirzaPanel\Application\Queries\QueryInterface;
use BotMirzaPanel\Domain\Entities\User\User;
use BotMirzaPanel\Domain\Repositories\UserRepositoryInterface;
use BotMirzaPanel\Domain\ValueObjects\User\UserId;
use BotMirzaPanel\Domain\Exceptions\EntityNotFoundException;

/**
 * Handler for getting a user by their ID
 */
final readonly class GetUserByIdQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function handle(QueryInterface $query): ?User
    {
        if (!$query instanceof GetUserByIdQuery) {
            throw new \InvalidArgumentException('Invalid query type');
        }

        $userId = UserId::fromString($query->userId);
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw new EntityNotFoundException("User with ID {$query->userId} not found");
        }

        return $user;
    }
}