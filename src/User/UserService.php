<?php

declare(strict_types=1);

namespace BotMirzaPanel\User;

use BotMirzaPanel\Config\ConfigManager;
use BotMirzaPanel\Database\DatabaseManager;

/**
 * User service that manages all user-related operations
 * Handles user management, authentication, and business logic
 */
class UserService
{
    private ConfigManager $config;
    private DatabaseManager $db;
    private array $rateLimitCache = [];

    public function __construct(ConfigManager $config, DatabaseManager $db)
    {
        $this->config = $config;
        $this->db = $db;
    }

    /**
     * Get or create user
     */
    public function getOrCreateUser(int $userId, array $userInfo, string $referralCode = null): array
    {
        $user = $this->getUserById($userId);
        
        if (!$user) {
            $user = $this->createUser($userId, $userInfo, $referralCode);
        } else {
            // Update user info if changed
            $this->updateUserInfo($userId, $userInfo);
        }
        
        return $user;
    }

    /**
     * Get user by ID
     */
    public function getUserById(int $userId): ?array
    {
        return $this->db->findOne('users', ['id' => $userId]);
    }

    /**
     * Create new user
     */
    public function createUser(int $userId, array $userInfo, string $referralCode = null): array
    {
        $userData = [
            'id' => $userId,
            'username' => $userInfo['username'] ?? null,
            'first_name' => $userInfo['first_name'] ?? '',
            'last_name' => $userInfo['last_name'] ?? '',
            'language_code' => $userInfo['language_code'] ?? 'en',
            'is_bot' => $userInfo['is_bot'] ?? false,
            'balance' => 0,
            'status' => 'active',
            'step' => 'home',
            'phone_verified' => false,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Handle referral
        if ($referralCode) {
            $referrer = $this->getUserByReferralCode($referralCode);
            if ($referrer) {
                $userData['referred_by'] = $referrer['id'];
                $this->processReferralReward($referrer['id'], $userId);
            }
        }
        
        $this->db->insert('users', $userData);
        
        // Generate referral code for new user
        $this->generateReferralCode($userId);
        
        return $this->getUserById($userId);
    }

    /**
     * Update user information
     */
    public function updateUserInfo(int $userId, array $userInfo): bool
    {
        $updateData = [
            'username' => $userInfo['username'] ?? null,
            'first_name' => $userInfo['first_name'] ?? '',
            'last_name' => $userInfo['last_name'] ?? '',
            'language_code' => $userInfo['language_code'] ?? 'en',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->update('users', $updateData, ['id' => $userId]);
    }

    /**
     * Check if user is blocked
     */
    public function isUserBlocked(int $userId): bool
    {
        $user = $this->getUserById($userId);
        return $user && $user['status'] === 'blocked';
    }

    /**
     * Block user
     */
    public function blockUser(int $userId, string $reason = ''): bool
    {
        return $this->db->update('users', [
            'status' => 'blocked',
            'block_reason' => $reason,
            'blocked_at' => date('Y-m-d H:i:s')
        ], ['id' => $userId]);
    }

    /**
     * Unblock user
     */
    public function unblockUser(int $userId): bool
    {
        return $this->db->update('users', [
            'status' => 'active',
            'block_reason' => null,
            'blocked_at' => null
        ], ['id' => $userId]);
    }

    /**
     * Check if user is rate limited
     */
    public function isRateLimited(int $userId): bool
    {
        $rateLimit = $this->config->get('bot.rate_limit', 10); // messages per minute
        $timeWindow = 60; // seconds
        
        $cacheKey = "rate_limit_{$userId}";
        $now = time();
        
        if (!isset($this->rateLimitCache[$cacheKey])) {
            $this->rateLimitCache[$cacheKey] = [];
        }
        
        // Clean old entries
        $this->rateLimitCache[$cacheKey] = array_filter(
            $this->rateLimitCache[$cacheKey],
            fn($timestamp) => $now - $timestamp < $timeWindow
        );
        
        // Check if limit exceeded
        if (count($this->rateLimitCache[$cacheKey]) >= $rateLimit) {
            return true;
        }
        
        // Add current request
        $this->rateLimitCache[$cacheKey][] = $now;
        
        return false;
    }

    /**
     * Get user step/state
     */
    public function getUserStep(int $userId): string
    {
        $user = $this->getUserById($userId);
        return $user['step'] ?? 'home';
    }

    /**
     * Set user step/state
     */
    public function setUserStep(int $userId, string $step): bool
    {
        return $this->db->update('users', ['step' => $step], ['id' => $userId]);
    }

    /**
     * Get user balance
     */
    public function getUserBalance(int $userId): float
    {
        $user = $this->getUserById($userId);
        return (float)($user['balance'] ?? 0);
    }

    /**
     * Add balance to user
     */
    public function addBalance(int $userId, float $amount, string $description = ''): bool
    {
        $this->db->beginTransaction();
        
        try {
            // Update user balance
            $this->db->query(
                "UPDATE users SET balance = balance + ? WHERE id = ?",
                [$amount, $userId]
            );
            
            // Log transaction
            $this->logTransaction($userId, 'credit', $amount, $description);
            
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * Deduct balance from user
     */
    public function deductBalance(int $userId, float $amount, string $description = ''): bool
    {
        $currentBalance = $this->getUserBalance($userId);
        
        if ($currentBalance < $amount) {
            return false; // Insufficient balance
        }
        
        $this->db->beginTransaction();
        
        try {
            // Update user balance
            $this->db->query(
                "UPDATE users SET balance = balance - ? WHERE id = ?",
                [$amount, $userId]
            );
            
            // Log transaction
            $this->logTransaction($userId, 'debit', $amount, $description);
            
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * Update user phone number
     */
    public function updateUserPhone(int $userId, string $phoneNumber): bool
    {
        return $this->db->update('users', [
            'phone_number' => $phoneNumber,
            'phone_verified' => true,
            'phone_verified_at' => date('Y-m-d H:i:s')
        ], ['id' => $userId]);
    }

    /**
     * Validate phone number format
     */
    public function validatePhoneNumber(string $phoneNumber): bool
    {
        // Remove all non-digit characters
        $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Check if it's a valid length (8-15 digits)
        return strlen($cleaned) >= 8 && strlen($cleaned) <= 15;
    }

    /**
     * Get user services
     */
    public function getUserServices(int $userId): array
    {
        return $this->db->findAll('user_services', 
            ['user_id' => $userId], 
            ['created_at' => 'DESC']
        );
    }

    /**
     * Get user by referral code
     */
    public function getUserByReferralCode(string $referralCode): ?array
    {
        return $this->db->findOne('users', ['referral_code' => $referralCode]);
    }

    /**
     * Generate referral code for user
     */
    public function generateReferralCode(int $userId): string
    {
        $referralCode = 'REF_' . $userId . '_' . substr(md5($userId . time()), 0, 6);
        
        $this->db->update('users', ['referral_code' => $referralCode], ['id' => $userId]);
        
        return $referralCode;
    }

    /**
     * Get user info for display
     */
    public function getUserInfo(int $userId): ?array
    {
        $user = $this->getUserById($userId);
        
        if (!$user) {
            return null;
        }
        
        // Get additional stats
        $services = $this->getUserServices($userId);
        $transactions = $this->getUserTransactions($userId, 10);
        
        return [
            'user' => $user,
            'services_count' => count($services),
            'recent_transactions' => $transactions,
            'total_spent' => $this->getUserTotalSpent($userId),
            'referrals_count' => $this->getUserReferralsCount($userId)
        ];
    }

    /**
     * Get system statistics
     */
    public function getSystemStats(): array
    {
        $stats = [];
        
        // User statistics
        $stats['users'] = [
            'total' => $this->db->count('users'),
            'active' => $this->db->count('users', ['status' => 'active']),
            'blocked' => $this->db->count('users', ['status' => 'blocked']),
            'new_today' => $this->db->count('users', [
                'created_at >=' => date('Y-m-d 00:00:00')
            ])
        ];
        
        // Service statistics
        $stats['services'] = [
            'total' => $this->db->count('user_services'),
            'active' => $this->db->count('user_services', ['status' => 'active']),
            'expired' => $this->db->count('user_services', ['status' => 'expired'])
        ];
        
        // Financial statistics
        $totalRevenue = $this->db->query(
            "SELECT SUM(amount) as total FROM transactions WHERE type = 'payment' AND status = 'completed'"
        )[0]['total'] ?? 0;
        
        $stats['financial'] = [
            'total_revenue' => $totalRevenue,
            'total_balance' => $this->db->query("SELECT SUM(balance) as total FROM users")[0]['total'] ?? 0
        ];
        
        return $stats;
    }

    /**
     * Queue broadcast message
     */
    public function queueBroadcastMessage(string $message): bool
    {
        return $this->db->insert('broadcast_queue', [
            'message' => $message,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]) > 0;
    }

    /**
     * Set processing value for user (temporary data)
     */
    public function setProcessingValue(int $userId, string $value): bool
    {
        return $this->db->update('users', ['processing_value' => $value], ['id' => $userId]);
    }

    /**
     * Get processing value for user
     */
    public function getProcessingValue(int $userId): ?string
    {
        $user = $this->getUserById($userId);
        return $user['processing_value'] ?? null;
    }

    /**
     * Clear processing value for user
     */
    public function clearProcessingValue(int $userId): bool
    {
        return $this->db->update('users', ['processing_value' => null], ['id' => $userId]);
    }

    /**
     * Process referral reward
     */
    private function processReferralReward(int $referrerId, int $newUserId): void
    {
        $rewardAmount = $this->config->get('referral.reward_amount', 0);
        
        if ($rewardAmount > 0) {
            $this->addBalance($referrerId, $rewardAmount, "Referral reward for user {$newUserId}");
        }
    }

    /**
     * Log user transaction
     */
    private function logTransaction(int $userId, string $type, float $amount, string $description): void
    {
        $this->db->insert('transactions', [
            'user_id' => $userId,
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get user transactions
     */
    private function getUserTransactions(int $userId, int $limit = 50): array
    {
        return $this->db->findAll('transactions', 
            ['user_id' => $userId], 
            ['created_at' => 'DESC'], 
            $limit
        );
    }

    /**
     * Get user total spent amount
     */
    private function getUserTotalSpent(int $userId): float
    {
        $result = $this->db->query(
            "SELECT SUM(amount) as total FROM transactions WHERE user_id = ? AND type = 'debit'",
            [$userId]
        );
        
        return (float)($result[0]['total'] ?? 0);
    }

    /**
     * Get user referrals count
     */
    private function getUserReferralsCount(int $userId): int
    {
        return $this->db->count('users', ['referred_by' => $userId]);
    }

    /**
     * Get all active users (normalized for cron usage)
     */
    public function getAllActiveUsers(): array
    {
        $sql = "SELECT id AS user_id, username FROM users WHERE status = 'active'";
        return $this->db->fetchAll($sql);
    }

    /**
     * Update user statistics payload
     */
    public function updateUserStats(int $userId, array $stats): bool
    {
        // Store the last fetched stats JSON and bump updated_at
        $affected = $this->db->update('users', [
            'last_stats' => json_encode($stats),
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $userId]);

        return $affected >= 0; // consider 0 rows affected as not an error
    }

    /**
     * Return list of admin users to notify (from config)
     */
    public function getAdminUsers(): array
    {
        $admins = $this->config->get('admin.ids', []);
        if (!is_array($admins) || empty($admins)) {
            $single = $this->config->get('admin.id');
            $admins = $single ? [$single] : [];
        }
        // Normalize payload to expected shape
        return array_map(fn($id) => ['user_id' => (int)$id], $admins);
    }
}