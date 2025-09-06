<?php

declare(strict_types=1);

namespace BotMirzaPanel\Domain\Exceptions;

use Exception;

/**
 * Exception thrown when domain validation fails
 */
class ValidationException extends Exception
{
    public function __construct()
    {
        parent::__construct($message, $code, $previous);
    }
}