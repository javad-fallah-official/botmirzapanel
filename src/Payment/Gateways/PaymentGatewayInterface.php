<?php

namespace BotMirzaPanel\Payment\Gateways;

/**
 * Interface for all payment gateways
 * Defines the contract that all payment gateways must implement
 */
interface PaymentGatewayInterface
{
    /**
     * Get gateway display name
     */
    public function getDisplayName(): string;

    /**
     * Get gateway description
     */
    public function getDescription(): string;

    /**
     * Get supported currencies
     */
    public function getSupportedCurrencies(): array;

    /**
     * Get minimum payment amount
     */
    public function getMinAmount(): float;

    /**
     * Get maximum payment amount
     */
    public function getMaxAmount(): float;

    /**
     * Check if gateway is properly configured
     */
    public function isConfigured(): bool;

    /**
     * Create a payment
     * 
     * @param array $paymentData Payment information
     * @return array Gateway response with payment URL and transaction ID
     * @throws \Exception If payment creation fails
     */
    public function createPayment(array $paymentData): array;

    /**
     * Verify payment callback
     * 
     * @param array $callbackData Callback data from gateway
     * @return array Verification result with status and transaction details
     */
    public function verifyCallback(array $callbackData): array;

    /**
     * Get payment status
     * 
     * @param string $transactionId Gateway transaction ID
     * @return array Payment status information
     */
    public function getPaymentStatus(string $transactionId): array;

    /**
     * Cancel payment
     * 
     * @param string $transactionId Gateway transaction ID
     * @return bool Success status
     */
    public function cancelPayment(string $transactionId): bool;

    /**
     * Refund payment
     * 
     * @param string $transactionId Gateway transaction ID
     * @param float $amount Refund amount (optional, full refund if not specified)
     * @return array Refund result
     */
    public function refundPayment(string $transactionId, float $amount = null): array;

    /**
     * Get gateway configuration requirements
     * 
     * @return array Required configuration fields
     */
    public function getConfigurationRequirements(): array;

    /**
     * Validate gateway configuration
     * 
     * @param array $config Configuration to validate
     * @return array Validation result with errors if any
     */
    public function validateConfiguration(array $config): array;

    /**
     * Test gateway connection
     * 
     * @return array Test result
     */
    public function testConnection(): array;
}