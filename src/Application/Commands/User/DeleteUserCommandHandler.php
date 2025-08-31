<?php

declare(strict_types=1);

namespace Application\Commands\User;

use Application\Commands\CommandHandlerInterface;
use Application\Commands\CommandInterface;
use Domain\Repositories\UserRepositoryInterface;
use Domain\Services\User\UserService;
use Domain\ValueObjects\User\UserId;

/**
 * Handler for deleting a user
 */
final readonly class DeleteUserCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserService $userService
    ) {}

    public function handle(CommandInterface $command): bool
    {
        if (!$command instanceof DeleteUserCommand) {
            throw new \InvalidArgumentException('Invalid command type');
        }

        $userId = UserId::fromString($command->userId);
        $user = $this->userRepository->findById($userId);
        
        if (!$user) {
            throw new \DomainException('User not found');
        }

        if ($command->softDelete) {
            // Use domain service for soft delete
            $deletedUser = $this->userService->softDeleteUser($user);
            $this->userRepository->save($deletedUser);
        } else {
            // Hard delete
            $this->userRepository->delete($userId);
        }

        return true;
    }
}