<?php

namespace BotMirzaPanel\Application\Commands\Payment;

use BotMirzaPanel\Application\Commands\CommandHandlerInterface;
use BotMirzaPanel\Domain\Entities\Payment\Payment;
use BotMirzaPanel\Domain\Repositories\PaymentRepositoryInterface;
use BotMirzaPanel\Domain\ValueObjects\Payment\PaymentId;
use BotMirzaPanel\Domain\ValueObjects\Payment\PaymentStatus;
use BotMirzaPanel\Domain\Services\Payment\PaymentService;

class CreatePaymentCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository,
        private PaymentService $paymentService
    ) {}

    public function handle(CreatePaymentCommand $command): PaymentId
    {
        $paymentId = PaymentId::generate();
        
        $payment = new Payment(
            $paymentId,
            $command->getUserId(),
            $command->getAmount(),
            $command->getMethod(),
            PaymentStatus::pending(),
            $command->getDescription()
        );

        $this->paymentRepository->save($payment);

        return $paymentId;
    }
}