<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers;

use App\Infrastructure\Container\AbstractServiceProvider;
use App\Shared\Contracts\ContainerInterface;
use App\Infrastructure\Database\DatabaseManager;
use App\Infrastructure\Config\ConfigManager;
use App\Infrastructure\Cache\CacheManager;
use App\Infrastructure\Logging\Logger;
use App\Shared\Contracts\DatabaseInterface;
use App\Shared\Contracts\ConfigInterface;
use App\Shared\Contracts\CacheInterface;
use App\Shared\Contracts\LoggerInterface;

/**
 * Infrastructure Service Provider
 * Registers infrastructure services like database, config, cache, logging
 */
class InfrastructureServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [
        DatabaseInterface::class,
        ConfigInterface::class,
        CacheInterface::class,
        LoggerInterface::class,
        DatabaseManager::class,
        ConfigManager::class,
        CacheManager::class,
        Logger::class,
    ];

    public function register(ContainerInterface $container): void
    {
        // Register core infrastructure services
        $this->registerDatabase($container);
        $this->registerConfig($container);
        $this->registerCache($container);
        $this->registerLogger($container);
    }

    private function registerDatabase(ContainerInterface $container): void
    {
        // Database Manager
        $this->singleton(
            $container,
            DatabaseManager::class,
            function (ContainerInterface $c) {
                return new DatabaseManager(
                    $c->get(ConfigInterface::class)
                );
            }
        );

        // Database Interface
        $this->alias($container, DatabaseInterface::class, DatabaseManager::class);
    }

    private function registerConfig(ContainerInterface $container): void
    {
        // Config Manager
        $this->singleton(
            $container,
            ConfigManager::class,
            function () {
                return new ConfigManager();
            }
        );

        // Config Interface
        $this->alias($container, ConfigInterface::class, ConfigManager::class);
    }

    private function registerCache(ContainerInterface $container): void
    {
        // Cache Manager
        $this->singleton(
            $container,
            CacheManager::class,
            function (ContainerInterface $c) {
                return new CacheManager(
                    $c->get(ConfigInterface::class)
                );
            }
        );

        // Cache Interface
        $this->alias($container, CacheInterface::class, CacheManager::class);
    }

    private function registerLogger(ContainerInterface $container): void
    {
        // Logger
        $this->singleton(
            $container,
            Logger::class,
            function (ContainerInterface $c) {
                return new Logger(
                    $c->get(ConfigInterface::class)
                );
            }
        );

        // Logger Interface
        $this->alias($container, LoggerInterface::class, Logger::class);
    }

    public function boot(ContainerInterface $container): void
    {
        // Initialize database connections
        $database = $container->get(DatabaseInterface::class);
        $database->connect();

        // Initialize cache connections if needed
        $cache = $container->get(CacheInterface::class);
        if (method_exists($cache, 'connect')) {
            $cache->connect();
        }

        // Configure logger
        $logger = $container->get(LoggerInterface::class);
        if (method_exists($logger, 'initialize')) {
            $logger->initialize();
        }
    }
}