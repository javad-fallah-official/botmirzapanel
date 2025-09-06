<?php

declare(strict_types=1);

namespace BotMirzaPanel\Infrastructure\Database\Console;

use BotMirzaPanel\Database\DatabaseManager;
use BotMirzaPanel\Infrastructure\Database\MigrationRunner;

/**
 * Console command for running database migrations
 */
class MigrateCommand
{
    private DatabaseManager $db;
    private MigrationRunner $migrationRunner;
    
    public function __construct()
    {
        $this->db = $db;
        $this->migrationRunner = new MigrationRunner($db);
    }
    
    /**
     * Execute the migration command
     */
    public function execute(array $args = []): void
    {
        $command = $args[0] ?? 'migrate';
        
        switch ($command) {
            case 'migrate':
                $this->runMigrations();
                break;
                
            case 'rollback':
                $this->rollback();
                break;
                
            case 'status':
                $this->showStatus();
                break;
                
            case 'update':
                $this->updateTables();
                break;
                
            default:
                $this->showHelp();
                break;
        }
    }
    
    /**
     * Run all pending migrations
     */
    private function runMigrations(): void
    {
        echo "Starting database migrations...\n";
        echo "================================\n";
        
        try {
            $this->migrationRunner->runMigrations();
            echo "\nAll migrations completed successfully!\n";
        } catch (\Exception $e) {
            echo "\nMigration failed: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    /**
     * Rollback the last migration
     */
    private function rollback(): void
    {
        echo "Rolling back last migration...\n";
        echo "==============================\n";
        
        try {
            $this->migrationRunner->rollback();
            echo "\nRollback completed successfully!\n";
        } catch (\Exception $e) {
            echo "\nRollback failed: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    /**
     * Show migration status
     */
    private function showStatus(): void
    {
        echo "Migration Status\n";
        echo "================\n";
        
        $status = $this->migrationRunner->getStatus();
        
        foreach ($status as $migration) {
            $name = basename(str_replace('\\', '/', $migration['migration']));
            $executed = $migration['executed'] ? '✓' : '✗';
            $needed = $migration['needed'] ? '(needed)' : '(not needed)';
            
            echo sprintf("[%s] %s %s\n", $executed, $name, $needed);
        }
    }
    
    /**
     * Update existing table structures
     */
    private function updateTables(): void
    {
        echo "Updating existing table structures...\n";
        echo "====================================\n";
        
        try {
            $this->migrationRunner->updateExistingTables();
            echo "\nTable updates completed successfully!\n";
        } catch (\Exception $e) {
            echo "\nTable update failed: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    /**
     * Show help information
     */
    private function showHelp(): void
    {
        echo "Database Migration Commands\n";
        echo "==========================\n";
        echo "\n";
        echo "Available commands:\n";
        echo "  migrate  - Run all pending migrations\n";
        echo "  rollback - Rollback the last migration\n";
        echo "  status   - Show migration status\n";
        echo "  update   - Update existing table structures\n";
        echo "  help     - Show this help message\n";
        echo "\n";
        echo "Usage:\n";
        echo "  php migrate.php [command]\n";
        echo "\n";
        echo "Examples:\n";
        echo "  php migrate.php migrate\n";
        echo "  php migrate.php status\n";
        echo "  php migrate.php rollback\n";
    }
}