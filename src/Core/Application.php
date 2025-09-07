<?php

declare(strict_types=1);

namespace BotMirzaPanel\Core;

use BotMirzaPanel\Config\ConfigManager;
use BotMirzaPanel\Database\DatabaseManager;
use BotMirzaPanel\Telegram\TelegramBot;
use BotMirzaPanel\Payment\PaymentService;
use BotMirzaPanel\Panel\PanelService;
use BotMirzaPanel\User\UserService;
use BotMirzaPanel\Cron\CronService;
use BotMirzaPanel\Shared\Contracts\ContainerInterface;

/**
 * Main application class that orchestrates all modules
 * Implements dependency injection and service container pattern
 */
class Application
{
    private ConfigManager $config;
    private ?DatabaseManager $database = null;
    private ?TelegramBot $telegram = null;
    private ?PaymentService $payment = null;
    private ?PanelService $panel = null;
    private ?UserService $user = null;
    private ?CronService $cron = null;
    private array $services = [];
    // Store reference to container for lazy/fallback resolution
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->initializeServices();
    }

    /**
     * Initialize all application services with proper dependency injection
     */
    private function initializeServices(): void
    {
        // Resolve only lightweight services eagerly
        $this->config = $this->container->get('config');

        // Defer heavy services to first use via getService
        $this->services = [
            'config' => $this->config,
        ];
    }

    /**
     * Get service from container
     */
    public function getService(string $name): object
    {
        if (isset($this->services[$name])) {
            return $this->services[$name];
        }

        // Lazy resolve and cache
        $service = $this->container->get($name);
        $this->services[$name] = $service;
        return $service;
    }

    /**
     * Handle incoming Telegram webhook
     */
    public function handleWebhook(array $update): void
    {
        try {
            /** @var TelegramBot $telegram */
            $telegram = $this->getService('telegram');
            $telegram->processUpdate($update, $this->services);
        } catch (\Exception $e) {
            error_log("Webhook error: " . $e->getMessage());
            // Log to admin if configured
            if ($this->config->get('admin.error_reporting', false)) {
                /** @var TelegramBot $telegram */
                $telegram = $this->getService('telegram');
                $telegram->sendMessage(
                    $this->config->get('admin.id'),
                    "Error: " . $e->getMessage()
                );
            }
        }
    }

    /**
     * Run cron jobs
     */
    public function runCron(?string $jobName = null): void
    {
        /** @var CronService $cron */
        $cron = $this->getService('cron');
        $cron->run($jobName);
    }

    /**
     * Initialize database tables
     */
    public function initializeDatabase(): void
    {
        /** @var DatabaseManager $db */
        $db = $this->getService('database');
        $db->initializeTables();
    }

    /**
     * Get application version
     */
    public function getVersion(): string
    {
        return '2.0.0-refactored';
    }
}