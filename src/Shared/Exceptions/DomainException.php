<?php

declare(strict_types=1);

namespace BotMirzaPanel\Shared\Exceptions;

/**
 * Exception thrown when domain business rules are violated
 */
class DomainException extends ApplicationException
{
    /**
     * Domain entity or aggregate that caused the exception
     */
    protected string $domain;

    /**
     * Business rule that was violated
     */
    protected string $rule;

    /**
     * Create a new domain exception
     *
     * @param string $message Exception message
     * @param string $domain Domain entity or aggregate
     * @param string $rule Business rule that was violated
     * @param int $code Exception code
     * @param \Exception|null $previous Previous exception
     * @param array $context Additional context
     */
    public function __construct(
        string $message,
        string $domain = '',
        string $rule = '',
        int $code = 0,
        ?\Exception $previous = null,
        array $context = []
    ) {
        $this->domain = $domain;
        $this->rule = $rule;
        
        parent::__construct(
            $message,
            $code ?: self::getErrorCode('domain'),
            $previous,
            $context
        );
    }

    /**
     * Get the domain that caused the exception
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * Get the business rule that was violated
     */
    public function getRule(): string
    {
        return $this->rule;
    }

    /**
     * Create a domain exception for invalid entity state
     */
    public static function invalidEntityState(
        string $entity,
        string $message,
        array $context = []
    ): self {
        return new self(
            $message,
            $entity,
            'invalid_state',
            0,
            null,
            $context
        );
    }

    /**
     * Create a domain exception for business rule violation
     */
    public static function businessRuleViolation(
        string $rule,
        string $message,
        string $domain = '',
        array $context = []
    ): self {
        return new self(
            $message,
            $domain,
            $rule,
            0,
            null,
            $context
        );
    }

    /**
     * Create a domain exception for entity not found
     */
    public static function entityNotFound(
        string $entity,
        string $identifier,
        array $context = []
    ): self {
        return new self(
            "$entity not found with identifier: $identifier",
            $entity,
            'not_found',
            0,
            null,
            $context
        );
    }

    /**
     * Create a domain exception for duplicate entity
     */
    public static function duplicateEntity(
        string $entity,
        string $identifier,
        array $context = []
    ): self {
        return new self(
            "$entity already exists with identifier: $identifier",
            $entity,
            'duplicate',
            0,
            null,
            $context
        );
    }

    /**
     * Convert to array including domain-specific information
     */
    public function toArray(): array
    {
        $array = parent::toArray();
        $array['domain'] = $this->domain;
        $array['rule'] = $this->rule;
        return $array;
    }
}