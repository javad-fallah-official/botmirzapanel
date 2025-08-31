<?php

declare(strict_types=1);

namespace BotMirzaPanel\Infrastructure\Providers;

use BotMirzaPanel\Infrastructure\Container\AbstractServiceProvider;
use BotMirzaPanel\Shared\Contracts\ContainerInterface;
use BotMirzaPanel\Config\ConfigManager;
use BotMirzaPanel\Infrastructure\Services\AssetOptimizationService;
use BotMirzaPanel\Infrastructure\Services\DatabaseOptimizationService;
use BotMirzaPanel\Infrastructure\Services\MemoryOptimizationService;
use BotMirzaPanel\Infrastructure\Services\PerformanceMonitoringService;
use BotMirzaPanel\Shared\Contracts\CacheInterface;

/**
 * Performance Service Provider
 * 
 * Registers all performance optimization services with the DI container.
 * Configures services based on environment and performance configuration.
 */
class PerformanceServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [
        DatabaseOptimizationService::class,
        MemoryOptimizationService::class,
        AssetOptimizationService::class,
        PerformanceMonitoringService::class,
        'database.optimization',
        'memory.optimization',
        'asset.optimization',
        'performance.monitoring',
    ];

    public function register(ContainerInterface $container): void
    {
        // Register optimization services
        $this->registerDatabaseOptimizationService($container);
        $this->registerMemoryOptimizationService($container);
        $this->registerAssetOptimizationService($container);
        $this->registerPerformanceMonitoringService($container);
    }

    /**
     * Register database optimization service
     */
    private function registerDatabaseOptimizationService(ContainerInterface $container): void
    {
        $this->singleton(
            $container,
            DatabaseOptimizationService::class,
            function (ContainerInterface $c) {
                /** @var ConfigManager $config */
                $config = $c->get(ConfigManager::class);
                $databaseConfig = (array) $config->get('performance.database', []);

                return new DatabaseOptimizationService(
                    $c->get(CacheInterface::class),
                    $databaseConfig
                );
            }
        );

        $this->alias($container, 'database.optimization', DatabaseOptimizationService::class);
    }

    /**
     * Register memory optimization service
     */
    private function registerMemoryOptimizationService(ContainerInterface $container): void
    {
        $this->singleton(
            $container,
            MemoryOptimizationService::class,
            function (ContainerInterface $c) {
                /** @var ConfigManager $config */
                $config = $c->get(ConfigManager::class);
                $memoryConfig = (array) $config->get('performance.memory', []);

                return new MemoryOptimizationService(
                    $c->get(CacheInterface::class),
                    $memoryConfig
                );
            }
        );

        $this->alias($container, 'memory.optimization', MemoryOptimizationService::class);
    }

    /**
     * Register asset optimization service
     */
    private function registerAssetOptimizationService(ContainerInterface $container): void
    {
        $this->singleton(
            $container,
            AssetOptimizationService::class,
            function (ContainerInterface $c) {
                /** @var ConfigManager $config */
                $config = $c->get(ConfigManager::class);
                $assetConfig = (array) $config->get('performance.assets', []);

                return new AssetOptimizationService(
                    $c->get(CacheInterface::class),
                    $assetConfig
                );
            }
        );

        $this->alias($container, 'asset.optimization', AssetOptimizationService::class);
    }

    /**
     * Register performance monitoring service
     */
    private function registerPerformanceMonitoringService(ContainerInterface $container): void
    {
        $this->singleton(
            $container,
            PerformanceMonitoringService::class,
            function (ContainerInterface $c) {
                /** @var ConfigManager $config */
                $config = $c->get(ConfigManager::class);
                $monitoringConfig = (array) $config->get('performance.monitoring', []);

                return new PerformanceMonitoringService(
                    $c->get(CacheInterface::class),
                    $monitoringConfig
                );
            }
        );

        $this->alias($container, 'performance.monitoring', PerformanceMonitoringService::class);
    }

    /**
     * Boot performance services
     * 
     * Initialize services that need to be started immediately.
     */
    public function boot(ContainerInterface $container): void
    {
        /** @var ConfigManager $config */
        $config = $container->get(ConfigManager::class);

        $environment = (string) $config->get('app.env', 'production');
        $envConfig = (array) $config->get("performance.environment.{$environment}", []);

        // Initialize memory optimization if enabled
        if ($envConfig['enable_memory_optimization'] ?? false) {
            $memoryService = $container->get(MemoryOptimizationService::class);
            $memoryService->optimizeMemory();
        }

        // Initialize performance monitoring if enabled
        if ($envConfig['enable_monitoring'] ?? false) {
            $container->get(PerformanceMonitoringService::class);
            // Start monitoring background processes if needed
        }

        // Warm up cache if enabled
        $warmingEnabled = (bool) $config->get('performance.caching.warming.enabled', false);
        if ($warmingEnabled) {
            $cacheService = $container->get(CacheInterface::class);
            $preloadData = (array) $config->get('performance.caching.warming.preload_data', []);

            if (!empty($preloadData)) {
                $cacheService->warmUp($preloadData);
            }
        }
    }

    /**
     * Get service dependencies
     */
    public function provides(): array
    {
        return [
            DatabaseOptimizationService::class,
            MemoryOptimizationService::class,
            AssetOptimizationService::class,
            PerformanceMonitoringService::class,
            'database.optimization',
            'memory.optimization',
            'asset.optimization',
            'performance.monitoring',
        ];
    }
}