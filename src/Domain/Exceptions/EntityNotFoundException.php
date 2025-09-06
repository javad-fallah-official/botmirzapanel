<?php

declare(strict_types=1);

namespace BotMirzaPanel\Domain\Exceptions;

use Exception;

/**
 * Exception thrown when a requested entity is not found
 */
class EntityNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct($message, $code, $previous);
    }
}