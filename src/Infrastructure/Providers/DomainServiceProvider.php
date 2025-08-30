<?php

declare(strict_types=1);

namespace BotMirzaPanel\Infrastructure\Providers;

use BotMirzaPanel\Infrastructure\Container\AbstractServiceProvider;
use BotMirzaPanel\Shared\Contracts\ContainerInterface;
use BotMirzaPanel\Domain\Repositories\UserRepositoryInterface;
use BotMirzaPanel\Domain\Repositories\PaymentRepositoryInterface;
use BotMirzaPanel\Domain\Services\UserDomainService;
use BotMirzaPanel\Domain\Services\PaymentDomainService;
use BotMirzaPanel\Shared\Contracts\EventDispatcherInterface;

/**
 * Domain Service Provider
 * Registers domain services and event dispatcher
 */
class DomainServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [
        UserDomainService::class,
        PaymentDomainService::class,
        EventDispatcherInterface::class,
    ];

    public function register(ContainerInterface $container): void
    {
        // Register domain services (repositories must be bound elsewhere)
        $this->registerDomainServices($container);

        // Register a lightweight in-memory event dispatcher
        $this->registerEventDispatcher($container);
    }

    private function registerDomainServices(ContainerInterface $container): void
    {
        // User Domain Service
        $this->singleton(
            $container,
            UserDomainService::class,
            function (ContainerInterface $c) {
                return new UserDomainService(
                    $c->get(UserRepositoryInterface::class)
                );
            }
        );

        // Payment Domain Service
        $this->singleton(
            $container,
            PaymentDomainService::class,
            function (ContainerInterface $c) {
                return new PaymentDomainService(
                    $c->get(PaymentRepositoryInterface::class),
                    $c->get(UserRepositoryInterface::class)
                );
            }
        );
    }

    private function registerEventDispatcher(ContainerInterface $container): void
    {
        $this->singleton(
            $container,
            EventDispatcherInterface::class,
            function () {
                return new class implements EventDispatcherInterface {
                    private array $listeners = [];

                    public function dispatch(string $eventName, $eventData = null): void
                    {
                        if (!isset($this->listeners[$eventName])) {
                            return;
                        }
                        // Sort by priority (descending)
                        krsort($this->listeners[$eventName]);
                        foreach ($this->listeners[$eventName] as $priority => $listeners) {
                            foreach ($listeners as $listener) {
                                try {
                                    $listener($eventData, $eventName);
                                } catch (\Throwable $e) {
                                    // Ignore listener exceptions to avoid breaking dispatch flow
                                }
                            }
                        }
                    }

                    public function addListener(string $eventName, callable $listener, int $priority = 0): void
                    {
                        $this->listeners[$eventName][$priority][] = $listener;
                    }

                    public function removeListener(string $eventName, callable $listener): void
                    {
                        if (!isset($this->listeners[$eventName])) {
                            return;
                        }
                        foreach ($this->listeners[$eventName] as $priority => $listeners) {
                            foreach ($listeners as $index => $l) {
                                if ($l === $listener) {
                                    unset($this->listeners[$eventName][$priority][$index]);
                                }
                            }
                            if (empty($this->listeners[$eventName][$priority])) {
                                unset($this->listeners[$eventName][$priority]);
                            }
                        }
                        if (empty($this->listeners[$eventName])) {
                            unset($this->listeners[$eventName]);
                        }
                    }

                    public function hasListeners(string $eventName): bool
                    {
                        return !empty($this->listeners[$eventName]);
                    }

                    public function getListeners(string $eventName): array
                    {
                        if (!isset($this->listeners[$eventName])) {
                            return [];
                        }
                        $result = [];
                        krsort($this->listeners[$eventName]);
                        foreach ($this->listeners[$eventName] as $listeners) {
                            foreach ($listeners as $listener) {
                                $result[] = $listener;
                            }
                        }
                        return $result;
                    }
                };
            }
        );
    }

    public function boot(ContainerInterface $container): void
    {
        // Place to register event listeners if needed in the future
    }
}