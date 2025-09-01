<?php

declare(strict_types=1);

namespace BotMirzaPanel\Application\Commands\User;

use BotMirzaPanel\Application\Commands\CommandHandlerInterface;
use BotMirzaPanel\Application\Commands\CommandInterface;
use BotMirzaPanel\Domain\Entities\User\User;
use BotMirzaPanel\Domain\Repositories\UserRepositoryInterface;
use BotMirzaPanel\Domain\Services\User\UserService;
use BotMirzaPanel\Domain\ValueObjects\Common\Email;

/**
 * Handler for creating a new user
 */
final readonly class CreateUserCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserService $userService
    ) {}

    public function handle(CommandInterface $command): User
    {
        if (!$command instanceof CreateUserCommand) {
            throw new \InvalidArgumentException('Invalid command type');
        }

        // Check for existing users
        if ($command->email && $this->userRepository->emailExists(Email::fromString($command->email))) {
            throw new \DomainException('User with this email already exists');
        }

        // Note: Phone number and Telegram ID existence checks would need to be added to the repository interface

        // Use domain service for business logic
        $user = $this->userService->createUser(
            telegramId: $command->telegramChatId,
            username: $command->email, // Use email as username for now
            firstName: $command->firstName,
            lastName: $command->lastName,
            email: $command->email,
            phoneNumber: $command->phoneNumber
        );
        
        // Activate user if requested
        if ($command->isActive) {
            $user->activate();
        }

        // Save user
        $this->userRepository->save($user);

        return $user;
    }
}