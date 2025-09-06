<?php

declare(strict_types=1);

namespace BotMirzaPanel\Application\EventListeners;

use BotMirzaPanel\Domain\Events\SubscriptionCreated;
use BotMirzaPanel\Domain\Events\SubscriptionRenewed;
use BotMirzaPanel\Domain\Events\SubscriptionCancelled;
use BotMirzaPanel\Domain\Events\SubscriptionExpired;
use BotMirzaPanel\Domain\Events\SubscriptionSuspended;
use BotMirzaPanel\Domain\Events\SubscriptionReactivated;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Subscription Event Listeners
 * 
 * Handles subscription-related domain events
 */
class SubscriptionEventListeners
{
    private LoggerInterface $logger;
    
    public function __construct()
    {
        $this->logger = $logger ?? new NullLogger();
    }
    
    /**
     * Handle subscription created event
     */
    public function onSubscriptionCreated(SubscriptionCreated $event): void
    {
        $payload = $event->getPayload();
        
        $this->logger->info('Subscription created', [
            'subscription_id' => $event->getAggregateId(),
            'user_id' => $payload['user_id'],
            'plan_id' => $payload['plan_id'],
            'amount' => $payload['amount'],
            'start_date' => $payload['start_date'],
            'end_date' => $payload['end_date'],
        ]);
        
        // Activate user services
        $this->activateUserServices($payload['user_id'], $payload['plan_id']);
        
        // Send subscription welcome notification
        $this->sendSubscriptionWelcomeNotification($payload['user_id'], $event->getAggregateId(), $payload);
        
        // Update user subscription statistics
        $this->updateUserSubscriptionStats($payload['user_id'], 'created');
        
        // Schedule renewal reminder
        $this->scheduleRenewalReminder($event->getAggregateId(), $payload['end_date']);
        
        // Grant user permissions based on plan
        $this->grantPlanPermissions($payload['user_id'], $payload['plan_id']);
    }
    
    /**
     * Handle subscription renewed event
     */
    public function onSubscriptionRenewed(SubscriptionRenewed $event): void
    {
        $payload = $event->getPayload();
        
        $this->logger->info('Subscription renewed', [
            'subscription_id' => $event->getAggregateId(),
            'user_id' => $payload['user_id'],
            'new_end_date' => $payload['new_end_date'],
            'renewal_amount' => $payload['renewal_amount'],
            'payment_id' => $payload['payment_id'],
        ]);
        
        // Extend user services
        $this->extendUserServices($payload['user_id'], $payload['new_end_date']);
        
        // Send renewal confirmation notification
        $this->sendRenewalConfirmationNotification($payload['user_id'], $event->getAggregateId(), $payload);
        
        // Update user subscription statistics
        $this->updateUserSubscriptionStats($payload['user_id'], 'renewed');
        
        // Schedule next renewal reminder
        $this->scheduleRenewalReminder($event->getAggregateId(), $payload['new_end_date']);
        
        // Process loyalty rewards
        $this->processLoyaltyRewards($payload['user_id']);
    }
    
    /**
     * Handle subscription cancelled event
     */
    public function onSubscriptionCancelled(SubscriptionCancelled $event): void
    {
        $payload = $event->getPayload();
        
        $this->logger->info('Subscription cancelled', [
            'subscription_id' => $event->getAggregateId(),
            'user_id' => $payload['user_id'],
            'reason' => $payload['reason'],
            'cancelled_at' => $payload['cancelled_at'],
            'refund_issued' => $payload['refund_issued'],
        ]);
        
        // Deactivate user services (with grace period)
        $this->scheduleServiceDeactivation($payload['user_id'], $event->getAggregateId());
        
        // Send cancellation confirmation notification
        $this->sendCancellationConfirmationNotification($payload['user_id'], $event->getAggregateId(), $payload);
        
        // Update user subscription statistics
        $this->updateUserSubscriptionStats($payload['user_id'], 'cancelled');
        
        // Cancel scheduled renewal reminders
        $this->cancelRenewalReminders($event->getAggregateId());
        
        // Process exit survey
        $this->sendExitSurvey($payload['user_id'], $payload['reason']);
        
        // Revoke plan-specific permissions
        $this->revokePlanPermissions($payload['user_id']);
    }
    
    /**
     * Handle subscription expired event
     */
    public function onSubscriptionExpired(SubscriptionExpired $event): void
    {
        $payload = $event->getPayload();
        
        $this->logger->warning('Subscription expired', [
            'subscription_id' => $event->getAggregateId(),
            'user_id' => $payload['user_id'],
            'expired_at' => $payload['expired_at'],
        ]);
        
        // Deactivate user services immediately
        $this->deactivateUserServices($payload['user_id']);
        
        // Send expiration notification
        $this->sendExpirationNotification($payload['user_id'], $event->getAggregateId(), $payload);
        
        // Update user subscription statistics
        $this->updateUserSubscriptionStats($payload['user_id'], 'expired');
        
        // Send renewal offer
        $this->sendRenewalOffer($payload['user_id'], $event->getAggregateId());
        
        // Revoke plan-specific permissions
        $this->revokePlanPermissions($payload['user_id']);
        
        // Archive user data if needed
        $this->scheduleDataArchival($payload['user_id']);
    }
    
    /**
     * Handle subscription suspended event
     */
    public function onSubscriptionSuspended(SubscriptionSuspended $event): void
    {
        $payload = $event->getPayload();
        
        $this->logger->warning('Subscription suspended', [
            'subscription_id' => $event->getAggregateId(),
            'user_id' => $payload['user_id'],
            'reason' => $payload['reason'],
            'suspended_at' => $payload['suspended_at'],
            'suspended_until' => $payload['suspended_until'],
        ]);
        
        // Temporarily deactivate user services
        $this->suspendUserServices($payload['user_id']);
        
        // Send suspension notification
        $this->sendSuspensionNotification($payload['user_id'], $event->getAggregateId(), $payload);
        
        // Update user subscription statistics
        $this->updateUserSubscriptionStats($payload['user_id'], 'suspended');
        
        // Schedule automatic reactivation if applicable
        if (!empty($payload['suspended_until'])) {
            $this->scheduleAutoReactivation($event->getAggregateId(), $payload['suspended_until']);
        }
        
        // Temporarily revoke plan-specific permissions
        $this->suspendPlanPermissions($payload['user_id']);
    }
    
    /**
     * Handle subscription reactivated event
     */
    public function onSubscriptionReactivated(SubscriptionReactivated $event): void
    {
        $payload = $event->getPayload();
        
        $this->logger->info('Subscription reactivated', [
            'subscription_id' => $event->getAggregateId(),
            'user_id' => $payload['user_id'],
            'reactivated_at' => $payload['reactivated_at'],
            'payment_id' => $payload['payment_id'],
        ]);
        
        // Reactivate user services
        $this->reactivateUserServices($payload['user_id']);
        
        // Send reactivation confirmation notification
        $this->sendReactivationConfirmationNotification($payload['user_id'], $event->getAggregateId(), $payload);
        
        // Update user subscription statistics
        $this->updateUserSubscriptionStats($payload['user_id'], 'reactivated');
        
        // Cancel any scheduled deactivations
        $this->cancelScheduledDeactivations($event->getAggregateId());
        
        // Restore plan-specific permissions
        $this->restorePlanPermissions($payload['user_id']);
    }
    
    // Private helper methods
    
    private function activateUserServices(string $userId, string $planId): void
    {
        $this->logger->debug('Activating user services', ['user_id' => $userId, 'plan_id' => $planId]);
    }
    
    private function extendUserServices(string $userId, string $newEndDate): void
    {
        $this->logger->debug('Extending user services', ['user_id' => $userId, 'new_end_date' => $newEndDate]);
    }
    
    private function deactivateUserServices(string $userId): void
    {
        $this->logger->debug('Deactivating user services', ['user_id' => $userId]);
    }
    
    private function suspendUserServices(string $userId): void
    {
        $this->logger->debug('Suspending user services', ['user_id' => $userId]);
    }
    
    private function reactivateUserServices(string $userId): void
    {
        $this->logger->debug('Reactivating user services', ['user_id' => $userId]);
    }
    
    private function scheduleServiceDeactivation(string $userId, string $subscriptionId): void
    {
        $this->logger->debug('Scheduling service deactivation', ['user_id' => $userId, 'subscription_id' => $subscriptionId]);
    }
    
    private function scheduleRenewalReminder(string $subscriptionId, string $endDate): void
    {
        $this->logger->debug('Scheduling renewal reminder', ['subscription_id' => $subscriptionId, 'end_date' => $endDate]);
    }
    
    private function cancelRenewalReminders(string $subscriptionId): void
    {
        $this->logger->debug('Cancelling renewal reminders', ['subscription_id' => $subscriptionId]);
    }
    
    private function scheduleAutoReactivation(string $subscriptionId, string $reactivationDate): void
    {
        $this->logger->debug('Scheduling auto reactivation', ['subscription_id' => $subscriptionId, 'reactivation_date' => $reactivationDate]);
    }
    
    private function cancelScheduledDeactivations(string $subscriptionId): void
    {
        $this->logger->debug('Cancelling scheduled deactivations', ['subscription_id' => $subscriptionId]);
    }
    
    private function grantPlanPermissions(string $userId, string $planId): void
    {
        $this->logger->debug('Granting plan permissions', ['user_id' => $userId, 'plan_id' => $planId]);
    }
    
    private function revokePlanPermissions(string $userId): void
    {
        $this->logger->debug('Revoking plan permissions', ['user_id' => $userId]);
    }
    
    private function suspendPlanPermissions(string $userId): void
    {
        $this->logger->debug('Suspending plan permissions', ['user_id' => $userId]);
    }
    
    private function restorePlanPermissions(string $userId): void
    {
        $this->logger->debug('Restoring plan permissions', ['user_id' => $userId]);
    }
    
    private function updateUserSubscriptionStats(string $userId, string $action): void
    {
        $this->logger->debug('Updating user subscription statistics', ['user_id' => $userId, 'action' => $action]);
    }
    
    private function processLoyaltyRewards(string $userId): void
    {
        $this->logger->debug('Processing loyalty rewards', ['user_id' => $userId]);
    }
    
    private function scheduleDataArchival(string $userId): void
    {
        $this->logger->debug('Scheduling data archival', ['user_id' => $userId]);
    }
    
    // Notification methods
    
    private function sendSubscriptionWelcomeNotification(string $userId, string $subscriptionId, array $data): void
    {
        $this->logger->debug('Sending subscription welcome notification', ['user_id' => $userId, 'subscription_id' => $subscriptionId]);
    }
    
    private function sendRenewalConfirmationNotification(string $userId, string $subscriptionId, array $data): void
    {
        $this->logger->debug('Sending renewal confirmation notification', ['user_id' => $userId, 'subscription_id' => $subscriptionId]);
    }
    
    private function sendCancellationConfirmationNotification(string $userId, string $subscriptionId, array $data): void
    {
        $this->logger->debug('Sending cancellation confirmation notification', ['user_id' => $userId, 'subscription_id' => $subscriptionId]);
    }
    
    private function sendExpirationNotification(string $userId, string $subscriptionId, array $data): void
    {
        $this->logger->debug('Sending expiration notification', ['user_id' => $userId, 'subscription_id' => $subscriptionId]);
    }
    
    private function sendSuspensionNotification(string $userId, string $subscriptionId, array $data): void
    {
        $this->logger->debug('Sending suspension notification', ['user_id' => $userId, 'subscription_id' => $subscriptionId]);
    }
    
    private function sendReactivationConfirmationNotification(string $userId, string $subscriptionId, array $data): void
    {
        $this->logger->debug('Sending reactivation confirmation notification', ['user_id' => $userId, 'subscription_id' => $subscriptionId]);
    }
    
    private function sendRenewalOffer(string $userId, string $subscriptionId): void
    {
        $this->logger->debug('Sending renewal offer', ['user_id' => $userId, 'subscription_id' => $subscriptionId]);
    }
    
    private function sendExitSurvey(string $userId, string $reason): void
    {
        $this->logger->debug('Sending exit survey', ['user_id' => $userId, 'reason' => $reason]);
    }
}