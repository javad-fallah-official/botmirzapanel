<?php

declare(strict_types=1);

namespace Application\Commands\User;

use Application\Commands\CommandHandlerInterface;
use Application\Commands\CommandInterface;
use Domain\Entities\User\User;
use Domain\Repositories\UserRepositoryInterface;
use Domain\Services\User\UserService;
use Domain\ValueObjects\User\UserId;
use Domain\ValueObjects\User\UserEmail;
use Domain\ValueObjects\User\UserPassword;
use Domain\ValueObjects\User\UserName;
use Domain\ValueObjects\User\UserPhoneNumber;
use Domain\ValueObjects\User\UserTelegramChatId;

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
        if ($command->email && $command->email !== $user->getEmail()->getValue()) {
            if ($this->userRepository->existsByEmail($command->email)) {
                throw new \DomainException('User with this email already exists');
            }
        }

        // Check for phone number conflicts
        if ($command->phoneNumber && 
            (!$user->getPhoneNumber() || $command->phoneNumber !== $user->getPhoneNumber()->getValue())) {
            if ($this->userRepository->existsByPhoneNumber($command->phoneNumber)) {
                throw new \DomainException('User with this phone number already exists');
            }
        }

        // Check for Telegram chat ID conflicts
        if ($command->telegramChatId && 
            (!$user->getTelegramChatId() || $command->telegramChatId !== $user->getTelegramChatId()->getValue())) {
            if ($this->userRepository->existsByTelegramChatId($command->telegramChatId)) {
                throw new \DomainException('User with this Telegram chat ID already exists');
            }
        }

        // Use domain service for business logic
        $updatedUser = $this->userService->updateUser(
            user: $user,
            email: $command->email ? UserEmail::fromString($command->email) : null,
            password: $command->password ? UserPassword::fromPlainText($command->password) : null,
            firstName: $command->firstName ? UserName::fromString($command->firstName) : null,
            lastName: $command->lastName ? UserName::fromString($command->lastName) : null,
            phoneNumber: $command->phoneNumber ? UserPhoneNumber::fromString($command->phoneNumber) : null,
            telegramChatId: $command->telegramChatId ? UserTelegramChatId::fromString($command->telegramChatId) : null,
            isActive: $command->isActive,
            isPremium: $command->isPremium,
            premiumExpiresAt: $command->premiumExpiresAt ? new \DateTimeImmutable($command->premiumExpiresAt) : null,
            metadata: $command->metadata
        );

        // Save updated user
        $this->userRepository->save($updatedUser);

        return $updatedUser;
    }
}