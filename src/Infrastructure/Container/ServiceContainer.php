<?php

declare(strict_types=1);

namespace BotMirzaPanel\Infrastructure\Container;

use BotMirzaPanel\Config\ConfigManager;
use BotMirzaPanel\Database\DatabaseManager;

// Legacy Services
use BotMirzaPanel\User\UserService as LegacyUserService;
use BotMirzaPanel\Payment\PaymentService;
use BotMirzaPanel\Panel\PanelService;
use BotMirzaPanel\Telegram\TelegramBot;

// Domain Services
use BotMirzaPanel\Domain\Services\User\UserService;
use BotMirzaPanel\Domain\Services\User\UserValidationService;
use BotMirzaPanel\Domain\Services\User\UserSecurityService;

// Infrastructure
use BotMirzaPanel\Infrastructure\Repositories\UserRepository;
use BotMirzaPanel\Infrastructure\Database\UserEntityMapper;
use BotMirzaPanel\Infrastructure\Adapters\LegacyUserServiceAdapter;

// Application Layer
use BotMirzaPanel\Application\Commands\User\CreateUserCommandHandler;
use BotMirzaPanel\Application\Queries\User\GetUserByIdQueryHandler;

// Events
use BotMirzaPanel\Domain\Events\EventDispatcher;
use BotMirzaPanel\Infrastructure\Events\InMemoryEventDispatcher;
use BotMirzaPanel\Infrastructure\Events\EventServiceProvider;

/**
 * Service container for dependency injection and service management
 * Handles both legacy and new domain-driven services during migration
 */
class ServiceContainer
{
    private array $services = [];
    private array $singletons = [];
    private ConfigManager $config;
    private DatabaseManager $db;
    
    public function __construct(ConfigManager $config, DatabaseManager $db)
    {
        $this->config = $config;
        $this->db = $db;
        $this->registerServices();
    }
    
    /**
     * Register all services
     */
    private function registerServices(): void
    {
        // Core services
        $this->singleton(ConfigManager::class, fn() => $this->config);
        $this->singleton(DatabaseManager::class, fn() => $this->db);
        
        // Infrastructure services
        $this->singleton(UserEntityMapper::class, fn() => new UserEntityMapper());
        $this->singleton(UserRepository::class, fn() => new UserRepository(
            $this->get(DatabaseManager::class),
            $this->get(UserEntityMapper::class)
        ));
        
        // Domain services
        $this->singleton(UserValidationService::class, fn() => new UserValidationService(
            $this->get(UserRepository::class)
        ));
        $this->singleton(UserSecurityService::class, fn() => new UserSecurityService());
        $this->singleton(UserService::class, fn() => new UserService(
            $this->get(UserRepository::class),
            $this->get(UserValidationService::class),
            $this->get(UserSecurityService::class)
        ));
        
        // Application layer
        $this->singleton(CreateUserCommandHandler::class, fn() => new CreateUserCommandHandler(
            $this->get(UserService::class)
        ));
        $this->singleton(GetUserByIdQueryHandler::class, fn() => new GetUserByIdQueryHandler(
            $this->get(UserRepository::class)
        ));
        
        // Legacy services
        $this->singleton(LegacyUserService::class, fn() => new LegacyUserService(
            $this->get(ConfigManager::class),
            $this->get(DatabaseManager::class)
        ));
        $this->singleton(PaymentService::class, fn() => new PaymentService(
            $this->get(ConfigManager::class),
            $this->get(DatabaseManager::class)
        ));
        $this->singleton(PanelService::class, fn() => new PanelService(
            $this->get(ConfigManager::class),
            $this->get(DatabaseManager::class)
        ));
        $this->singleton(TelegramBot::class, fn() => new TelegramBot(
            $this->get(ConfigManager::class)
        ));
        
        // Migration adapters
        $this->singleton(LegacyUserServiceAdapter::class, fn() => new LegacyUserServiceAdapter(
            $this->get(LegacyUserService::class),
            $this->get(UserService::class),
            $this->get(CreateUserCommandHandler::class),
            $this->get(GetUserByIdQueryHandler::class)
        ));
        
        // Event system
        $this->singleton(EventDispatcher::class, fn() => new InMemoryEventDispatcher());
        $this->singleton(InMemoryEventDispatcher::class, fn() => $this->get(EventDispatcher::class));
        $this->singleton(EventServiceProvider::class, fn() => new EventServiceProvider(
            $this->get(EventDispatcher::class)
        ));
        
        // Initialize event listeners
        $this->get(EventServiceProvider::class)->register();
    }
    
    /**
     * Register a service factory
     */
    public function bind(string $abstract, callable $factory): void
    {
        $this->services[$abstract] = $factory;
    }
    
    /**
     * Register a singleton service
     */
    public function singleton(string $abstract, callable $factory): void
    {
        $this->services[$abstract] = $factory;
        $this->singletons[$abstract] = true;
    }
    
    /**
     * Get a service instance
     */
    public function get(string $abstract)
    {
        if (!isset($this->services[$abstract])) {
            throw new \InvalidArgumentException("Service {$abstract} not found");
        }
        
        // Return singleton instance if already created
        if (isset($this->singletons[$abstract]) && isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        
        // Create new instance
        $instance = $this->services[$abstract]();
        
        // Store singleton instance
        if (isset($this->singletons[$abstract])) {
            $this->instances[$abstract] = $instance;
        }
        
        return $instance;
    }
    
    /**
     * Check if service is registered
     */
    public function has(string $abstract): bool
    {
        return isset($this->services[$abstract]);
    }
    
    /**
     * Get the legacy user service adapter for backward compatibility
     */
    public function getUserService(): LegacyUserServiceAdapter
    {
        return $this->get(LegacyUserServiceAdapter::class);
    }
    
    /**
     * Get payment service
     */
    public function getPaymentService(): PaymentService
    {
        return $this->get(PaymentService::class);
    }
    
    /**
     * Get panel service
     */
    public function getPanelService(): PanelService
    {
        return $this->get(PanelService::class);
    }
    
    /**
     * Get telegram bot
     */
    public function getTelegramBot(): TelegramBot
    {
        return $this->get(TelegramBot::class);
    }
    
    private array $instances = [];
}