<?php

declare(strict_types=1);

namespace BotMirzaPanel\Infrastructure\External\Payment;

use BotMirzaPanel\Domain\ValueObjects\Money;

/**
 * Payment Gateway Interface
 * 
 * Defines the contract for all payment gateway implementations
 */
interface PaymentGatewayInterface
{
    /**
     * Get the gateway name
     */
    public function getName(): string;
    
    /**
     * Check if the gateway is enabled
     */
    public function isEnabled(): bool;
    
    /**
     * Get supported currencies
     * 
     * @return string[]
     */
    public function getSupportedCurrencies(): array;
    
    /**
     * Create a payment request
     * 
     * @param string $orderId Unique order identifier
     * @param Money $amount Payment amount
     * @param string $description Payment description
     * @param string $callbackUrl URL to redirect after payment
     * @param array $metadata Additional payment metadata
     * @return array Payment creation response
     */
    public function createPayment(
        string $orderId,
        Money $amount,
        string $description,
        string $callbackUrl,
        array $metadata = []
    ): array;
    
    /**
     * Verify a payment
     * 
     * @param string $paymentId Payment identifier
     * @param array $callbackData Data received from gateway callback
     * @return array Verification result
     */
    public function verifyPayment(string $paymentId, array $callbackData): array;
    
    /**
     * Get payment status
     * 
     * @param string $paymentId Payment identifier
     * @return array Payment status information
     */
    public function getPaymentStatus(string $paymentId): array;
    
    /**
     * Refund a payment
     * 
     * @param string $paymentId Payment identifier
     * @param Money $amount Refund amount (null for full refund)
     * @param string $reason Refund reason
     * @return array Refund result
     */
    public function refundPayment(string $paymentId, ?Money $amount = null, string $reason = ''): array;
    
    /**
     * Get gateway configuration requirements
     * 
     * @return array Configuration fields required by this gateway
     */
    public function getConfigurationFields(): array;
    
    /**
     * Validate gateway configuration
     * 
     * @param array $config Configuration to validate
     * @return bool True if configuration is valid
     */
    public function validateConfiguration(array $config): bool;
    
    /**
     * Get minimum payment amount
     */
    public function getMinimumAmount(): Money;
    
    /**
     * Get maximum payment amount
     */
    public function getMaximumAmount(): Money;
    
    /**
     * Get gateway fees
     * 
     * @param Money $amount Payment amount
     * @return Money Gateway fee amount
     */
    public function calculateFee(Money $amount): Money;
}