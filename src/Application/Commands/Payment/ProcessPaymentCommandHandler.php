<?php

namespace BotMirzaPanel\Application\Commands\Payment;

use BotMirzaPanel\Application\Commands\CommandHandlerInterface;
use BotMirzaPanel\Domain\Repositories\PaymentRepositoryInterface;
use BotMirzaPanel\Domain\Services\Payment\PaymentService;
use BotMirzaPanel\Domain\ValueObjects\Payment\PaymentStatus;
use BotMirzaPanel\Domain\Exceptions\PaymentNotFoundException;

class ProcessPaymentCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository,
        private PaymentService $paymentService
    ) {}

    public function handle(ProcessPaymentCommand $command): bool
    {
        $payment = $this->paymentRepository->findById($command->getPaymentId());
        
        if (!$payment) {
            throw new PaymentNotFoundException(
                'Payment not found: ' . $command->getPaymentId()->getValue()
            );
        }

        return $this->paymentService->processPayment(
            $payment,
            $command->getMethod(),
            $command->getGatewayData()
        );
    }
}