<?php

declare(strict_types=1);

namespace BotMirzaPanel\Domain\Events;

use BotMirzaPanel\Domain\ValueObjects\Email;
use BotMirzaPanel\Domain\ValueObjects\Money;

/**
 * User created event
 */
class UserCreated extends AbstractDomainEvent
{
    public function getEventName(): string
    {
        return 'user.created';
    }

    public static function create(
        string $userId,
        ?string $username,
        string $firstName,
        string $lastName,
        ?Email $email = null,
        ?string $phoneNumber = null,
        ?string $referredBy = null
    ): self {
        return new self($userId, [
            'username' => $username,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email?->getValue(),
            'phone_number' => $phoneNumber,
            'referred_by' => $referredBy,
        ]);
    }
}

/**
 * User profile updated event
 */
class UserProfileUpdated extends AbstractDomainEvent
{
    public function getEventName(): string
    {
        return 'user.profile_updated';
    }

    public static function create(
        string $userId,
        array $oldData,
        array $newData
    ): self {
        return new self($userId, [
            'old_data' => $oldData,
            'new_data' => $newData,
            'changed_fields' => array_keys(array_diff_assoc($newData, $oldData)),
        ]);
    }
}

/**
 * User balance updated event
 */
class UserBalanceUpdated extends AbstractDomainEvent
{
    public function getEventName(): string
    {
        return 'user.balance_updated';
    }

    public static function create(
        string $userId,
        Money $oldBalance,
        Money $newBalance,
        string $reason,
        ?string $transactionId = null
    ): self {
        return new self($userId, [
            'old_balance' => $oldBalance->toArray(),
            'new_balance' => $newBalance->toArray(),
            'difference' => $newBalance->subtract($oldBalance)->toArray(),
            'reason' => $reason,
            'transaction_id' => $transactionId,
        ]);
    }
}

/**
 * User status changed event
 */
class UserStatusChanged extends AbstractDomainEvent
{
    public function getEventName(): string
    {
        return 'user.status_changed';
    }

    public static function create(
        string $userId,
        string $oldStatus,
        string $newStatus,
        ?string $reason = null,
        ?string $changedBy = null
    ): self {
        return new self($userId, [
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'reason' => $reason,
            'changed_by' => $changedBy,
        ]);
    }
}

/**
 * User role assigned event
 */
class UserRoleAssigned extends AbstractDomainEvent
{
    public function getEventName(): string
    {
        return 'user.role_assigned';
    }

    public static function create(
        string $userId,
        string $role,
        ?string $assignedBy = null
    ): self {
        return new self($userId, [
            'role' => $role,
            'assigned_by' => $assignedBy,
        ]);
    }
}

/**
 * User role removed event
 */
class UserRoleRemoved extends AbstractDomainEvent
{
    public function getEventName(): string
    {
        return 'user.role_removed';
    }

    public static function create(
        string $userId,
        string $role,
        ?string $removedBy = null
    ): self {
        return new self($userId, [
            'role' => $role,
            'removed_by' => $removedBy,
        ]);
    }
}

/**
 * User phone verified event
 */
class UserPhoneVerified extends AbstractDomainEvent
{
    public function getEventName(): string
    {
        return 'user.phone_verified';
    }

    public static function create(
        string $userId,
        string $phoneNumber
    ): self {
        return new self($userId, [
            'phone_number' => $phoneNumber,
        ]);
    }
}

/**
 * User email verified event
 */
class UserEmailVerified extends AbstractDomainEvent
{
    public function getEventName(): string
    {
        return 'user.email_verified';
    }

    public static function create(
        string $userId,
        Email $email
    ): self {
        return new self($userId, [
            'email' => $email->getValue(),
        ]);
    }
}

/**
 * User referred event
 */
class UserReferred extends AbstractDomainEvent
{
    public function getEventName(): string
    {
        return 'user.referred';
    }

    public static function create(
        string $referrerId,
        string $referredUserId,
        string $referralCode
    ): self {
        return new self($referrerId, [
            'referred_user_id' => $referredUserId,
            'referral_code' => $referralCode,
        ]);
    }
}

/**
 * User login event
 */
class UserLoggedIn extends AbstractDomainEvent
{
    public function getEventName(): string
    {
        return 'user.logged_in';
    }

    public static function create(
        string $userId,
        string $ipAddress,
        string $userAgent,
        ?string $location = null
    ): self {
        return new self($userId, [
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'location' => $location,
        ]);
    }
}

/**
 * User deleted event
 */
class UserDeleted extends AbstractDomainEvent
{
    public function getEventName(): string
    {
        return 'user.deleted';
    }

    public static function create(
        string $userId,
        ?string $deletedBy = null,
        ?string $reason = null
    ): self {
        return new self($userId, [
            'deleted_by' => $deletedBy,
            'reason' => $reason,
        ]);
    }
}