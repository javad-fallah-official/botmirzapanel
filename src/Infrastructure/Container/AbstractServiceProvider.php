<?php

declare(strict_types=1);

namespace App\Infrastructure\Container;

use App\Shared\Contracts\ContainerInterface;

/**
 * Abstract Service Provider
 * Base implementation for service providers
 */
abstract class AbstractServiceProvider implements ServiceProviderInterface
{
    protected bool $deferred = false;
    protected array $provides = [];

    /**
     * Register services in the container
     */
    abstract public function register(ContainerInterface $container): void;

    /**
     * Boot services after all providers have been registered
     * Override this method if you need to perform actions after registration
     */
    public function boot(ContainerInterface $container): void
    {
        // Default implementation does nothing
    }

    /**
     * Get the services provided by this provider
     */
    public function provides(): array
    {
        return $this->provides;
    }

    /**
     * Check if this provider is deferred
     */
    public function isDeferred(): bool
    {
        return $this->deferred;
    }

    /**
     * Mark this provider as deferred
     */
    protected function defer(): void
    {
        $this->deferred = true;
    }

    /**
     * Set the services provided by this provider
     */
    protected function setProvides(array $services): void
    {
        $this->provides = $services;
    }

    /**
     * Helper method to register a singleton
     */
    protected function singleton(ContainerInterface $container, string $abstract, mixed $concrete = null): void
    {
        $container->register($abstract, $concrete ?? $abstract, true);
    }

    /**
     * Helper method to register a transient service
     */
    protected function bind(ContainerInterface $container, string $abstract, mixed $concrete = null): void
    {
        $container->register($abstract, $concrete ?? $abstract, false);
    }

    /**
     * Helper method to register a factory
     */
    protected function factory(ContainerInterface $container, string $abstract, callable $factory): void
    {
        $container->factory($abstract, $factory);
    }

    /**
     * Helper method to register an alias
     */
    protected function alias(ContainerInterface $container, string $alias, string $abstract): void
    {
        if (method_exists($container, 'alias')) {
            $container->alias($alias, $abstract);
        }
    }
}