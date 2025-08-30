<?php

declare(strict_types=1);

namespace BotMirzaPanel\Infrastructure\Container;

use BotMirzaPanel\Shared\Contracts\ContainerInterface;
use BotMirzaPanel\Shared\Exceptions\ServiceException;
use ReflectionClass;
use ReflectionParameter;
use ReflectionException;

/**
 * Dependency Injection Container
 * Manages service registration, resolution, and lifecycle
 */
class Container implements ContainerInterface
{
    private array $services = [];
    private array $instances = [];
    private array $factories = [];
    private array $singletons = [];
    private array $aliases = [];
    private array $resolving = [];

    public function register(string $id, mixed $service, bool $singleton = false): void
    {
        $this->services[$id] = $service;
        
        if ($singleton) {
            $this->singletons[$id] = true;
        }
    }

    public function get(string $id): mixed
    {
        // Check for circular dependencies
        if (isset($this->resolving[$id])) {
            $chain = implode(' -> ', array_keys($this->resolving)) . ' -> ' . $id;
            throw new ServiceException(
                'Container',
                'get',
                "Circular dependency detected: {$chain}"
            );
        }

        // Return existing singleton instance
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        // Resolve alias
        if (isset($this->aliases[$id])) {
            return $this->get($this->aliases[$id]);
        }

        // Mark as resolving
        $this->resolving[$id] = true;

        try {
            $service = $this->resolve($id);
            
            // Store singleton instance
            if (isset($this->singletons[$id])) {
                $this->instances[$id] = $service;
            }
            
            return $service;
        } finally {
            // Remove from resolving
            unset($this->resolving[$id]);
        }
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]) || 
               isset($this->instances[$id]) || 
               isset($this->aliases[$id]) ||
               class_exists($id) ||
               interface_exists($id);
    }

    public function remove(string $id): void
    {
        unset(
            $this->services[$id],
            $this->instances[$id],
            $this->factories[$id],
            $this->singletons[$id],
            $this->aliases[$id]
        );
    }

    public function getServiceIds(): array
    {
        return array_keys($this->services);
    }

    public function make(string $className, array $parameters = []): object
    {
        try {
            $reflection = new ReflectionClass($className);
            
            if (!$reflection->isInstantiable()) {
                throw new ServiceException(
                    'Container',
                    'make',
                    "Class {$className} is not instantiable"
                );
            }

            $constructor = $reflection->getConstructor();
            
            if ($constructor === null) {
                return $reflection->newInstance();
            }

            $dependencies = $this->resolveDependencies(
                $constructor->getParameters(),
                $parameters
            );

            return $reflection->newInstanceArgs($dependencies);
        } catch (ReflectionException $e) {
            throw new ServiceException(
                'Container',
                'make',
                "Failed to create instance of {$className}: {$e->getMessage()}"
            );
        }
    }

    public function factory(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
    }

    /**
     * Register a singleton service
     */
    public function singleton(string $id, mixed $service): void
    {
        $this->register($id, $service, true);
    }

    /**
     * Register an alias for a service
     */
    public function alias(string $alias, string $id): void
    {
        $this->aliases[$alias] = $id;
    }

    /**
     * Bind an interface to an implementation
     */
    public function bind(string $abstract, string $concrete, bool $singleton = false): void
    {
        $this->register($abstract, $concrete, $singleton);
    }

    /**
     * Get all registered services (alias for getServiceIds)
     */
    public function getServices(): array
    {
        return $this->getServiceIds();
    }

    /**
     * Register a service provider
     */
    public function registerProvider(ServiceProviderInterface $provider): void
    {
        $provider->register($this);
    }

    /**
     * Get all registered singletons
     */
    public function getSingletons(): array
    {
        return array_keys($this->singletons);
    }

    /**
     * Clear all instances (useful for testing)
     */
    public function clearInstances(): void
    {
        $this->instances = [];
    }

    /**
     * Resolve a service
     */
    private function resolve(string $id): mixed
    {
        // Check for factory
        if (isset($this->factories[$id])) {
            return $this->factories[$id]($this);
        }

        // Check for registered service
        if (isset($this->services[$id])) {
            $service = $this->services[$id];
            
            if (is_string($service) && class_exists($service)) {
                return $this->make($service);
            }
            
            if (is_callable($service)) {
                return $service($this);
            }
            
            return $service;
        }

        // Try to auto-resolve class
        if (class_exists($id)) {
            return $this->make($id);
        }

        throw new ServiceException(
            'Container',
            'resolve',
            "Service '{$id}' not found and cannot be auto-resolved"
        );
    }

    /**
     * Resolve constructor dependencies
     */
    private function resolveDependencies(array $parameters, array $provided = []): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();
            
            // Use provided parameter if available
            if (array_key_exists($name, $provided)) {
                $dependencies[] = $provided[$name];
                continue;
            }

            $type = $parameter->getType();
            
            if ($type === null) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new ServiceException(
                        'Container',
                        'resolveDependencies',
                        "Cannot resolve parameter '{$name}' - no type hint and no default value"
                    );
                }
                continue;
            }

            $typeName = $type->getName();
            
            // Handle built-in types
            if ($type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new ServiceException(
                        'Container',
                        'resolveDependencies',
                        "Cannot resolve built-in type '{$typeName}' for parameter '{$name}'"
                    );
                }
                continue;
            }

            // Resolve class/interface dependency
            try {
                $dependencies[] = $this->get($typeName);
            } catch (ServiceException $e) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } elseif ($type->allowsNull()) {
                    $dependencies[] = null;
                } else {
                    throw new ServiceException(
                        'Container',
                        'resolveDependencies',
                        "Cannot resolve dependency '{$typeName}' for parameter '{$name}': {$e->getMessage()}"
                    );
                }
            }
        }

        return $dependencies;
    }

    /**
     * Check if a service is registered as singleton
     */
    public function isSingleton(string $id): bool
    {
        return isset($this->singletons[$id]);
    }

    /**
     * Get container statistics
     */
    public function getStats(): array
    {
        return [
            'services' => count($this->services),
            'instances' => count($this->instances),
            'factories' => count($this->factories),
            'singletons' => count($this->singletons),
            'aliases' => count($this->aliases),
        ];
    }

    /**
     * Validate container configuration
     */
    public function validate(): array
    {
        $issues = [];

        foreach ($this->services as $id => $service) {
            try {
                // Try to resolve without creating instance
                if (is_string($service) && class_exists($service)) {
                    $reflection = new ReflectionClass($service);
                    if (!$reflection->isInstantiable()) {
                        $issues[] = "Service '{$id}' points to non-instantiable class '{$service}'";
                    }
                }
            } catch (ReflectionException $e) {
                $issues[] = "Service '{$id}' has reflection error: {$e->getMessage()}";
            }
        }

        // Check for orphaned aliases
        foreach ($this->aliases as $alias => $target) {
            if (!$this->has($target)) {
                $issues[] = "Alias '{$alias}' points to non-existent service '{$target}'";
            }
        }

        return $issues;
    }
}