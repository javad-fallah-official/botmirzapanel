<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers;

use App\Infrastructure\Container\AbstractServiceProvider;
use App\Shared\Contracts\ContainerInterface;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Repositories\PaymentRepositoryInterface;
use App\Domain\Services\UserDomainService;
use App\Domain\Services\PaymentDomainService;
use App\Infrastructure\Persistence\UserRepository;
use App\Infrastructure\Persistence\PaymentRepository;
use App\Infrastructure\Events\EventDispatcher;
use App\Shared\Contracts\EventDispatcherInterface;

/**
 * Domain Service Provider
 * Registers domain services, repositories, and related dependencies
 */
class DomainServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [
        UserRepositoryInterface::class,
        PaymentRepositoryInterface::class,
        UserDomainService::class,
        PaymentDomainService::class,
        EventDispatcherInterface::class,
    ];

    public function register(ContainerInterface $container): void
    {
        // Register repositories
        $this->registerRepositories($container);
        
        // Register domain services
        $this->registerDomainServices($container);
        
        // Register event dispatcher
        $this->registerEventDispatcher($container);
    }

    private function registerRepositories(ContainerInterface $container): void
    {
        // User Repository
        $this->singleton(
            $container,
            UserRepositoryInterface::class,
            UserRepository::class
        );

        // Payment Repository
        $this->singleton(
            $container,
            PaymentRepositoryInterface::class,
            PaymentRepository::class
        );
    }

    private function registerDomainServices(ContainerInterface $container): void
    {
        // User Domain Service
        $this->singleton(
            $container,
            UserDomainService::class,
            function (ContainerInterface $c) {
                return new UserDomainService(
                    $c->get(UserRepositoryInterface::class),
                    $c->get(EventDispatcherInterface::class)
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
                    $c->get(UserRepositoryInterface::class),
                    $c->get(EventDispatcherInterface::class)
                );
            }
        );
    }

    private function registerEventDispatcher(ContainerInterface $container): void
    {
        $this->singleton(
            $container,
            EventDispatcherInterface::class,
            EventDispatcher::class
        );
    }

    public function boot(ContainerInterface $container): void
    {
        // Register event listeners if needed
        $eventDispatcher = $container->get(EventDispatcherInterface::class);
        
        // You can register event listeners here
        // $eventDispatcher->listen(UserCreated::class, UserCreatedListener::class);
    }
}