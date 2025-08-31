<?php

declare(strict_types=1);

namespace BotMirzaPanel\Infrastructure\Providers;

use BotMirzaPanel\Infrastructure\Container\AbstractServiceProvider;
use BotMirzaPanel\Shared\Contracts\ContainerInterface;
use BotMirzaPanel\User\UserService;
use BotMirzaPanel\Payment\PaymentService;
use BotMirzaPanel\Config\ConfigManager;
use BotMirzaPanel\Database\DatabaseManager;
use BotMirzaPanel\Application\Services\UserApplicationService;
use BotMirzaPanel\Application\Services\PaymentApplicationService;
use BotMirzaPanel\Domain\Services\UserDomainService;
use BotMirzaPanel\Domain\Services\PaymentDomainService;
use BotMirzaPanel\Domain\Repositories\UserRepositoryInterface;
use BotMirzaPanel\Domain\Repositories\PaymentRepositoryInterface;
use BotMirzaPanel\Shared\Contracts\EventDispatcherInterface;
use BotMirzaPanel\Panel\PanelService;
use BotMirzaPanel\Cron\CronService;
use BotMirzaPanel\Telegram\TelegramBot;

/**
 * Application Service Provider
 * Registers application services and use case handlers
 */
class ApplicationServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [
        UserService::class,
        PaymentService::class,
        PanelService::class,
        TelegramBot::class,
        CronService::class,
    ];

    public function register(ContainerInterface $container): void
    {
        // Register application services
        $this->registerApplicationServices($container);
    }

    private function registerApplicationServices(ContainerInterface $container): void
    {
        // User Service
        $this->singleton(
            $container,
            UserService::class,
            function (ContainerInterface $c) {
                return new UserService(
                    $c->get(ConfigManager::class),
                    $c->get(DatabaseManager::class)
                );
            }
        );

        // Alias for convenience
        $this->alias($container, 'user', UserService::class);

        // Payment Service
        $this->singleton(
            $container,
            PaymentService::class,
            function (ContainerInterface $c) {
                return new PaymentService(
                    $c->get(ConfigManager::class),
                    $c->get(DatabaseManager::class)
                );
            }
        );

        // Alias for convenience
        $this->alias($container, 'payment', PaymentService::class);

        // Panel Service
        $this->singleton(
            $container,
            PanelService::class,
            function (ContainerInterface $c) {
                return new PanelService(
                    $c->get(ConfigManager::class),
                    $c->get(DatabaseManager::class)
                );
            }
        );
        $this->alias($container, 'panel', PanelService::class);

        // Telegram Bot Service
        $this->singleton(
            $container,
            TelegramBot::class,
            function (ContainerInterface $c) {
                return new TelegramBot(
                    $c->get(ConfigManager::class)
                );
            }
        );
        $this->alias($container, 'telegram', TelegramBot::class);

        // Cron Service
        $this->singleton(
            $container,
            CronService::class,
            function (ContainerInterface $c) {
                return new CronService(
                    $c->get(ConfigManager::class),
                    $c->get(DatabaseManager::class),
                    $c->get(UserService::class),
                    $c->get(PanelService::class),
                    $c->get(TelegramBot::class)
                );
            }
        );
        $this->alias($container, 'cron', CronService::class);
    }

    public function boot(ContainerInterface $container): void
    {
        // Boot application services if needed
        // This is where you can perform any initialization
        // that requires other services to be registered
    }
}