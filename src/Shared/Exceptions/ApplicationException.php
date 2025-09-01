<?php

declare(strict_types=1);

namespace BotMirzaPanel\Shared\Exceptions;

use Exception;

/**
 * Base application exception
 * All custom exceptions should extend this class
 */
class ApplicationException extends Exception
{
    /**
     * Additional context data for the exception
     * 
     * @var array
     */
    protected array $context = [];

    /**
     * Error code mapping for different exception types
     * 
     * @var array
     */
    protected static array $errorCodes = [
        'validation' => 1000,
        'authentication' => 2000,
        'authorization' => 3000,
        'not_found' => 4000,
        'service' => 5000,
        'external' => 6000,
        'database' => 7000,
    ];

    /**
     * Create a new application exception
     * 
     * @param string $message Exception message
     * @param int $code Exception code
     * @param Exception|null $previous Previous exception
     * @param array $context Additional context data
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get the exception context
     * 
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Set additional context data
     * 
     * @param array $context
     * @return self
     */
    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Add context data
     * 
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function addContext(string $key, mixed $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }

    /**
     * Get error code for a specific type
     * 
     * @param string $type
     * @return int
     */
    public static function getErrorCode(string $type): int
    {
        return self::$errorCodes[$type] ?? 0;
    }

    /**
     * Convert exception to array for logging/debugging
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'context' => $this->context,
            'trace' => $this->getTraceAsString(),
        ];
    }
}