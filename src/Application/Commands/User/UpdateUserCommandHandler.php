<?php

declare(strict_types=1);

namespace BotMirzaPanel\Application\Commands\User;

use BotMirzaPanel\Application\Commands\CommandHandlerInterface;
use BotMirzaPanel\Application\Commands\CommandInterface;
use BotMirzaPanel\Domain\Entities\User\User;
use BotMirzaPanel\Domain\Repositories\UserRepositoryInterface;
use BotMirzaPanel\Domain\Services\User\UserService;
use BotMirzaPanel\Domain\ValueObjects\User\UserId;
use BotMirzaPanel\Domain\ValueObjects\Common\Email;
use BotMirzaPanel\Domain\ValueObjects\Common\PhoneNumber;

/**
 * Handler for updating an existing user
 */
final readonly class UpdateUserCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserService $userService
    ) {}

    public function handle(CommandInterface $command): User
    {
        if (!$command instanceof UpdateUserCommand) {
            throw new \InvalidArgumentException('Invalid command type');
        }

        $userId = UserId::fromString($command->userId);
        $user = $this->userRepository->findById($userId);
        
        if (!$user) {
            throw new \DomainException('User not found');
        }

        // Check for email conflicts
        if ($command->email && (!$user->getEmail() || $command->email !== $user->getEmail()->getValue())) {
            if ($this->userRepository->emailExists(Email::fromString($command->email), $userId)) {
                throw new \DomainException('User with this email already exists');
            }
        }

        // Note: Phone number and Telegram ID conflict checks would need additional repository methods

        // Use domain service for business logic
        $updatedUser = $this->userService->updateUserProfile(
            user: $user,
            username: null, // Keep existing username
            firstName: $command->firstName,
            lastName: $command->lastName,
            email: $command->email,
            phoneNumber: $command->phoneNumber
        );
        
        // Handle activation/deactivation
        if ($command->isActive !== null) {
            if ($command->isActive && !$updatedUser->isActive()) {
                $updatedUser->activate();
            } elseif (!$command->isActive && $updatedUser->isActive()) {
                $updatedUser->deactivate();
            }
        }

        // Save updated user
        $this->userRepository->save($updatedUser);

        return $updatedUser;
    }
}