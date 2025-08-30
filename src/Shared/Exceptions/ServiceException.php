<?php

declare(strict_types=1);

namespace BotMirzaPanel\Shared\Exceptions;

/**
 * Exception thrown when service operations fail
 */
class ServiceException extends ApplicationException
{
    /**
     * Service name that threw the exception
     * 
     * @var string
     */
    protected string $serviceName;

    /**
     * Operation that failed
     * 
     * @var string
     */
    protected string $operation;

    /**
     * Create a new service exception
     * 
     * @param string $serviceName Name of the service
     * @param string $operation Operation that failed
     * @param string $message Exception message
     * @param array $context Additional context
     * @param \Exception|null $previous Previous exception
     */
    public function __construct(
        string $serviceName,
        string $operation,
        string $message = 'Service operation failed',
        array $context = [],
        ?\Exception $previous = null
    ) {
        $this->serviceName = $serviceName;
        $this->operation = $operation;
        
        $fullMessage = sprintf(
            '[%s::%s] %s',
            $serviceName,
            $operation,
            $message
        );
        
        parent::__construct(
            $fullMessage,
            self::getErrorCode('service'),
            $previous,
            array_merge($context, [
                'service' => $serviceName,
                'operation' => $operation,
            ])
        );
    }

    /**
     * Get the service name
     * 
     * @return string
     */
    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    /**
     * Get the failed operation
     * 
     * @return string
     */
    public function getOperation(): string
    {
        return $this->operation;
    }

    /**
     * Create a service exception for external API failures
     * 
     * @param string $serviceName
     * @param string $apiEndpoint
     * @param string $message
     * @param array $context
     * @return self
     */
    public static function externalApiFailure(
        string $serviceName,
        string $apiEndpoint,
        string $message = 'External API call failed',
        array $context = []
    ): self {
        return new self(
            $serviceName,
            'external_api_call',
            $message,
            array_merge($context, ['endpoint' => $apiEndpoint])
        );
    }

    /**
     * Create a service exception for configuration errors
     * 
     * @param string $serviceName
     * @param string $configKey
     * @param string $message
     * @return self
     */
    public static function configurationError(
        string $serviceName,
        string $configKey,
        string $message = 'Service configuration error'
    ): self {
        return new self(
            $serviceName,
            'configuration',
            $message,
            ['config_key' => $configKey]
        );
    }
}