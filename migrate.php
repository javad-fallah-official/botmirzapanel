<?php

/**
 * Database Migration Script
 * 
 * This script handles database migrations for the BotMirzaPanel application.
 * 
 * Usage:
 *   php migrate.php [command]
 * 
 * Commands:
 *   migrate  - Run all pending migrations
 *   rollback - Rollback the last migration
 *   status   - Show migration status
 *   update   - Update existing table structures
 *   help     - Show help information
 */

require_once __DIR__ . '/src/bootstrap.php';

use BotMirzaPanel\Database\DatabaseManager;
use BotMirzaPanel\Infrastructure\Database\Console\MigrateCommand;

try {
    // Initialize database connection
    $config = require __DIR__ . '/config.php';
    $db = new DatabaseManager(
        $config['database']['host'] ?? 'localhost',
        $config['database']['username'] ?? 'root',
        $config['database']['password'] ?? '',
        $config['database']['database'] ?? 'botmirzapanel',
        $config['database']['port'] ?? 3306
    );
    
    // Create and execute migration command
    $migrateCommand = new MigrateCommand($db);
    $args = array_slice($argv, 1); // Remove script name from arguments
    
    if (empty($args)) {
        $args = ['help'];
    }
    
    $migrateCommand->execute($args);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}