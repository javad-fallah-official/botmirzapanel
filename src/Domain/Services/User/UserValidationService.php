<?php

declare(strict_types=1);

namespace BotMirzaPanel\Domain\Services\User;

use BotMirzaPanel\Domain\ValueObjects\Common\Email;
use BotMirzaPanel\Domain\ValueObjects\Common\PhoneNumber;
use BotMirzaPanel\Domain\Exceptions\ValidationException;

/**
 * UserValidationService
 * 
 * Domain service for user validation logic.
 */
class UserValidationService
{
    /**
     * Validate Telegram ID format
     */
    public function validateTelegramId(string $telegramId): void
    {
        if (empty($telegramId)) {
            throw new ValidationException('Telegram ID cannot be empty');
        }
        
        if (!is_numeric($telegramId)) {
            throw new ValidationException('Telegram ID must be numeric');
        }
        
        if (strlen($telegramId) < 5 || strlen($telegramId) > 15) {
            throw new ValidationException('Telegram ID must be between 5 and 15 digits');
        }
    }
    
    /**
     * Validate email uniqueness (placeholder - should be implemented with repository)
     */
    public function validateEmailUniqueness(Email $email): void
    {
        // TODO: Implement with UserRepository
        // This is a placeholder for email uniqueness validation
    }
    
    /**
     * Validate phone number uniqueness (placeholder - should be implemented with repository)
     */
    public function validatePhoneUniqueness(PhoneNumber $phoneNumber): void
    {
        // TODO: Implement with UserRepository
        // This is a placeholder for phone number uniqueness validation
    }
    
    /**
     * Validate username format and uniqueness
     */
    public function validateUsername(?string $username): void
    {
        if ($username === null) {
            return;
        }
        
        if (strlen($username) < 3 || strlen($username) > 50) {
            throw new ValidationException('Username must be between 3 and 50 characters');
        }
        
        if (!preg_match('/^[a-zA-Z0-9._@-]+$/', $username)) {
            throw new ValidationException('Username can only contain alphanumeric characters, periods, underscores, at signs, and hyphens');
        }
        
        // TODO: Implement uniqueness check with UserRepository
    }
    
    /**
     * Validate user data consistency
     */
    public function validateUserData(array $userData): void
    {
        // Validate required fields
        if (empty($userData['telegramId'])) {
            throw new ValidationException('Telegram ID is required');
        }
        
        // Validate optional fields if provided
        if (isset($userData['email']) && !empty($userData['email'])) {
            Email::fromString($userData['email']); // This will throw if invalid
        }
        
        if (isset($userData['phoneNumber']) && !empty($userData['phoneNumber'])) {
            PhoneNumber::fromString($userData['phoneNumber']); // This will throw if invalid
        }
    }
}