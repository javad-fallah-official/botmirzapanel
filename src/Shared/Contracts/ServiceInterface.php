<?php

declare(strict_types=1);

namespace App\Shared\Contracts;

/**
 * Base service interface for application services
 * Defines common service operations and lifecycle methods
 */
interface ServiceInterface
{
    /**
     * Initialize the service
     * Called when the service is first instantiated
     * 
     * @return void
     */
    public function initialize(): void;

    /**
     * Check if the service is properly configured and ready to use
     * 
     * @return bool True if service is ready, false otherwise
     */
    public function isReady(): bool;

    /**
     * Get the service name/identifier
     * 
     * @return string Service identifier
     */
    public function getName(): string;

    /**
     * Get service dependencies
     * 
     * @return array Array of service class names this service depends on
     */
    public function getDependencies(): array;

    /**
     * Cleanup resources when service is destroyed
     * 
     * @return void
     */
    public function cleanup(): void;
}