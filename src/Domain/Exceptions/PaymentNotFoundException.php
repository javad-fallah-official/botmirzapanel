<?php

namespace BotMirzaPanel\Domain\Exceptions;

class PaymentNotFoundException extends EntityNotFoundException
{
    public function __construct(string $message = 'Payment not found', int $code = 0, \Throwable $previous = null): void
    {
        parent::__construct($message, $code, $previous);
    }
}