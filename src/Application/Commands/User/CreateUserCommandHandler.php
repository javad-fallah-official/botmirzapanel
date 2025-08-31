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
use Domain\ValueObjects\User\UserReferralCode;
use Domain\ValueObjects\User\UserStatus;
use Domain\ValueObjects\User\UserRole;
use Domain\ValueObjects\Common\DateTimeRange;

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

        // Check if user already exists
        if ($this->userRepository->existsByEmail($command->email)) {
            throw new \DomainException('User with this email already exists');
        }

        if ($command->phoneNumber && $this->userRepository->existsByPhoneNumber($command->phoneNumber)) {
            throw new \DomainException('User with this phone number already exists');
        }

        if ($command->telegramChatId && $this->userRepository->existsByTelegramChatId($command->telegramChatId)) {
            throw new \DomainException('User with this Telegram chat ID already exists');
        }

        // Create value objects
        $userId = UserId::generate();
        $email = UserEmail::fromString($command->email);
        $password = UserPassword::fromPlainText($command->password);
        $firstName = $command->firstName ? UserName::fromString($command->firstName) : null;
        $lastName = $command->lastName ? UserName::fromString($command->lastName) : null;
        $phoneNumber = $command->phoneNumber ? UserPhoneNumber::fromString($command->phoneNumber) : null;
        $telegramChatId = $command->telegramChatId ? UserTelegramChatId::fromString($command->telegramChatId) : null;
        $referralCode = $command->referralCode ? UserReferralCode::fromString($command->referralCode) : UserReferralCode::generate();
        $status = $command->isActive ? UserStatus::active() : UserStatus::inactive();
        $role = UserRole::user();
        $createdAt = new \DateTimeImmutable();

        // Create user entity
        $user = new User(
            id: $userId,
            email: $email,
            password: $password,
            firstName: $firstName,
            lastName: $lastName,
            phoneNumber: $phoneNumber,
            telegramChatId: $telegramChatId,
            referralCode: $referralCode,
            status: $status,
            role: $role,
            isPremium: $command->isPremium,
            premiumExpiresAt: null,
            lastLoginAt: null,
            emailVerifiedAt: null,
            phoneVerifiedAt: null,
            createdAt: $createdAt,
            updatedAt: $createdAt,
            metadata: $command->metadata ?? []
        );

        // Use domain service for business logic
        $user = $this->userService->createUser(
            email: $email,
            password: $password,
            firstName: $firstName,
            lastName: $lastName,
            phoneNumber: $phoneNumber,
            telegramChatId: $telegramChatId,
            referralCode: $referralCode,
            isActive: $command->isActive,
            isPremium: $command->isPremium,
            metadata: $command->metadata ?? []
        );

        // Save user
        $this->userRepository->save($user);

        return $user;
    }
}