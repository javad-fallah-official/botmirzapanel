<?php

namespace BotMirzaPanel\Application\Commands\Payment;

use BotMirzaPanel\Application\Commands\CommandInterface;
use BotMirzaPanel\Domain\ValueObjects\Payment\PaymentId;
use BotMirzaPanel\Domain\ValueObjects\Payment\PaymentMethod;

class ProcessPaymentCommand implements CommandInterface
{
    public function __construct(
        private PaymentId $paymentId,
        private PaymentMethod $method,
        private array $gatewayData = []
    ) {}

    public function getPaymentId(): PaymentId
    {
        return $this->paymentId;
    }

    public function getMethod(): PaymentMethod
    {
        return $this->method;
    }

    public function getGatewayData(): array
    {
        return $this->gatewayData;
    }
}