<?php

namespace BotMirzaPanel\Application\Commands\Payment;

use BotMirzaPanel\Application\Commands\CommandInterface;
use BotMirzaPanel\Domain\ValueObjects\User\UserId;
use BotMirzaPanel\Domain\ValueObjects\Common\Money;
use BotMirzaPanel\Domain\ValueObjects\Payment\PaymentMethod;

class CreatePaymentCommand implements CommandInterface
{
    public function __construct(
        private UserId $userId,
        private Money $amount,
        private PaymentMethod $method,
        private ?string $description = null
    ) {}

    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getMethod(): PaymentMethod
    {
        return $this->method;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}