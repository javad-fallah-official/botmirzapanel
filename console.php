#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * BotMirzaPanel Console Application Entry Point
 * 
 * Usage: php console.php <command> [options] [arguments]
 * 
 * Examples:
 *   php console.php user list
 *   php console.php payment stats --from=2024-01-01
 *   php console.php telegram webhook:set https://example.com/webhook
 *   php console.php database migrate
 */

// Ensure we're running from command line
if (php_sapi_name() !== 'cli') {
    echo "This script can only be run from the command line.\n";
    exit(1);
}

// Set error reporting for CLI
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Define project root
define('PROJECT_ROOT', __DIR__);

try {
    // Bootstrap the application
    require_once __DIR__ . '/src/bootstrap.php';
    
    use BotMirzaPanel\Presentation\Console\ConsoleApplication;
    use BotMirzaPanel\Infrastructure\Container\ServiceContainer;
    
    // Create service container
    $container = new ServiceContainer();
    
    // Create and run console application
    $console = new ConsoleApplication($container);
    $exitCode = $console->run($argv);
    
    exit($exitCode);
    
} catch (\Exception $e) {
    fwrite(STDERR, "Fatal Error: {$e->getMessage()}\n");
    fwrite(STDERR, "Stack trace:\n{$e->getTraceAsString()}\n");
    exit(1);
} catch (\Error $e) {
    fwrite(STDERR, "Fatal Error: {$e->getMessage()}\n");
    fwrite(STDERR, "Stack trace:\n{$e->getTraceAsString()}\n");
    exit(1);
}