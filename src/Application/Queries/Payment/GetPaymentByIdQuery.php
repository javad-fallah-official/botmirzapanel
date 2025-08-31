<?php

namespace BotMirzaPanel\Application\Queries\Payment;

use BotMirzaPanel\Application\Queries\QueryInterface;
use BotMirzaPanel\Domain\ValueObjects\Payment\PaymentId;

class GetPaymentByIdQuery implements QueryInterface
{
    public function __construct(
        private PaymentId $paymentId
    ) {}

    public function getPaymentId(): PaymentId
    {
        return $this->paymentId;
    }
}