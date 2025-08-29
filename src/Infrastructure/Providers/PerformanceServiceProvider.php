<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers;

use App\Infrastructure\Services\AssetOptimizationService;
use App\Infrastructure\Services\CacheService;
use App\Infrastructure\Services\DatabaseOptimizationService;
use App\Infrastructure\Services\MemoryOptimizationService;
use App\Infrastructure\Services\PerformanceMonitoringService;
use App\Shared\Contracts\CacheInterface;
use App\Shared\DependencyInjection\ServiceProviderInterface;
use App\Shared\DependencyInjection\Container;

/**
 * Performance Service Provider
 * 
 * Registers all performance optimization services with the DI container.
 * Configures services based on environment and performance configuration.
 */
class PerformanceServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $this->registerCacheService($container);
        $this->registerDatabaseOptimizationService($container);
        $this->registerMemoryOptimizationService($container);
        $this->registerAssetOptimizationService($container);
        $this->registerPerformanceMonitoringService($container);
    }

    /**
     * Register cache service
     */
    private function registerCacheService(Container $container): void
    {
        $container->bind(CacheInterface::class, function (Container $container) {
            $config = $container->get('config');
            $performanceConfig = $config['performance'] ?? [];
            $cacheConfig = $performanceConfig['caching'] ?? [];
            
            return new CacheService($cacheConfig);
        });

        $container->alias('cache', CacheInterface::class);
        $container->alias(CacheService::class, CacheInterface::class);
    }

    /**
     * Register database optimization service
     */
    private function registerDatabaseOptimizationService(Container $container): void
    {
        $container->bind(DatabaseOptimizationService::class, function (Container $container) {
            $config = $container->get('config');
            $performanceConfig = $config['performance'] ?? [];
            $databaseConfig = $performanceConfig['database'] ?? [];
            
            return new DatabaseOptimizationService($databaseConfig);
        });

        $container->alias('database.optimization', DatabaseOptimizationService::class);
    }

    /**
     * Register memory optimization service
     */
    private function registerMemoryOptimizationService(Container $container): void
    {
        $container->bind(MemoryOptimizationService::class, function (Container $container) {
            $config = $container->get('config');
            $performanceConfig = $config['performance'] ?? [];
            $memoryConfig = $performanceConfig['memory'] ?? [];
            
            return new MemoryOptimizationService($memoryConfig);
        });

        $container->alias('memory.optimization', MemoryOptimizationService::class);
    }

    /**
     * Register asset optimization service
     */
    private function registerAssetOptimizationService(Container $container): void
    {
        $container->bind(AssetOptimizationService::class, function (Container $container) {
            $config = $container->get('config');
            $performanceConfig = $config['performance'] ?? [];
            $assetConfig = $performanceConfig['assets'] ?? [];
            
            return new AssetOptimizationService($assetConfig);
        });

        $container->alias('asset.optimization', AssetOptimizationService::class);
    }

    /**
     * Register performance monitoring service
     */
    private function registerPerformanceMonitoringService(Container $container): void
    {
        $container->bind(PerformanceMonitoringService::class, function (Container $container) {
            $config = $container->get('config');
            $performanceConfig = $config['performance'] ?? [];
            $monitoringConfig = $performanceConfig['monitoring'] ?? [];
            
            return new PerformanceMonitoringService($monitoringConfig);
        });

        $container->alias('performance.monitoring', PerformanceMonitoringService::class);
    }

    /**
     * Boot performance services
     * 
     * Initialize services that need to be started immediately.
     */
    public function boot(Container $container): void
    {
        $config = $container->get('config');
        $performanceConfig = $config['performance'] ?? [];
        $environment = $config['app']['env'] ?? 'production';
        $envConfig = $performanceConfig['environment'][$environment] ?? [];

        // Initialize memory optimization if enabled
        if ($envConfig['enable_memory_optimization'] ?? false) {
            $memoryService = $container->get(MemoryOptimizationService::class);
            $memoryService->optimizeMemory();
        }

        // Initialize performance monitoring if enabled
        if ($envConfig['enable_monitoring'] ?? false) {
            $monitoringService = $container->get(PerformanceMonitoringService::class);
            // Start monitoring background processes if needed
        }

        // Warm up cache if enabled
        if ($performanceConfig['caching']['warming']['enabled'] ?? false) {
            $cacheService = $container->get(CacheInterface::class);
            $preloadData = $performanceConfig['caching']['warming']['preload_data'] ?? [];
            
            foreach ($preloadData as $key => $value) {
                $cacheService->warmUp($key, $value);
            }
        }
    }

    /**
     * Get service dependencies
     */
    public function provides(): array
    {
        return [
            CacheInterface::class,
            CacheService::class,
            DatabaseOptimizationService::class,
            MemoryOptimizationService::class,
            AssetOptimizationService::class,
            PerformanceMonitoringService::class,
            'cache',
            'database.optimization',
            'memory.optimization',
            'asset.optimization',
            'performance.monitoring',
        ];
    }
}