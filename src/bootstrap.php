<?php

declare(strict_types=1);

/**
 * Bootstrap file for the modular BotMirzaPanel application
 * This file initializes all modules with proper dependency injection
 */

// Prevent direct access
if (!defined('BOTMIRZAPANEL_INIT')) {
    die('Direct access not allowed');
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('Asia/Tehran');

// Define constants
define('BOTMIRZAPANEL_ROOT', dirname(__DIR__));
define('BOTMIRZAPANEL_SRC', __DIR__);
define('BOTMIRZAPANEL_CONFIG', BOTMIRZAPANEL_ROOT . '/config');
define('BOTMIRZAPANEL_LOGS', BOTMIRZAPANEL_ROOT . '/logs');
define('BOTMIRZAPANEL_CACHE', BOTMIRZAPANEL_ROOT . '/cache');

// Create necessary directories
$directories = [
    BOTMIRZAPANEL_LOGS,
    BOTMIRZAPANEL_CACHE,
    BOTMIRZAPANEL_ROOT . '/uploads',
    BOTMIRZAPANEL_ROOT . '/backups'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// PSR-4 Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'BotMirzaPanel\\';
    $baseDir = BOTMIRZAPANEL_SRC . '/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Load legacy functions for backward compatibility
if (file_exists(BOTMIRZAPANEL_ROOT . '/functions.php')) {
    require_once BOTMIRZAPANEL_ROOT . '/functions.php';
}

// Import required classes
use BotMirzaPanel\Core\Application;
use BotMirzaPanel\Infrastructure\Providers\InfrastructureServiceProvider;
use BotMirzaPanel\Infrastructure\Providers\ApplicationServiceProvider;
use BotMirzaPanel\Infrastructure\Providers\DomainServiceProvider;
use BotMirzaPanel\Infrastructure\Providers\PerformanceServiceProvider;
use BotMirzaPanel\Database\DatabaseManager;
use BotMirzaPanel\Config\ConfigManager;
// use BotMirzaPanel\Infrastructure\Container\ServiceContainer;
use BotMirzaPanel\Telegram\TelegramBot;
use BotMirzaPanel\Payment\PaymentService;
use BotMirzaPanel\Panel\PanelService;
use BotMirzaPanel\User\UserService;
// use BotMirzaPanel\Infrastructure\Adapters\LegacyUserServiceAdapter;
use BotMirzaPanel\Cron\CronService;

/**
 * Initialize configuration and database
 */
$configFile = BOTMIRZAPANEL_ROOT . '/config.php';
if (!file_exists($configFile)) {
    throw new Exception('Configuration file not found: ' . $configFile);
}

// Config and DB will be resolved via container providers; avoid manual instantiation here.
// $configData = require $configFile;
// $configManager = new ConfigManager($configData);

// $databaseManager = new DatabaseManager(
//     $configManager->get('database.host', 'localhost'),
//     $configManager->get('database.username', 'root'),
//     $configManager->get('database.password', ''),
//     $configManager->get('database.database', 'botmirzapanel'),
//     $configManager->get('database.port', 3306)
// );

/**
 * Initialize the new service container for domain-driven architecture
 */
// Legacy ServiceContainer usage removed; using unified Container with providers
// $serviceContainer = new ServiceContainer($configManager, $databaseManager);

/**
 * Initialize the legacy DI container for backward compatibility
 */
$container = new \BotMirzaPanel\Infrastructure\Container\Container();

// Register service providers
$providers = [
    new InfrastructureServiceProvider(),
    new ApplicationServiceProvider(),
    new DomainServiceProvider(),
    new PerformanceServiceProvider(),
];

foreach ($providers as $provider) {
    $container->registerProvider($provider);
}

// Boot service providers (after all registrations)
foreach ($providers as $provider) {
    $provider->boot($container);
}

// Register Application
$container->singleton('app', function($c) {
    return new Application($c);
});

/**
 * Global helper functions for accessing services
 */
function app(): Application
{
    global $container;
    return $container->get('app');
}

/**
 * Get configuration value or configuration manager
 * 
 * @param string|null $key Configuration key
 * @param mixed $default Default value if key not found
 * @return mixed Configuration value or configuration manager
 */
function config(string $key = null, $default = null): mixed
{
    global $container;
    $configManager = $container->get('config');
    
    if ($key === null) {
        return $configManager;
    }
    
    return $configManager->get($key, $default);
}

function db(): DatabaseManager
{
    global $container;
    return $container->get('database');
}

function telegram(): TelegramBot
{
    global $container;
    return $container->get('telegram');
}

function userService(): UserService
{
    global $container;
    return $container->get('user');
}

function panelService(): PanelService
{
    global $container;
    return $container->get('panel');
}

function paymentService(): PaymentService
{
    global $container;
    return $container->get('payment');
}

function cronService(): CronService
{
    global $container;
    return $container->get('cron');
}

/**
 * Error handling
 */
function handleError(\Throwable $e): void
{
    $errorMessage = sprintf(
        "[%s] %s in %s:%d\nStack trace:\n%s",
        date('Y-m-d H:i:s'),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );
    
    // Log error
    error_log($errorMessage, 3, BOTMIRZAPANEL_LOGS . '/error.log');
    
    // In development, show detailed error
    if (config('app.debug', false)) {
        echo "<pre>{$errorMessage}</pre>";
    } else {
        // In production, show generic error
        echo "An error occurred. Please try again later.";
    }
}

// Set global error handler
set_exception_handler('handleError');
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new \ErrorException($message, 0, $severity, $file, $line);
});

/**
 * Backward compatibility functions
 */
/**
 * Get configuration value (backward compatibility)
 * 
 * @param string $key Configuration key
 * @param mixed $default Default value if key not found
 * @return mixed Configuration value
 */
function getConfig($key, $default = null): mixed
{
    return config($key, $default);
}

/**
 * Get database manager (backward compatibility)
 * 
 * @return DatabaseManager Database manager instance
 */
function getDatabase(): DatabaseManager
{
    return db();
}

/**
 * Get Telegram bot instance (backward compatibility)
 * 
 * @return TelegramBot Telegram bot instance
 */
function getTelegram(): TelegramBot
{
    return telegram();
}

/**
 * Initialize the application
 */
try {
    // Load configuration
    $config = $container->get('config');
    
    // Test database connection
    // $database = $container->get('database');
    
    // Initialize application
    $app = $container->get('app');
    
    // Log successful initialization
    error_log(
        "[" . date('Y-m-d H:i:s') . "] BotMirzaPanel initialized successfully",
        3,
        BOTMIRZAPANEL_LOGS . '/app.log'
    );
    
} catch (\Throwable $e) {
    handleError($e);
    exit(1);
}

/**
 * Make services globally available for legacy code
 */
$GLOBALS['container'] = $container;
$GLOBALS['config'] = $container->get('config');
// $GLOBALS['database'] = $container->get('database');
// $GLOBALS['telegram'] = $container->get('telegram');
// $GLOBALS['userService'] = $container->get('user');
// $GLOBALS['panelService'] = $container->get('panel');
// $GLOBALS['paymentService'] = $container->get('payment');
// $GLOBALS['cronService'] = $container->get('cron');
$GLOBALS['app'] = $container->get('app');

// Mark initialization as complete
define('BOTMIRZAPANEL_INITIALIZED', true);

/**
 * Optional: Auto-migrate database if needed
 */
if (config('database.auto_migrate', false)) {
    try {
        // Run any pending migrations
        // This would be implemented based on your migration system
    } catch (\Exception $e) {
        error_log("Migration failed: " . $e->getMessage());
    }
}

/**
 * Optional: Register shutdown function for cleanup
 */
register_shutdown_function(function() {
    // Cleanup tasks
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
    
    // Log shutdown
    error_log(
        "[" . date('Y-m-d H:i:s') . "] BotMirzaPanel shutdown",
        3,
        BOTMIRZAPANEL_LOGS . '/app.log'
    );
});

return $container;