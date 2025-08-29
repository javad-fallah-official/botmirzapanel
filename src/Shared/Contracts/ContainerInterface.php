<?php

declare(strict_types=1);

namespace App\Shared\Contracts;

/**
 * Dependency injection container interface
 * Provides service location and dependency resolution
 */
interface ContainerInterface
{
    /**
     * Register a service in the container
     * 
     * @param string $id Service identifier
     * @param mixed $service Service instance, factory callable, or class name
     * @param bool $singleton Whether to treat as singleton
     * @return void
     */
    public function register(string $id, $service, bool $singleton = true): void;

    /**
     * Get a service from the container
     * 
     * @param string $id Service identifier
     * @return mixed The service instance
     * @throws \InvalidArgumentException If service not found
     */
    public function get(string $id);

    /**
     * Check if a service is registered
     * 
     * @param string $id Service identifier
     * @return bool True if service exists
     */
    public function has(string $id): bool;

    /**
     * Remove a service from the container
     * 
     * @param string $id Service identifier
     * @return void
     */
    public function remove(string $id): void;

    /**
     * Get all registered service identifiers
     * 
     * @return string[] Array of service IDs
     */
    public function getServiceIds(): array;

    /**
     * Create an instance of a class with dependency injection
     * 
     * @param string $className The class name to instantiate
     * @param array $parameters Additional constructor parameters
     * @return object The created instance
     */
    public function make(string $className, array $parameters = []): object;

    /**
     * Register a factory for creating services
     * 
     * @param string $id Service identifier
     * @param callable $factory Factory function
     * @return void
     */
    public function factory(string $id, callable $factory): void;
}