<?php

namespace BotMirzaPanel\Application\Queries\Payment;

use BotMirzaPanel\Application\Queries\QueryHandlerInterface;
use BotMirzaPanel\Domain\Entities\Payment\Payment;
use BotMirzaPanel\Domain\Repositories\PaymentRepositoryInterface;
use BotMirzaPanel\Domain\Exceptions\PaymentNotFoundException;

class GetPaymentByIdQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository
    ) {}

    public function handle(GetPaymentByIdQuery $query): ?Payment
    {
        $payment = $this->paymentRepository->findById($query->getPaymentId());
        
        if (!$payment) {
            throw new PaymentNotFoundException(
                'Payment not found: ' . $query->getPaymentId()->getValue()
            );
        }

        return $payment;
    }
}