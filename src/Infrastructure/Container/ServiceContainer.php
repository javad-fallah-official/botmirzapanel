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
use BotMirzaPanel\Domain\Services\Payment\PaymentService as DomainPaymentService;
use BotMirzaPanel\Domain\Services\Panel\PanelService as DomainPanelService;
use BotMirzaPanel\Domain\Services\Subscription\SubscriptionService;

// Infrastructure
use BotMirzaPanel\Infrastructure\Repositories\UserRepository;
use BotMirzaPanel\Infrastructure\Database\UserEntityMapper;
use BotMirzaPanel\Infrastructure\Adapters\LegacyUserServiceAdapter;
use BotMirzaPanel\Domain\Repositories\PaymentRepositoryInterface;
use BotMirzaPanel\Domain\Repositories\PanelRepositoryInterface;
use BotMirzaPanel\Domain\Repositories\SubscriptionRepositoryInterface;
use BotMirzaPanel\Infrastructure\Repositories\PaymentRepository;
use BotMirzaPanel\Infrastructure\Repositories\PanelRepository;
use BotMirzaPanel\Infrastructure\Repositories\SubscriptionRepository;

// Application Layer
use BotMirzaPanel\Application\Commands\User\CreateUserCommandHandler;
use BotMirzaPanel\Application\Queries\User\GetUserByIdQueryHandler;
use BotMirzaPanel\Application\Commands\Payment\CreatePaymentCommandHandler;
use BotMirzaPanel\Application\Commands\Payment\UpdatePaymentCommandHandler;
use BotMirzaPanel\Application\Queries\Payment\GetPaymentByIdQueryHandler;
use BotMirzaPanel\Application\Queries\Payment\GetPaymentsQueryHandler;
use BotMirzaPanel\Application\Commands\Panel\CreatePanelCommandHandler;
use BotMirzaPanel\Application\Commands\Panel\UpdatePanelCommandHandler;
use BotMirzaPanel\Application\Queries\Panel\GetPanelByIdQueryHandler;
use BotMirzaPanel\Application\Queries\Panel\GetPanelsQueryHandler;
use BotMirzaPanel\Application\Commands\Subscription\CreateSubscriptionCommandHandler;
use BotMirzaPanel\Application\Commands\Subscription\UpdateSubscriptionCommandHandler;
use BotMirzaPanel\Application\Queries\Subscription\GetSubscriptionByIdQueryHandler;
use BotMirzaPanel\Application\Queries\Subscription\GetSubscriptionsQueryHandler;

// Events
use BotMirzaPanel\Domain\Events\EventDispatcher;
use BotMirzaPanel\Infrastructure\Events\InMemoryEventDispatcher;
use BotMirzaPanel\Infrastructure\Events\EventServiceProvider;

// External Services
use BotMirzaPanel\Infrastructure\External\ExternalServiceProvider;

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
    
    public function __construct()
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
        
        // Repository implementations
        $this->singleton(PaymentRepositoryInterface::class, fn() => new PaymentRepository(
            $this->get(DatabaseManager::class)
        ));
        $this->singleton(PanelRepositoryInterface::class, fn() => new PanelRepository(
            $this->get(DatabaseManager::class)
        ));
        $this->singleton(SubscriptionRepositoryInterface::class, fn() => new SubscriptionRepository(
            $this->get(DatabaseManager::class)
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
        
        // Domain services for other entities
        $this->singleton(DomainPaymentService::class, fn() => new DomainPaymentService(
            $this->get(PaymentRepositoryInterface::class)
        ));
        $this->singleton(DomainPanelService::class, fn() => new DomainPanelService(
            $this->get(PanelRepositoryInterface::class)
        ));
        $this->singleton(SubscriptionService::class, fn() => new SubscriptionService(
            $this->get(SubscriptionRepositoryInterface::class)
        ));
        
        // Application layer - User
        $this->singleton(CreateUserCommandHandler::class, fn() => new CreateUserCommandHandler(
            $this->get(UserService::class)
        ));
        $this->singleton(GetUserByIdQueryHandler::class, fn() => new GetUserByIdQueryHandler(
            $this->get(UserRepository::class)
        ));
        
        // Application layer - Payment
        $this->singleton(CreatePaymentCommandHandler::class, fn() => new CreatePaymentCommandHandler(
            $this->get(PaymentRepositoryInterface::class),
            $this->get(DomainPaymentService::class)
        ));
        $this->singleton(UpdatePaymentCommandHandler::class, fn() => new UpdatePaymentCommandHandler(
            $this->get(PaymentRepositoryInterface::class),
            $this->get(DomainPaymentService::class)
        ));
        $this->singleton(GetPaymentByIdQueryHandler::class, fn() => new GetPaymentByIdQueryHandler(
            $this->get(PaymentRepositoryInterface::class)
        ));
        $this->singleton(GetPaymentsQueryHandler::class, fn() => new GetPaymentsQueryHandler(
            $this->get(PaymentRepositoryInterface::class)
        ));
        
        // Application layer - Panel
        $this->singleton(CreatePanelCommandHandler::class, fn() => new CreatePanelCommandHandler(
            $this->get(PanelRepositoryInterface::class),
            $this->get(DomainPanelService::class)
        ));
        $this->singleton(UpdatePanelCommandHandler::class, fn() => new UpdatePanelCommandHandler(
            $this->get(PanelRepositoryInterface::class),
            $this->get(DomainPanelService::class)
        ));
        $this->singleton(GetPanelByIdQueryHandler::class, fn() => new GetPanelByIdQueryHandler(
            $this->get(PanelRepositoryInterface::class)
        ));
        $this->singleton(GetPanelsQueryHandler::class, fn() => new GetPanelsQueryHandler(
            $this->get(PanelRepositoryInterface::class)
        ));
        
        // Application layer - Subscription
        $this->singleton(CreateSubscriptionCommandHandler::class, fn() => new CreateSubscriptionCommandHandler(
            $this->get(SubscriptionRepositoryInterface::class),
            $this->get(SubscriptionService::class)
        ));
        $this->singleton(UpdateSubscriptionCommandHandler::class, fn() => new UpdateSubscriptionCommandHandler(
            $this->get(SubscriptionRepositoryInterface::class),
            $this->get(SubscriptionService::class)
        ));
        $this->singleton(GetSubscriptionByIdQueryHandler::class, fn() => new GetSubscriptionByIdQueryHandler(
            $this->get(SubscriptionRepositoryInterface::class)
        ));
        $this->singleton(GetSubscriptionsQueryHandler::class, fn() => new GetSubscriptionsQueryHandler(
            $this->get(SubscriptionRepositoryInterface::class)
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
        
        // External services
        $this->singleton(ExternalServiceProvider::class, fn() => new ExternalServiceProvider($this));
        $this->get(ExternalServiceProvider::class)->register();
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
     * 
     * @param string $abstract Service identifier
     * @return mixed Service instance
     * @throws \InvalidArgumentException When service is not found
     */
    public function get(string $abstract): mixed
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