<?php

declare(strict_types=1);

namespace BotMirzaPanel\Infrastructure\Database;

use BotMirzaPanel\Domain\Entities\User\User;
use BotMirzaPanel\Domain\ValueObjects\User\UserId;
use BotMirzaPanel\Domain\ValueObjects\User\UserName as Username;
use BotMirzaPanel\Domain\ValueObjects\User\UserStatus;
use BotMirzaPanel\Domain\ValueObjects\Common\Email;
use BotMirzaPanel\Domain\ValueObjects\Common\PhoneNumber;

/**
 * Maps between User domain entity and database representation
 */
class UserEntityMapper
{
    /**
     * Convert database row to User entity
     */
    public function toDomainEntity(array $row): User
    {
        $userId = UserId::fromString($row['id']);
        $username = $row['username'] ? Username::fromString($row['username']) : null;
        $email = $row['email'] ? Email::fromString($row['email']) : null;
        $phoneNumber = $row['phone_number'] ? PhoneNumber::fromString($row['phone_number']) : null;
        $status = UserStatus::fromString($row['status'] ?? 'active');
        
        $user = User::create(
            $userId,
            $username,
            $email,
            $phoneNumber,
            $row['telegram_chat_id'] ?? null,
            $row['first_name'] ?? null,
            $row['last_name'] ?? null
        );
        
        // Set additional properties
        if (isset($row['password_hash'])) {
            $user->setPasswordHash($row['password_hash']);
        }
        
        if (isset($row['referral_code'])) {
            $user->setReferralCode($row['referral_code']);
        }
        
        if (isset($row['referred_by'])) {
            $user->setReferredBy(UserId::fromString($row['referred_by']));
        }
        
        if (isset($row['balance'])) {
            $user->setBalance((float) $row['balance']);
        }
        
        if (isset($row['is_premium'])) {
            $user->setIsPremium((bool) $row['is_premium']);
        }
        
        $user->setStatus($status);
        
        if (isset($row['created_at'])) {
            $user->setCreatedAt(new \DateTimeImmutable($row['created_at']));
        }
        
        if (isset($row['updated_at'])) {
            $user->setUpdatedAt(new \DateTimeImmutable($row['updated_at']));
        }
        
        return $user;
    }
    
    /**
     * Convert User entity to database array
     */
    public function toDatabaseArray(User $user): array
    {
        $data = [
            'id' => $user->getId()->getValue(),
            'telegram_chat_id' => $user->getTelegramChatId(),
            'username' => $user->getUsername()?->getValue(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'email' => $user->getEmail()?->getValue(),
            'phone_number' => $user->getPhoneNumber()?->getValue(),
            'password_hash' => $user->getPasswordHash(),
            'referral_code' => $user->getReferralCode(),
            'referred_by' => $user->getReferredBy()?->getValue(),
            'balance' => $user->getBalance(),
            'is_premium' => $user->getIsPremium(),
            'status' => $user->getStatus()->getValue(),
            'created_at' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at' => $user->getUpdatedAt()?->format('Y-m-d H:i:s')
        ];
        
        // Remove null values
        return array_filter($data, fn($value) => $value !== null);
    }
    
    /**
     * Convert array of database rows to array of User entities
     */
    public function toDomainEntities(array $rows): array
    {
        return array_map([$this, 'toDomainEntity'], $rows);
    }
}