<?php

declare(strict_types=1);

namespace BotMirzaPanel\Application\EventListeners;

use BotMirzaPanel\Domain\Events\UserCreated;
use BotMirzaPanel\Domain\Events\UserProfileUpdated;
use BotMirzaPanel\Domain\Events\UserBlocked;
use BotMirzaPanel\Domain\Events\UserUnblocked;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * User Event Listeners
 * 
 * Handles user-related domain events
 */
class UserEventListeners
{
    private LoggerInterface $logger;
    
    public function __construct()
    {
        $this->logger = $logger ?? new NullLogger();
    }
    
    /**
     * Handle user created event
     */
    public function onUserCreated(UserCreated $event): void
    {
        $payload = $event->getPayload();
        
        $this->logger->info('User created', [
            'user_id' => $event->getAggregateId(),
            'username' => $payload['username'] ?? null,
            'first_name' => $payload['first_name'] ?? null,
            'last_name' => $payload['last_name'] ?? null,
            'referred_by' => $payload['referred_by'] ?? null,
        ]);
        
        // Send welcome notification
        $this->sendWelcomeNotification($event->getAggregateId(), $payload);
        
        // Process referral if exists
        if (!empty($payload['referred_by'])) {
            $this->processReferral($event->getAggregateId(), $payload['referred_by']);
        }
        
        // Initialize user statistics
        $this->initializeUserStatistics($event->getAggregateId());
    }
    
    /**
     * Handle user profile updated event
     */
    public function onUserProfileUpdated(UserProfileUpdated $event): void
    {
        $payload = $event->getPayload();
        
        $this->logger->info('User profile updated', [
            'user_id' => $event->getAggregateId(),
            'updated_fields' => array_keys($payload),
        ]);
        
        // Update search index if needed
        $this->updateUserSearchIndex($event->getAggregateId(), $payload);
    }
    
    /**
     * Handle user blocked event
     */
    public function onUserBlocked(UserBlocked $event): void
    {
        $payload = $event->getPayload();
        
        $this->logger->warning('User blocked', [
            'user_id' => $event->getAggregateId(),
            'reason' => $payload['reason'] ?? 'No reason provided',
            'blocked_by' => $payload['blocked_by'] ?? 'System',
        ]);
        
        // Cancel active sessions
        $this->cancelUserSessions($event->getAggregateId());
        
        // Suspend active subscriptions
        $this->suspendUserSubscriptions($event->getAggregateId());
        
        // Send notification to user
        $this->sendBlockNotification($event->getAggregateId(), $payload);
    }
    
    /**
     * Handle user unblocked event
     */
    public function onUserUnblocked(UserUnblocked $event): void
    {
        $payload = $event->getPayload();
        
        $this->logger->info('User unblocked', [
            'user_id' => $event->getAggregateId(),
            'unblocked_by' => $payload['unblocked_by'] ?? 'System',
        ]);
        
        // Reactivate suspended subscriptions if applicable
        $this->reactivateUserSubscriptions($event->getAggregateId());
        
        // Send notification to user
        $this->sendUnblockNotification($event->getAggregateId());
    }
    
    /**
     * Send welcome notification to new user
     */
    private function sendWelcomeNotification(string $userId, array $userData): void
    {
        // Implementation would depend on notification service
        $this->logger->debug('Sending welcome notification', ['user_id' => $userId]);
    }
    
    /**
     * Process referral bonus
     */
    private function processReferral(string $newUserId, string $referrerId): void
    {
        // Implementation would depend on referral service
        $this->logger->debug('Processing referral', [
            'new_user_id' => $newUserId,
            'referrer_id' => $referrerId,
        ]);
    }
    
    /**
     * Initialize user statistics
     */
    private function initializeUserStatistics(string $userId): void
    {
        // Implementation would depend on statistics service
        $this->logger->debug('Initializing user statistics', ['user_id' => $userId]);
    }
    
    /**
     * Update user search index
     */
    private function updateUserSearchIndex(string $userId, array $updatedData): void
    {
        // Implementation would depend on search service
        $this->logger->debug('Updating user search index', ['user_id' => $userId]);
    }
    
    /**
     * Cancel all active user sessions
     */
    private function cancelUserSessions(string $userId): void
    {
        // Implementation would depend on session service
        $this->logger->debug('Cancelling user sessions', ['user_id' => $userId]);
    }
    
    /**
     * Suspend user subscriptions
     */
    private function suspendUserSubscriptions(string $userId): void
    {
        // Implementation would depend on subscription service
        $this->logger->debug('Suspending user subscriptions', ['user_id' => $userId]);
    }
    
    /**
     * Reactivate user subscriptions
     */
    private function reactivateUserSubscriptions(string $userId): void
    {
        // Implementation would depend on subscription service
        $this->logger->debug('Reactivating user subscriptions', ['user_id' => $userId]);
    }
    
    /**
     * Send block notification
     */
    private function sendBlockNotification(string $userId, array $blockData): void
    {
        // Implementation would depend on notification service
        $this->logger->debug('Sending block notification', ['user_id' => $userId]);
    }
    
    /**
     * Send unblock notification
     */
    private function sendUnblockNotification(string $userId): void
    {
        // Implementation would depend on notification service
        $this->logger->debug('Sending unblock notification', ['user_id' => $userId]);
    }
}