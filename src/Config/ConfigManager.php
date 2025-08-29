<?php

namespace BotMirzaPanel\Config;

/**
 * Configuration manager with environment-based settings
 * Handles all application configuration with proper validation
 */
class ConfigManager
{
    private array $config = [];
    private string $environment;

    public function __construct(string $configPath = null)
    {
        $this->environment = $_ENV['APP_ENV'] ?? 'production';
        $this->loadConfiguration($configPath);
    }

    /**
     * Load configuration from files and environment
     */
    private function loadConfiguration(string $configPath = null): void
    {
        $basePath = $configPath ?? dirname(__DIR__, 2);
        
        // Load legacy config.php for backward compatibility
        $legacyConfigPath = $basePath . '/config.php';
        if (file_exists($legacyConfigPath)) {
            $this->loadLegacyConfig($legacyConfigPath);
        }
        
        // Load environment-specific configuration
        $envConfigPath = $basePath . "/config/{$this->environment}.php";
        if (file_exists($envConfigPath)) {
            $envConfig = require $envConfigPath;
            $this->config = array_merge($this->config, $envConfig);
        }
        
        // Override with environment variables
        $this->loadEnvironmentVariables();
        
        // Validate required configuration
        $this->validateConfiguration();
    }

    /**
     * Load legacy config.php file
     */
    private function loadLegacyConfig(string $path): void
    {
        // Capture variables from legacy config
        ob_start();
        include $path;
        ob_end_clean();
        
        // Map legacy variables to new structure
        $this->config = [
            'database' => [
                'host' => $GLOBALS['servername'] ?? 'localhost',
                'name' => $GLOBALS['dbname'] ?? '',
                'username' => $GLOBALS['usernamedb'] ?? '',
                'password' => $GLOBALS['passworddb'] ?? '',
                'charset' => 'utf8mb4'
            ],
            'telegram' => [
                'api_key' => $GLOBALS['APIKEY'] ?? '',
                'username' => $GLOBALS['usernamebot'] ?? ''
            ],
            'admin' => [
                'id' => $GLOBALS['adminnumber'] ?? 0,
                'error_reporting' => true
            ],
            'app' => [
                'domain' => $GLOBALS['domainhosts'] ?? '',
                'timezone' => 'Asia/Tehran',
                'debug' => false
            ]
        ];
    }

    /**
     * Load configuration from environment variables
     */
    private function loadEnvironmentVariables(): void
    {
        $envMappings = [
            'DB_HOST' => 'database.host',
            'DB_NAME' => 'database.name',
            'DB_USERNAME' => 'database.username',
            'DB_PASSWORD' => 'database.password',
            'TELEGRAM_API_KEY' => 'telegram.api_key',
            'TELEGRAM_USERNAME' => 'telegram.username',
            'ADMIN_ID' => 'admin.id',
            'APP_DOMAIN' => 'app.domain',
            'APP_DEBUG' => 'app.debug',
            'NOWPAYMENTS_API_KEY' => 'payment.nowpayments.api_key',
            'AQAYEPARDAKHT_MERCHANT_ID' => 'payment.aqayepardakht.merchant_id'
        ];

        foreach ($envMappings as $envKey => $configKey) {
            $value = $_ENV[$envKey] ?? null;
            if ($value !== null) {
                $this->setNestedValue($configKey, $value);
            }
        }
    }

    /**
     * Set nested configuration value using dot notation
     */
    private function setNestedValue(string $key, $value): void
    {
        $keys = explode('.', $key);
        $current = &$this->config;
        
        foreach ($keys as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }
        
        $current = $value;
    }

    /**
     * Get configuration value using dot notation
     */
    public function get(string $key, $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }

    /**
     * Set configuration value
     */
    public function set(string $key, $value): void
    {
        $this->setNestedValue($key, $value);
    }

    /**
     * Get all configuration
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * Validate required configuration
     */
    private function validateConfiguration(): void
    {
        $required = [
            'database.host',
            'database.name',
            'database.username',
            'telegram.api_key',
            'admin.id'
        ];

        foreach ($required as $key) {
            if (empty($this->get($key))) {
                throw new \InvalidArgumentException("Required configuration '{$key}' is missing");
            }
        }
    }

    /**
     * Get current environment
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * Check if running in debug mode
     */
    public function isDebug(): bool
    {
        return (bool) $this->get('app.debug', false);
    }
}