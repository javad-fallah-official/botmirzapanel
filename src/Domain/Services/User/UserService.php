<?php

declare(strict_types=1);

namespace BotMirzaPanel\Domain\Services\User;

use BotMirzaPanel\Domain\Entities\User\User;
use BotMirzaPanel\Domain\ValueObjects\User\UserId;
use BotMirzaPanel\Domain\ValueObjects\User\Username;
use BotMirzaPanel\Domain\ValueObjects\Common\Email;
use BotMirzaPanel\Domain\ValueObjects\Common\PhoneNumber;

/**
 * UserService
 * 
 * Domain service for user-related business logic and operations.
 */
class UserService
{
    public function __construct() {}

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
        $user = User::create(
            UserId::generate(),
            $telegramId,
            $username,
            $firstName,
            $lastName,
            $emailVO,
            $phoneVO
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
        $user->addMetadata('unbanned_at', (new \DateTimeImmutable())->format('c'));
        
        return $user;
    }

    /**
     * Soft delete a user account
     */
    public function deleteUser(User $user): User
    {
        if ($user->getStatus()->isDeleted()) {
            throw new \DomainException('User is already deleted.');
        }
        
        $user->delete();
        $user->addMetadata('deleted_at', (new \DateTimeImmutable())->format('c'));
        
        return $user;
    }

    /**
     * Restore a soft-deleted user account
     */
    public function restoreUser(User $user): User
    {
        if (!$user->getStatus()->isDeleted()) {
            throw new \DomainException('User is not deleted.');
        }
        
        $user->activate();
        $user->removeMetadata('deleted_at');
        $user->addMetadata('restored_at', (new \DateTimeImmutable())->format('c'));
        
        return $user;
    }

    /**
     * Check if user can perform an action based on their status
     */
    public function canUserPerformAction(User $user, string $action): bool
    {
        $status = $user->getStatus();
        
        return match ($action) {
            'login' => $status->canLogin(),
            'create_subscription' => $status->isActive(),
            'use_vpn' => $status->isActive(),
            'update_profile' => $status->canLogin(),
            'change_password' => $status->canLogin(),
            'delete_account' => $status->canLogin(),
            default => false,
        };
    }

    /**
     * Get user activity summary
     */
    public function getUserActivitySummary(User $user): array
    {
        $metadata = $user->getMetadata();
        
        return [
            'status' => $user->getStatus()->toArray(),
            'created_at' => $user->getCreatedAt()->format('c'),
            'updated_at' => $user->getUpdatedAt()->format('c'),
            'last_login' => $metadata['last_login'] ?? null,
            'login_count' => (int) ($metadata['login_count'] ?? 0),
            'subscription_count' => (int) ($metadata['subscription_count'] ?? 0),
            'total_data_used' => $metadata['total_data_used'] ?? '0',
            'is_premium' => (bool) ($metadata['is_premium'] ?? false),
            'referral_count' => (int) ($metadata['referral_count'] ?? 0),
        ];
    }

    /**
     * Update user last login information
     */
    public function recordUserLogin(User $user): User
    {
        $now = new \DateTimeImmutable();
        $loginCount = (int) ($user->getMetadata()['login_count'] ?? 0) + 1;
        
        $user->addMetadata('last_login', $now->format('c'));
        $user->addMetadata('login_count', (string) $loginCount);
        
        return $user;
    }

    /**
     * Check if user is eligible for premium features
     */
    public function isEligibleForPremium(User $user): bool
    {
        $status = $user->getStatus();
        
        if (!$status->isActive()) {
            return false;
        }
        
        // Check if user has active subscriptions
        $metadata = $user->getMetadata();
        $hasActiveSubscription = (bool) ($metadata['has_active_subscription'] ?? false);
        
        return $hasActiveSubscription;
    }

    /**
     * Update user premium status
     */
    public function updatePremiumStatus(User $user, bool $isPremium): User
    {
        $user->addMetadata('is_premium', $isPremium ? 'true' : 'false');
        $user->addMetadata('premium_updated_at', (new \DateTimeImmutable())->format('c'));
        
        return $user;
    }

    /**
     * Get user display information
     */
    public function getUserDisplayInfo(User $user): array
    {
        return [
            'id' => $user->getId()->getValue(),
            'telegram_id' => $user->getTelegramId(),
            'username' => $user->getUsername(),
            'display_name' => $user->getDisplayName(),
            'full_name' => $user->getFullName(),
            'email' => $user->getEmail()?->getValue(),
            'phone' => $user->getPhoneNumber()?->getValue(),
            'status' => $user->getStatus()->getDisplayName(),
            'status_color' => $user->getStatus()->getColor(),
            'status_icon' => $user->getStatus()->getIcon(),
            'is_premium' => $this->isEligibleForPremium($user),
            'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}