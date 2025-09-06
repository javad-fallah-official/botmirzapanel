<?php
// WARNING: SQL injection vulnerability detected in this file
// Please review and use prepared statements or secure_* functions


declare(strict_types=1);

namespace BotMirzaPanel\Infrastructure\Providers;

use BotMirzaPanel\Infrastructure\Container\AbstractServiceProvider;
use BotMirzaPanel\Shared\Contracts\ContainerInterface;
use BotMirzaPanel\Database\DatabaseManager;
use BotMirzaPanel\Config\ConfigManager;
use BotMirzaPanel\Infrastructure\Services\CacheService;
use BotMirzaPanel\Shared\Contracts\CacheInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Redis;
use BotMirzaPanel\Domain\Repositories\UserRepositoryInterface;
use BotMirzaPanel\Domain\Repositories\PaymentRepositoryInterface;
use BotMirzaPanel\Infrastructure\Repositories\UserRepository;
use BotMirzaPanel\Infrastructure\Repositories\PaymentRepository;

/**
 * Infrastructure Service Provider
 * Registers infrastructure services like database, config, cache, logging
 */
class InfrastructureServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [
        DatabaseManager::class,
        ConfigManager::class,
        CacheService::class,
        CacheInterface::class,
        LoggerInterface::class,
        UserRepositoryInterface::class,
        PaymentRepositoryInterface::class,
        'config',
        'cache',
        'logger',
    ];

    public function register(ContainerInterface $container): void
    {
        $this->registerConfig($container);
        $this->registerDatabase($container);
        $this->registerLogger($container);
        $this->registerCache($container);
        $this->registerRepositories($container);
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

        // Alias for easy access
        $this->alias($container, 'config', ConfigManager::class);
    }

    private function registerDatabase(ContainerInterface $container): void
    {
        // Database Manager
        $this->singleton(
            $container,
            DatabaseManager::class,
            function (ContainerInterface $c) {
                return new DatabaseManager(
                    $c->get(ConfigManager::class)
                );
            }
        );

        // Alias for convenience
        $this->alias($container, 'database', DatabaseManager::class);
    }

    private function registerLogger(ContainerInterface $container): void
    {
        // Bind PSR logger to NullLogger by default
        $this->singleton(
            $container,
            LoggerInterface::class,
            function () {
                return new NullLogger();
            }
        );

        // Alias for convenience
        $this->alias($container, 'logger', LoggerInterface::class);
    }

    private function registerCache(ContainerInterface $container): void
    {
        // Redis connection (lazy singleton)
        $container->register(
            Redis::class,
            function () use ($container) {
                $redis = new Redis();

                // Attempt connection using config if available
                try {
                    /** @var ConfigManager $config */
                    $config = $container->get(ConfigManager::class);
                    $host = $config->get('cache.redis.host', '127.0.0.1');
                    $port = (int) $config->get('cache.redis.port', 6379);
                    $timeout = (float) ($config->get('cache.redis.timeout', 1.5));
                    $db = (int) $config->get('cache.redis.db', 0);
                    $password = $config->get('cache.redis.password');

                    $redis->connect($host, $port, $timeout);
                    if (!empty($password)) {
                        $redis->auth($password);
                    }
                    if ($db > 0) {
                        $redis->select($db);
                    }
                } catch (\Throwable $e) {
                    // Swallow connection errors; CacheService will handle and logger will record via NullLogger
                }

                return $redis;
            },
            true
        );

        // Cache Service
        $this->singleton(
            $container,
            CacheService::class,
            function (ContainerInterface $c) {
                /** @var ConfigManager $config */
                $config = $c->get(ConfigManager::class);
                $cacheConfig = (array) $config->get('cache', []);

                return new CacheService(
                    $c->get(Redis::class),
                    $c->get(LoggerInterface::class),
                    $cacheConfig
                );
            }
        );

        // Bind CacheInterface to CacheService and provide common aliases
        $this->alias($container, CacheInterface::class, CacheService::class);
        $this->alias($container, 'cache', CacheInterface::class);
    }

    private function registerRepositories(ContainerInterface $container): void
    {
        // Bind domain repository interfaces to concrete infrastructure implementations
        $this->singleton(
            $container,
            UserRepositoryInterface::class,
            function (ContainerInterface $c) {
                return new UserRepository(
                    $c->get(DatabaseManager::class)
                );
            }
        );

        $this->singleton(
            $container,
            PaymentRepositoryInterface::class,
            function (ContainerInterface $c) {
                return new PaymentRepository(
                    $c->get(DatabaseManager::class)
                );
            }
        );
    }

    public function boot(ContainerInterface $container): void
    {
        // Trigger initialization of core services if needed
        $container->get(DatabaseManager::class);
        $container->get(CacheService::class);
        $container->get(LoggerInterface::class);
    }
}