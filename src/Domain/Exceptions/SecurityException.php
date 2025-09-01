<?php

declare(strict_types=1);

namespace BotMirzaPanel\Domain\Exceptions;

use Exception;

/**
 * Exception thrown when security-related operations fail
 */
class SecurityException extends Exception
{
    public function __construct(string $message = 'Security operation failed', int $code = 0, ?Exception $previous = null): void
    {
        parent::__construct($message, $code, $previous);
    }
}