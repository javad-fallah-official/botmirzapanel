<?php

declare(strict_types=1);

namespace BotMirzaPanel\Infrastructure\External;

use BotMirzaPanel\Infrastructure\Container\ServiceContainer;
use BotMirzaPanel\Infrastructure\External\Payment\PaymentGatewayInterface;
use BotMirzaPanel\Infrastructure\External\Payment\NowPaymentsGateway;
use BotMirzaPanel\Infrastructure\External\Panel\PanelAdapterInterface;
use BotMirzaPanel\Infrastructure\External\Panel\MarzbanAdapter;
use BotMirzaPanel\Infrastructure\External\Telegram\TelegramServiceInterface;
use BotMirzaPanel\Infrastructure\External\Telegram\TelegramService;
use BotMirzaPanel\Infrastructure\External\Notifications\NotificationServiceInterface;
use BotMirzaPanel\Infrastructure\External\Notifications\NotificationService;
use BotMirzaPanel\Config\ConfigManager;

/**
 * External Service Provider
 * 
 * Registers all external service implementations
 */
class ExternalServiceProvider
{
    private ServiceContainer $container;

    public function __construct(ServiceContainer $container): void
    {
        $this->container = $container;
    }

    /**
     * Register all external services
     */
    public function register(): void
    {
        $this->registerPaymentGateways();
        $this->registerPanelAdapters();
        $this->registerTelegramService();
        $this->registerNotificationService();
    }

    /**
     * Register payment gateway services
     */
    private function registerPaymentGateways(): void
    {
        // Register NowPayments gateway
        $this->container->bind(PaymentGatewayInterface::class, function () {
            return new NowPaymentsGateway(
                $this->container->get(ConfigManager::class)
            );
        });

        // Register gateway by name for factory pattern
        $this->container->bind('payment.gateway.nowpayments', function () {
            return new NowPaymentsGateway(
                $this->container->get(ConfigManager::class)
            );
        });

        // Register payment gateway factory
        $this->container->singleton('payment.gateway.factory', function () {
            return new PaymentGatewayFactory($this->container);
        });
    }

    /**
     * Register panel adapter services
     */
    private function registerPanelAdapters(): void
    {
        // Register Marzban adapter
        $this->container->bind(PanelAdapterInterface::class, function () {
            return new MarzbanAdapter(
                $this->container->get(ConfigManager::class)
            );
        });

        // Register adapter by name for factory pattern
        $this->container->bind('panel.adapter.marzban', function () {
            return new MarzbanAdapter(
                $this->container->get(ConfigManager::class)
            );
        });

        // Register panel adapter factory
        $this->container->singleton('panel.adapter.factory', function () {
            return new PanelAdapterFactory($this->container);
        });
    }

    /**
     * Register Telegram service
     */
    private function registerTelegramService(): void
    {
        $this->container->bind(TelegramServiceInterface::class, function () {
            return new TelegramService(
                $this->container->get(ConfigManager::class)
            );
        });

        $this->container->bind('telegram.service', function () {
            return $this->container->get(TelegramServiceInterface::class);
        });
    }

    /**
     * Register notification service
     */
    private function registerNotificationService(): void
    {
        $this->container->bind(NotificationServiceInterface::class, function () {
            return new NotificationService(
                $this->container->get(ConfigManager::class),
                $this->container->get(TelegramServiceInterface::class)
            );
        });

        $this->container->bind('notification.service', function () {
            return $this->container->get(NotificationServiceInterface::class);
        });
    }
}

/**
 * Payment Gateway Factory
 */
class PaymentGatewayFactory
{
    private ServiceContainer $container;
    private array $gateways = [
        'nowpayments' => 'payment.gateway.nowpayments'
    ];

    public function __construct(ServiceContainer $container): void
    {
        $this->container = $container;
    }

    public function create(string $gatewayName): PaymentGatewayInterface
    {
        if (!isset($this->gateways[$gatewayName])) {
            throw new \InvalidArgumentException("Unknown payment gateway: {$gatewayName}");
        }

        return $this->container->get($this->gateways[$gatewayName]);
    }

    public function getAvailableGateways(): array
    {
        return array_keys($this->gateways);
    }

    public function registerGateway(string $name, string $serviceId): void
    {
        $this->gateways[$name] = $serviceId;
    }
}

/**
 * Panel Adapter Factory
 */
class PanelAdapterFactory
{
    private ServiceContainer $container;
    private array $adapters = [
        'marzban' => 'panel.adapter.marzban'
    ];

    public function __construct(ServiceContainer $container): void
    {
        $this->container = $container;
    }

    public function create(string $adapterName): PanelAdapterInterface
    {
        if (!isset($this->adapters[$adapterName])) {
            throw new \InvalidArgumentException("Unknown panel adapter: {$adapterName}");
        }

        return $this->container->get($this->adapters[$adapterName]);
    }

    public function getAvailableAdapters(): array
    {
        return array_keys($this->adapters);
    }

    public function registerAdapter(string $name, string $serviceId): void
    {
        $this->adapters[$name] = $serviceId;
    }
}