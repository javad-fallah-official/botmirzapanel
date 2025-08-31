<?php

declare(strict_types=1);

namespace BotMirzaPanel\Domain\Services\User;

use BotMirzaPanel\Domain\ValueObjects\User\UserId;
use BotMirzaPanel\Domain\Exceptions\SecurityException;

/**
 * UserSecurityService
 * 
 * Domain service for user security operations.
 */
class UserSecurityService
{
    /**
     * Hash a password securely
     */
    public function hashPassword(string $password): string
    {
        if (strlen($password) < 8) {
            throw new SecurityException('Password must be at least 8 characters long');
        }
        
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iterations
            'threads' => 3,         // 3 threads
        ]);
    }
    
    /**
     * Verify a password against its hash
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
    
    /**
     * Generate a secure random token
     */
    public function generateSecureToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Generate a referral code
     */
    public function generateReferralCode(UserId $userId): string
    {
        $userIdHash = substr(hash('sha256', $userId->getValue()), 0, 8);
        $randomPart = substr($this->generateSecureToken(8), 0, 8);
        
        return strtoupper($userIdHash . $randomPart);
    }
    
    /**
     * Validate password strength
     */
    public function validatePasswordStrength(string $password): bool
    {
        // At least 8 characters
        if (strlen($password) < 8) {
            return false;
        }
        
        // Contains at least one uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }
        
        // Contains at least one lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }
        
        // Contains at least one digit
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Sanitize user input to prevent XSS
     */
    public function sanitizeInput(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Generate API key for user
     */
    public function generateApiKey(UserId $userId): string
    {
        $prefix = 'bmp_'; // BotMirzaPanel prefix
        $userHash = substr(hash('sha256', $userId->getValue()), 0, 8);
        $randomPart = $this->generateSecureToken(16);
        
        return $prefix . $userHash . '_' . $randomPart;
    }
    
    /**
     * Validate API key format
     */
    public function validateApiKeyFormat(string $apiKey): bool
    {
        return preg_match('/^bmp_[a-f0-9]{8}_[a-f0-9]{32}$/', $apiKey) === 1;
    }
}