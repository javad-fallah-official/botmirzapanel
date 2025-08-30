<?php

declare(strict_types=1);

namespace BotMirzaPanel\Infrastructure\Providers;

use BotMirzaPanel\Infrastructure\Container\AbstractServiceProvider;
use BotMirzaPanel\Shared\Contracts\ContainerInterface;
use BotMirzaPanel\Application\Services\UserApplicationService;
use BotMirzaPanel\Application\Services\PaymentApplicationService;
use BotMirzaPanel\Domain\Services\UserDomainService;
use BotMirzaPanel\Domain\Services\PaymentDomainService;
use BotMirzaPanel\Domain\Repositories\UserRepositoryInterface;
use BotMirzaPanel\Domain\Repositories\PaymentRepositoryInterface;
use BotMirzaPanel\Shared\Contracts\EventDispatcherInterface;

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