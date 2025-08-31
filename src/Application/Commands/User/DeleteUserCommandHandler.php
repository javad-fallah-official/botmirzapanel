<?php

declare(strict_types=1);

namespace BotMirzaPanel\Application\Commands\User;

use BotMirzaPanel\Application\Commands\CommandHandlerInterface;
use BotMirzaPanel\Application\Commands\CommandInterface;
use BotMirzaPanel\Domain\Repositories\UserRepositoryInterface;
use BotMirzaPanel\Domain\Services\User\UserService;
use BotMirzaPanel\Domain\ValueObjects\User\UserId;

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
            // Soft delete by deactivating the user
            $user->deactivate();
            $this->userRepository->save($user);
        } else {
            // Hard delete
            $this->userRepository->delete($user);
        }

        return true;
    }
}