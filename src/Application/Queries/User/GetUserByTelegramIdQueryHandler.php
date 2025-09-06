<?php

declare(strict_types=1);

namespace BotMirzaPanel\Application\Queries\User;

use BotMirzaPanel\Application\Queries\QueryHandlerInterface;
use BotMirzaPanel\Domain\Repositories\UserRepositoryInterface;
use BotMirzaPanel\Domain\Entities\User\User;

/**
 * Handler for GetUserByTelegramIdQuery
 */
class GetUserByTelegramIdQueryHandler implements QueryHandlerInterface
{
    private UserRepositoryInterface $userRepository;

    public function __construct()
    {
        $this->userRepository = $userRepository;
    }

    public function handle(GetUserByTelegramIdQuery $query): ?User
    {
        return $this->userRepository->findByTelegramId($query->getTelegramId());
    }
}