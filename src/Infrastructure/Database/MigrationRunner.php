<?php

declare(strict_types=1);

namespace BotMirzaPanel\Infrastructure\Database;

use BotMirzaPanel\Database\DatabaseManager;
use BotMirzaPanel\Infrastructure\Database\Migrations\CreateUsersTable;

/**
 * Handles running database migrations
 */
class MigrationRunner
{
    private DatabaseManager $db;
    private array $migrations;
    
    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
        $this->migrations = [
            CreateUsersTable::class
        ];
    }
    
    /**
     * Run all pending migrations
     */
    public function runMigrations(): void
    {
        $this->createMigrationsTable();
        
        foreach ($this->migrations as $migrationClass) {
            $migration = new $migrationClass($this->db);
            
            if (!$this->isMigrationRun($migrationClass) && $migration->shouldRun()) {
                echo "Running migration: {$migrationClass}\n";
                $migration->up();
                $this->markMigrationAsRun($migrationClass);
                echo "Migration completed: {$migrationClass}\n";
            } else {
                echo "Skipping migration: {$migrationClass} (already run or not needed)\n";
            }
        }
    }
    
    /**
     * Update existing tables without running full migrations
     */
    public function updateExistingTables(): void
    {
        foreach ($this->migrations as $migrationClass) {
            $migration = new $migrationClass($this->db);
            
            if (method_exists($migration, 'updateExistingTable')) {
                echo "Updating existing table structure: {$migrationClass}\n";
                $migration->updateExistingTable();
                echo "Table update completed: {$migrationClass}\n";
            }
        }
    }
    
    /**
     * Rollback the last migration
     */
    public function rollback(): void
    {
        $lastMigration = $this->getLastMigration();
        
        if ($lastMigration) {
            $migrationClass = $lastMigration['migration'];
            $migration = new $migrationClass($this->db);
            
            echo "Rolling back migration: {$migrationClass}\n";
            $migration->down();
            $this->removeMigrationRecord($migrationClass);
            echo "Rollback completed: {$migrationClass}\n";
        } else {
            echo "No migrations to rollback\n";
        }
    }
    
    /**
     * Create the migrations tracking table
     */
    private function createMigrationsTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $this->db->execute($sql);
    }
    
    /**
     * Check if a migration has been run
     */
    private function isMigrationRun(string $migrationClass): bool
    {
        try {
            $result = $this->db->selectOne(
                'SELECT id FROM migrations WHERE migration = ?',
                [$migrationClass]
            );
            return $result !== null;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Mark a migration as run
     */
    private function markMigrationAsRun(string $migrationClass): void
    {
        $this->db->insert('migrations', [
            'migration' => $migrationClass,
            'executed_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Get the last executed migration
     */
    private function getLastMigration(): ?array
    {
        try {
            return $this->db->selectOne(
                'SELECT * FROM migrations ORDER BY executed_at DESC LIMIT 1'
            );
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Remove a migration record
     */
    private function removeMigrationRecord(string $migrationClass): void
    {
        $this->db->delete('migrations', ['migration' => $migrationClass]);
    }
    
    /**
     * Get migration status
     */
    public function getStatus(): array
    {
        $status = [];
        
        foreach ($this->migrations as $migrationClass) {
            $status[] = [
                'migration' => $migrationClass,
                'executed' => $this->isMigrationRun($migrationClass),
                'needed' => (new $migrationClass($this->db))->shouldRun()
            ];
        }
        
        return $status;
    }
}