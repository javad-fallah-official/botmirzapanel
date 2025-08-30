<?php

declare(strict_types=1);

namespace BotMirzaPanel\Shared\Exceptions;

/**
 * Exception thrown when validation fails
 */
class ValidationException extends ApplicationException
{
    /**
     * Validation errors
     * 
     * @var array
     */
    protected array $errors = [];

    /**
     * Create a new validation exception
     * 
     * @param string $message
     * @param array $errors Validation errors
     * @param array $context Additional context
     */
    public function __construct(
        string $message = 'Validation failed',
        array $errors = [],
        array $context = []
    ) {
        $this->errors = $errors;
        parent::__construct(
            $message,
            self::getErrorCode('validation'),
            null,
            $context
        );
    }

    /**
     * Get validation errors
     * 
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if there are errors for a specific field
     * 
     * @param string $field
     * @return bool
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    /**
     * Get errors for a specific field
     * 
     * @param string $field
     * @return array
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * Add a validation error
     * 
     * @param string $field
     * @param string $message
     * @return self
     */
    public function addError(string $field, string $message): self
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
        return $this;
    }

    /**
     * Convert to array including validation errors
     * 
     * @return array
     */
    public function toArray(): array
    {
        $array = parent::toArray();
        $array['errors'] = $this->errors;
        return $array;
    }
}