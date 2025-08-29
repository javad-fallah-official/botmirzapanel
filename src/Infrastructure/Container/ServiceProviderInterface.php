<?php

declare(strict_types=1);

namespace App\Infrastructure\Container;

use App\Shared\Contracts\ContainerInterface;

/**
 * Service Provider Interface
 * Defines contract for service providers that register services in the container
 */
interface ServiceProviderInterface
{
    /**
     * Register services in the container
     */
    public function register(ContainerInterface $container): void;

    /**
     * Boot services after all providers have been registered
     */
    public function boot(ContainerInterface $container): void;

    /**
     * Get the services provided by this provider
     */
    public function provides(): array;

    /**
     * Check if this provider is deferred
     */
    public function isDeferred(): bool;
}