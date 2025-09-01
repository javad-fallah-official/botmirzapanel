<?php

declare(strict_types=1);

namespace BotMirzaPanel\Application\Queries\User;

use BotMirzaPanel\Application\Queries\QueryHandlerInterface;
use BotMirzaPanel\Domain\Repositories\UserRepositoryInterface;

/**
 * Handler for GetUsersQuery
 */
class GetUsersQueryHandler implements QueryHandlerInterface
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository): void
    {
        $this->userRepository = $userRepository;
    }

    public function handle(GetUsersQuery $query): array
    {
        return $this->userRepository->findBy(
            $query->getFilters(),
            $query->getOrderBy(),
            $query->getLimit(),
            $query->getOffset()
        );
    }
}