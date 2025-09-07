<?php
// WARNING: SQL injection vulnerability detected in this file
// Please review and use prepared statements or secure_* functions


declare(strict_types=1);

namespace BotMirzaPanel\Domain\Services\User;

use BotMirzaPanel\Domain\Entities\User\User;
use BotMirzaPanel\Domain\ValueObjects\User\UserId;
use BotMirzaPanel\Domain\ValueObjects\User\Username;
use BotMirzaPanel\Domain\ValueObjects\Common\Email;
use BotMirzaPanel\Domain\ValueObjects\Common\PhoneNumber;
use BotMirzaPanel\Domain\Services\ValidationService;
use BotMirzaPanel\Shared\Contracts\ServiceInterface;

/**
 * UserService
 * 
 * Domain service for user-related business logic and operations.
 */
class UserService implements ServiceInterface
{
    private ValidationService $validationService;
    private bool $initialized = false;

    public function __construct(ValidationService $validationService) {
        $this->validationService = $validationService;
    }

    public function initialize(): void
    {
        // Nothing special for now; mark service as ready
        $this->initialized = true;
    }

    public function isReady(): bool
    {
        return $this->initialized;
    }

    public function getName(): string
    {
        return 'domain.user_service';
    }

    public function getDependencies(): array
    {
        // Depends on validation service for input checks
        return [ValidationService::class];
    }

    public function cleanup(): void
    {
        // No resources to cleanup currently
        $this->initialized = false;
    }

    /**
     * Create a new user with validation and security checks
     * 
     * @param string $telegramId Telegram user ID
     * @param string|null $username Optional username
     * @param string|null $firstName Optional first name
     * @param string|null $lastName Optional last name
     * @param string|null $email Optional email address
     * @param string|null $phoneNumber Optional phone number
     * @return User Created user entity
     * @throws \InvalidArgumentException When validation fails
     */
    public function createUser(
        string $telegramId,
        ?string $username = null,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $email = null,
        ?string $phoneNumber = null
    ): User {
        // Validate telegram ID
        $this->validationService->validateTelegramId($telegramId);
        
        // Validate email if provided
        $emailVO = null;
        if ($email !== null) {
            $emailVO = Email::fromString($email);
            $this->validationService->validateEmailUniqueness($emailVO);
        }
        
        // Validate phone number if provided
        $phoneVO = null;
        if ($phoneNumber !== null) {
            $phoneVO = PhoneNumber::fromString($phoneNumber);
            $this->validationService->validatePhoneUniqueness($phoneVO);
        }
        
        // Validate username if provided
        if ($username !== null) {
            $this->validationService->validateUsername($username);
            $this->validationService->validateUsernameUniqueness($username);
        }
        
        // Create user
        $usernameVO = $username ? new Username($username) : new Username('user_' . uniqid());
        $user = User::create(
            UserId::generate(),
            $usernameVO,
            $emailVO,
            $phoneVO,
            $telegramId,
            $firstName,
            $lastName
        );
        
        return $user;
    }

    /**
     * Update user profile information
     * 
     * @param User $user User entity to update
     * @param string|null $username Optional new username
     * @param string|null $firstName Optional new first name
     * @param string|null $lastName Optional new last name
     * @param string|null $email Optional new email address
     * @param string|null $phoneNumber Optional new phone number
     * @return User Updated user entity
     * @throws \InvalidArgumentException When validation fails
     */
    public function updateUserProfile(
        User $user,
        ?string $username = null,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $email = null,
        ?string $phoneNumber = null
    ): User {
        // Validate username if changed
        if ($username !== null && $username !== $user->getUsername()) {
            $this->validationService->validateUsername($username);
            $this->validationService->validateUsernameUniqueness($username);
            $user->updateUsername($username);
        }
        
        // Update names
        if ($firstName !== null) {
            $user->updateFirstName($firstName);
        }
        
        if ($lastName !== null) {
            $user->updateLastName($lastName);
        }
        
        // Validate and update email if changed
        if ($email !== null) {
            $emailVO = Email::fromString($email);
            if (!$user->getEmail() || !$user->getEmail()?->equals($emailVO)) {
                $this->validationService->validateEmailUniqueness($emailVO);
                $user->updateEmail($emailVO);
            }
        }
        
        // Validate and update phone if changed
        if ($phoneNumber !== null) {
            $phoneVO = PhoneNumber::fromString($phoneNumber);
            if (!$user->getPhoneNumber() || !$user->getPhoneNumber()?->equals($phoneVO)) {
                $this->validationService->validatePhoneUniqueness($phoneVO);
                $user->updatePhoneNumber($phoneVO);
            }
        }
        
        return $user;
    }

    /**
     * Activate a user account
     */
    public function activateUser(User $user): User
    {
        if ($user->getStatus()->isActive()) {
            throw new \DomainException('User is already active.');
        }
        
        if ($user->getStatus()->isBanned()) {
            throw new \DomainException('Cannot activate a banned user.');
        }
        
        if ($user->getStatus()->isDeleted()) {
            throw new \DomainException('Cannot activate a deleted user.');
        }
        
        $user->activate();
        return $user;
    }

    /**
     * Suspend a user account
     */
    public function suspendUser(User $user, string $reason): User
    {
        if ($user->getStatus()->isSuspended()) {
            throw new \DomainException('User is already suspended.');
        }
        
        if ($user->getStatus()->isBanned()) {
            throw new \DomainException('Cannot suspend a banned user.');
        }
        
        if ($user->getStatus()->isDeleted()) {
            throw new \DomainException('Cannot suspend a deleted user.');
        }
        
        $user->suspend();
        $user->addMetadata('suspension_reason', $reason);
        $user->addMetadata('suspended_at', (new \DateTimeImmutable())->format('c'));
        
        return $user;
    }

    /**
     * Ban a user account
     */
    public function banUser(User $user, string $reason): User
    {
        if ($user->getStatus()->isBanned()) {
            throw new \DomainException('User is already banned.');
        }
        
        if ($user->getStatus()->isDeleted()) {
            throw new \DomainException('Cannot ban a deleted user.');
        }
        
        $user->ban();
        $user->addMetadata('ban_reason', $reason);
        $user->addMetadata('banned_at', (new \DateTimeImmutable())->format('c'));
        
        return $user;
    }

    /**
     * Unban a user account
     */
    public function unbanUser(User $user): User
    {
        if (!$user->getStatus()->isBanned()) {
            throw new \DomainException('User is not banned.');
        }
        
        $user->activate();
        $user->removeMetadata('ban_reason');
        $user->removeMetadata('banned_at');
        
        return $user;
    }
}