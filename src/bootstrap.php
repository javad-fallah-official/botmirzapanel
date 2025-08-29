<?php

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
use BotMirzaPanel\Config\ConfigManager;
use BotMirzaPanel\Database\DatabaseManager;
use BotMirzaPanel\Telegram\TelegramBot;
use BotMirzaPanel\Payment\PaymentService;
use BotMirzaPanel\Panel\PanelService;
use BotMirzaPanel\User\UserService;
use BotMirzaPanel\Cron\CronService;

/**
 * Service Container for Dependency Injection
 */
class ServiceContainer
{
    private array $services = [];
    private array $singletons = [];
    
    /**
     * Register a service
     */
    public function register(string $name, callable $factory, bool $singleton = true): void
    {
        $this->services[$name] = $factory;
        if ($singleton) {
            $this->singletons[$name] = true;
        }
    }
    
    /**
     * Get a service
     */
    public function get(string $name): mixed
    {
        if (!isset($this->services[$name])) {
            throw new \Exception("Service '{$name}' not found");
        }
        
        // Return singleton instance if already created
        if (isset($this->singletons[$name]) && isset($this->instances[$name])) {
            return $this->instances[$name];
        }
        
        // Create new instance
        $instance = $this->services[$name]($this);
        
        // Store singleton instance
        if (isset($this->singletons[$name])) {
            $this->instances[$name] = $instance;
        }
        
        return $instance;
    }
    
    /**
     * Check if service exists
     */
    public function has(string $name): bool
    {
        return isset($this->services[$name]);
    }
    
    private array $instances = [];
}

/**
 * Initialize the service container
 */
$container = new ServiceContainer();

// Register ConfigManager
$container->register('config', function($container) {
    return new ConfigManager();
});

// Register DatabaseManager
$container->register('database', function($container) {
    return new DatabaseManager($container->get('config'));
});

// Register UserService
$container->register('user', function($container) {
    return new UserService(
        $container->get('config'),
        $container->get('database')
    );
});

// Register PanelService
$container->register('panel', function($container) {
    return new PanelService(
        $container->get('config'),
        $container->get('database')
    );
});

// Register PaymentService
$container->register('payment', function($container) {
    return new PaymentService(
        $container->get('config'),
        $container->get('database')
    );
});

// Register TelegramBot
$container->register('telegram', function($container) {
    return new TelegramBot(
        $container->get('config'),
        $container->get('user'),
        $container->get('panel'),
        $container->get('payment')
    );
});

// Register CronService
$container->register('cron', function($container) {
    return new CronService(
        $container->get('config'),
        $container->get('database'),
        $container->get('user'),
        $container->get('panel'),
        $container->get('telegram')
    );
});

// Register Application
$container->register('app', function($container) {
    return new Application($container);
});

/**
 * Global helper functions for accessing services
 */
function app(): Application
{
    global $container;
    return $container->get('app');
}

function config(string $key = null, $default = null)
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
function getConfig($key, $default = null)
{
    return config($key, $default);
}

function getDatabase()
{
    return db();
}

function getTelegram()
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
    $database = $container->get('database');
    
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
$GLOBALS['database'] = $container->get('database');
$GLOBALS['telegram'] = $container->get('telegram');
$GLOBALS['userService'] = $container->get('user');
$GLOBALS['panelService'] = $container->get('panel');
$GLOBALS['paymentService'] = $container->get('payment');
$GLOBALS['cronService'] = $container->get('cron');
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