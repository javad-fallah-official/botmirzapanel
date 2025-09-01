<?php

namespace BotMirzaPanel\Infrastructure\External\Payment;

/**
 * Payment Gateway Factory Interface
 * Creates payment gateway instances based on gateway type
 */
interface PaymentGatewayFactoryInterface
{
    /**
     * Create a payment gateway instance
     *
     * @param string $gatewayType The type of payment gateway (nowpayments, aqayepardakht, etc.)
     * @param array $config Gateway configuration
     * @return PaymentGatewayInterface
     * @throws \InvalidArgumentException If gateway type is not supported
     */
    public function create(string $gatewayType, array $config): PaymentGatewayInterface;

    /**
     * Get list of supported gateway types
     *
     * @return array
     */
    public function getSupportedTypes(): array;

    /**
     * Check if a gateway type is supported
     *
     * @param string $gatewayType
     * @return bool
     */
    public function supports(string $gatewayType): bool;
}