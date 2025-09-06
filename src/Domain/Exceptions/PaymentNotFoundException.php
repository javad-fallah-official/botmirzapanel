<?php

namespace BotMirzaPanel\Domain\Exceptions;

class PaymentNotFoundException extends EntityNotFoundException
{
    public function __construct()
    {
        parent::__construct($message, $code, $previous);
    }
}