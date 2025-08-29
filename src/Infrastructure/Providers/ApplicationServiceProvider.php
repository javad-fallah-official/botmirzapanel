<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers;

use App\Infrastructure\Container\AbstractServiceProvider;
use App\Shared\Contracts\ContainerInterface;
use App\Application\Services\UserApplicationService;
use App\Application\Services\PaymentApplicationService;
use App\Domain\Services\UserDomainService;
use App\Domain\Services\PaymentDomainService;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Repositories\PaymentRepositoryInterface;
use App\Shared\Contracts\EventDispatcherInterface;

/**
 * Application Service Provider
 * Registers application services and use case handlers
 */
class ApplicationServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [
        UserApplicationService::class,
        PaymentApplicationService::class,
    ];

    public function register(ContainerInterface $container): void
    {
        // Register application services
        $this->registerApplicationServices($container);
    }

    private function registerApplicationServices(ContainerInterface $container): void
    {
        // User Application Service
        $this->singleton(
            $container,
            UserApplicationService::class,
            function (ContainerInterface $c) {
                return new UserApplicationService(
                    $c->get(UserRepositoryInterface::class),
                    $c->get(UserDomainService::class),
                    $c->get(EventDispatcherInterface::class)
                );
            }
        );

        // Payment Application Service
        $this->singleton(
            $container,
            PaymentApplicationService::class,
            function (ContainerInterface $c) {
                return new PaymentApplicationService(
                    $c->get(PaymentRepositoryInterface::class),
                    $c->get(PaymentDomainService::class),
                    $c->get(UserRepositoryInterface::class),
                    $c->get(EventDispatcherInterface::class)
                );
            }
        );
    }

    public function boot(ContainerInterface $container): void
    {
        // Boot application services if needed
        // This is where you can perform any initialization
        // that requires other services to be registered
    }
}