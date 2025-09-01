<?php

declare(strict_types=1);

namespace BotMirzaPanel\Application\EventListeners;

use BotMirzaPanel\Domain\Events\PaymentCreated;
use BotMirzaPanel\Domain\Events\PaymentCompleted;
use BotMirzaPanel\Domain\Events\PaymentFailed;
use BotMirzaPanel\Domain\Events\PaymentRefunded;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Payment Event Listeners
 * 
 * Handles payment-related domain events
 */
class PaymentEventListeners
{
    private LoggerInterface $logger;
    
    public function __construct(?LoggerInterface $logger = null): void
    {
        $this->logger = $logger ?? new NullLogger();
    }
    
    /**
     * Handle payment created event
     */
    public function onPaymentCreated(PaymentCreated $event): void
    {
        $payload = $event->getPayload();
        
        $this->logger->info('Payment created', [
            'payment_id' => $event->getAggregateId(),
            'user_id' => $payload['user_id'],
            'order_id' => $payload['order_id'],
            'amount' => $payload['amount'],
            'gateway' => $payload['gateway'],
        ]);
        
        // Send payment confirmation email
        $this->sendPaymentCreatedNotification($payload['user_id'], $event->getAggregateId(), $payload);
        
        // Update user payment statistics
        $this->updateUserPaymentStats($payload['user_id'], 'created');
        
        // Log for fraud detection
        $this->logForFraudDetection($event->getAggregateId(), $payload);
    }
    
    /**
     * Handle payment completed event
     */
    public function onPaymentCompleted(PaymentCompleted $event): void
    {
        $payload = $event->getPayload();
        
        $this->logger->info('Payment completed', [
            'payment_id' => $event->getAggregateId(),
            'user_id' => $payload['user_id'],
            'amount' => $payload['amount'],
            'transaction_id' => $payload['transaction_id'],
            'gateway' => $payload['gateway'],
        ]);
        
        // Process subscription activation/renewal
        $this->processSubscriptionUpdate($payload['user_id'], $event->getAggregateId(), $payload);
        
        // Send payment success notification
        $this->sendPaymentSuccessNotification($payload['user_id'], $event->getAggregateId(), $payload);
        
        // Update user payment statistics
        $this->updateUserPaymentStats($payload['user_id'], 'completed');
        
        // Process referral bonuses if applicable
        $this->processReferralBonuses($payload['user_id'], $payload['amount']);
        
        // Generate invoice
        $this->generateInvoice($event->getAggregateId(), $payload);
    }
    
    /**
     * Handle payment failed event
     */
    public function onPaymentFailed(PaymentFailed $event): void
    {
        $payload = $event->getPayload();
        
        $this->logger->warning('Payment failed', [
            'payment_id' => $event->getAggregateId(),
            'user_id' => $payload['user_id'],
            'amount' => $payload['amount'],
            'gateway' => $payload['gateway'],
            'reason' => $payload['reason'],
        ]);
        
        // Send payment failure notification
        $this->sendPaymentFailureNotification($payload['user_id'], $event->getAggregateId(), $payload);
        
        // Update user payment statistics
        $this->updateUserPaymentStats($payload['user_id'], 'failed');
        
        // Log for fraud detection and analysis
        $this->logPaymentFailure($event->getAggregateId(), $payload);
        
        // Suggest alternative payment methods
        $this->suggestAlternativePaymentMethods($payload['user_id'], $payload['gateway']);
    }
    
    /**
     * Handle payment refunded event
     */
    public function onPaymentRefunded(PaymentRefunded $event): void
    {
        $payload = $event->getPayload();
        
        $this->logger->info('Payment refunded', [
            'payment_id' => $event->getAggregateId(),
            'user_id' => $payload['user_id'],
            'refund_amount' => $payload['refund_amount'],
            'reason' => $payload['reason'],
        ]);
        
        // Process subscription cancellation/downgrade
        $this->processSubscriptionRefund($payload['user_id'], $event->getAggregateId(), $payload);
        
        // Send refund notification
        $this->sendRefundNotification($payload['user_id'], $event->getAggregateId(), $payload);
        
        // Update user payment statistics
        $this->updateUserPaymentStats($payload['user_id'], 'refunded');
        
        // Reverse referral bonuses if applicable
        $this->reverseReferralBonuses($payload['user_id'], $payload['refund_amount']);
    }
    
    /**
     * Send payment created notification
     */
    private function sendPaymentCreatedNotification(string $userId, string $paymentId, array $paymentData): void
    {
        $this->logger->debug('Sending payment created notification', [
            'user_id' => $userId,
            'payment_id' => $paymentId,
        ]);
    }
    
    /**
     * Send payment success notification
     */
    private function sendPaymentSuccessNotification(string $userId, string $paymentId, array $paymentData): void
    {
        $this->logger->debug('Sending payment success notification', [
            'user_id' => $userId,
            'payment_id' => $paymentId,
        ]);
    }
    
    /**
     * Send payment failure notification
     */
    private function sendPaymentFailureNotification(string $userId, string $paymentId, array $paymentData): void
    {
        $this->logger->debug('Sending payment failure notification', [
            'user_id' => $userId,
            'payment_id' => $paymentId,
        ]);
    }
    
    /**
     * Send refund notification
     */
    private function sendRefundNotification(string $userId, string $paymentId, array $refundData): void
    {
        $this->logger->debug('Sending refund notification', [
            'user_id' => $userId,
            'payment_id' => $paymentId,
        ]);
    }
    
    /**
     * Process subscription update after payment
     */
    private function processSubscriptionUpdate(string $userId, string $paymentId, array $paymentData): void
    {
        $this->logger->debug('Processing subscription update', [
            'user_id' => $userId,
            'payment_id' => $paymentId,
        ]);
    }
    
    /**
     * Process subscription refund
     */
    private function processSubscriptionRefund(string $userId, string $paymentId, array $refundData): void
    {
        $this->logger->debug('Processing subscription refund', [
            'user_id' => $userId,
            'payment_id' => $paymentId,
        ]);
    }
    
    /**
     * Update user payment statistics
     */
    private function updateUserPaymentStats(string $userId, string $action): void
    {
        $this->logger->debug('Updating user payment statistics', [
            'user_id' => $userId,
            'action' => $action,
        ]);
    }
    
    /**
     * Process referral bonuses
     */
    private function processReferralBonuses(string $userId, array $amount): void
    {
        $this->logger->debug('Processing referral bonuses', [
            'user_id' => $userId,
            'amount' => $amount,
        ]);
    }
    
    /**
     * Reverse referral bonuses
     */
    private function reverseReferralBonuses(string $userId, array $amount): void
    {
        $this->logger->debug('Reversing referral bonuses', [
            'user_id' => $userId,
            'amount' => $amount,
        ]);
    }
    
    /**
     * Generate invoice
     */
    private function generateInvoice(string $paymentId, array $paymentData): void
    {
        $this->logger->debug('Generating invoice', ['payment_id' => $paymentId]);
    }
    
    /**
     * Log for fraud detection
     */
    private function logForFraudDetection(string $paymentId, array $paymentData): void
    {
        $this->logger->debug('Logging for fraud detection', ['payment_id' => $paymentId]);
    }
    
    /**
     * Log payment failure
     */
    private function logPaymentFailure(string $paymentId, array $failureData): void
    {
        $this->logger->debug('Logging payment failure', ['payment_id' => $paymentId]);
    }
    
    /**
     * Suggest alternative payment methods
     */
    private function suggestAlternativePaymentMethods(string $userId, string $failedGateway): void
    {
        $this->logger->debug('Suggesting alternative payment methods', [
            'user_id' => $userId,
            'failed_gateway' => $failedGateway,
        ]);
    }
}