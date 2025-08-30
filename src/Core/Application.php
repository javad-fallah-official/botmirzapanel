<?php

namespace BotMirzaPanel\Core;

use BotMirzaPanel\Config\ConfigManager;
use BotMirzaPanel\Database\DatabaseManager;
use BotMirzaPanel\Telegram\TelegramBot;
use BotMirzaPanel\Payment\PaymentService;
use BotMirzaPanel\Panel\PanelService;
use BotMirzaPanel\User\UserService;
use BotMirzaPanel\Cron\CronService;

/**
 * Main application class that orchestrates all modules
 * Implements dependency injection and service container pattern
 */
class Application
{
    private ConfigManager $config;
    private DatabaseManager $database;
    private TelegramBot $telegram;
    private PaymentService $payment;
    private PanelService $panel;
    private UserService $user;
    private CronService $cron;
    private array $services = [];
    // Store reference to container for lazy/fallback resolution
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
        $this->initializeServices();
    }

    /**
     * Initialize all application services with proper dependency injection
     */
    private function initializeServices(): void
    {
        // Resolve from container instead of manual instantiation
        $this->config = $this->container->get('config');
        $this->database = $this->container->get('database');
        $this->telegram = $this->container->get('telegram');
        $this->payment = $this->container->get('payment');
        $this->panel = $this->container->get('panel');
        $this->user = $this->container->get('user');
        $this->cron = $this->container->get('cron');

        // Register services map for easy access
        $this->services = [
            'config' => $this->config,
            'database' => $this->database,
            'telegram' => $this->telegram,
            'payment' => $this->payment,
            'panel' => $this->panel,
            'user' => $this->user,
            'cron' => $this->cron,
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

        // Fallback to container lookup for lazily registered services
        return $this->container->get($name);
    }

    /**
     * Handle incoming Telegram webhook
     */
    public function handleWebhook(array $update): void
    {
        try {
            $this->telegram->processUpdate($update, $this->services);
        } catch (\Exception $e) {
            error_log("Webhook error: " . $e->getMessage());
            // Log to admin if configured
            if ($this->config->get('admin.error_reporting', false)) {
                $this->telegram->sendMessage(
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
        $this->cron->run($jobName);
    }

    /**
     * Initialize database tables
     */
    public function initializeDatabase(): void
    {
        $this->database->initializeTables();
    }

    /**
     * Get application version
     */
    public function getVersion(): string
    {
        return '2.0.0-refactored';
    }
}