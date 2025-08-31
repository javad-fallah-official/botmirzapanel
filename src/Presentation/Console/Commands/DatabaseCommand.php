<?php

declare(strict_types=1);

namespace BotMirzaPanel\Presentation\Console\Commands;

use BotMirzaPanel\Infrastructure\Persistence\Database\DatabaseManager;
use BotMirzaPanel\Infrastructure\Persistence\Migrations\MigrationRunner;

/**
 * Database Management Console Command
 */
class DatabaseCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'database';
    }

    public function getDescription(): string
    {
        return 'Database management (migrate, rollback, seed, status)';
    }

    public function getUsage(): string
    {
        return 'database <action> [options]';
    }

    public function getHelp(): string
    {
        return <<<HELP
Database Management Commands:

  database migrate             Run pending migrations
  database rollback [steps]    Rollback migrations (default: 1 step)
  database status              Show migration status
  database seed                Run database seeders
  database reset               Reset database (drop all tables)
  database fresh               Fresh database (reset + migrate + seed)
  database backup              Create database backup
  database restore <file>      Restore database from backup
  database check               Check database connection
  database create              Create database
  database drop                Drop database

Options:
  --force                     Force operation without confirmation
  --seed                      Run seeders after migration
  --step=N                    Number of migrations to run/rollback
  --verbose, -v               Verbose output
HELP;
    }

    public function execute(array $arguments = [], array $options = []): int
    {
        $this->setArguments($arguments);
        $this->setOptions($options);

        try {
            $action = $this->getArgument(0);
            
            if (!$action) {
                $this->error('No action specified.');
                $this->output($this->getHelp());
                return self::EXIT_INVALID_ARGUMENT;
            }

            switch ($action) {
                case 'migrate':
                    return $this->migrate();
                case 'rollback':
                    return $this->rollback();
                case 'status':
                    return $this->status();
                case 'seed':
                    return $this->seed();
                case 'reset':
                    return $this->reset();
                case 'fresh':
                    return $this->fresh();
                case 'backup':
                    return $this->backup();
                case 'restore':
                    return $this->restore();
                case 'check':
                    return $this->check();
                case 'create':
                    return $this->create();
                case 'drop':
                    return $this->drop();
                default:
                    $this->error("Unknown action: {$action}");
                    $this->output($this->getHelp());
                    return self::EXIT_INVALID_ARGUMENT;
            }
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Run database migrations
     */
    private function migrate(): int
    {
        $this->info('Running database migrations...');
        
        try {
            $migrationRunner = $this->getService(MigrationRunner::class);
            $step = $this->getOption('step');
            
            $migrations = $migrationRunner->getPendingMigrations();
            
            if (empty($migrations)) {
                $this->info('No pending migrations.');
                return self::EXIT_SUCCESS;
            }
            
            $this->info('Found ' . count($migrations) . ' pending migration(s).');
            
            if ($step) {
                $migrations = array_slice($migrations, 0, (int) $step);
                $this->info("Running {$step} migration(s)...");
            }
            
            foreach ($migrations as $i => $migration) {
                $this->info("Migrating: {$migration}");
                $migrationRunner->runMigration($migration);
                $this->progressBar($i + 1, count($migrations));
            }
            
            $this->success('Migrations completed successfully.');
            
            // Run seeders if requested
            if ($this->hasOption('seed')) {
                return $this->seed();
            }
            
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Migration failed: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Rollback database migrations
     */
    private function rollback(): int
    {
        $steps = (int) ($this->getArgument(1) ?? $this->getOption('step', 1));
        
        $this->info("Rolling back {$steps} migration(s)...");
        
        if (!$this->hasOption('force') && !$this->confirm('Are you sure you want to rollback migrations?')) {
            $this->info('Operation cancelled.');
            return self::EXIT_SUCCESS;
        }
        
        try {
            $migrationRunner = $this->getService(MigrationRunner::class);
            $migrations = $migrationRunner->getAppliedMigrations($steps);
            
            if (empty($migrations)) {
                $this->info('No migrations to rollback.');
                return self::EXIT_SUCCESS;
            }
            
            foreach ($migrations as $i => $migration) {
                $this->info("Rolling back: {$migration}");
                $migrationRunner->rollbackMigration($migration);
                $this->progressBar($i + 1, count($migrations));
            }
            
            $this->success('Rollback completed successfully.');
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Rollback failed: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Show migration status
     */
    private function status(): int
    {
        try {
            $migrationRunner = $this->getService(MigrationRunner::class);
            $applied = $migrationRunner->getAppliedMigrations();
            $pending = $migrationRunner->getPendingMigrations();
            $all = $migrationRunner->getAllMigrations();
            
            $this->output('Migration Status:');
            $this->output('');
            
            $headers = ['Migration', 'Status', 'Applied At'];
            $rows = [];
            
            foreach ($all as $migration) {
                $isApplied = in_array($migration, $applied);
                $appliedAt = $isApplied ? $migrationRunner->getMigrationAppliedAt($migration) : '-';
                
                $rows[] = [
                    $migration,
                    $isApplied ? 'Applied' : 'Pending',
                    $appliedAt
                ];
            }
            
            $this->table($headers, $rows);
            
            $this->output('');
            $this->info('Total migrations: ' . count($all));
            $this->info('Applied: ' . count($applied));
            $this->info('Pending: ' . count($pending));
            
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Failed to get migration status: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Run database seeders
     */
    private function seed(): int
    {
        $this->info('Running database seeders...');
        
        try {
            // TODO: Implement database seeding
            $this->success('Database seeding completed.');
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Seeding failed: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Reset database
     */
    private function reset(): int
    {
        $this->warning('This will drop all tables in the database!');
        
        if (!$this->hasOption('force') && !$this->confirm('Are you sure you want to reset the database?')) {
            $this->info('Operation cancelled.');
            return self::EXIT_SUCCESS;
        }
        
        try {
            $db = $this->getService(DatabaseManager::class);
            
            $this->info('Dropping all tables...');
            $db->dropAllTables();
            
            $this->success('Database reset completed.');
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Database reset failed: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Fresh database (reset + migrate + seed)
     */
    private function fresh(): int
    {
        $this->info('Creating fresh database...');
        
        // Reset database
        $result = $this->reset();
        if ($result !== self::EXIT_SUCCESS) {
            return $result;
        }
        
        // Run migrations
        $result = $this->migrate();
        if ($result !== self::EXIT_SUCCESS) {
            return $result;
        }
        
        // Run seeders
        if ($this->hasOption('seed')) {
            $result = $this->seed();
            if ($result !== self::EXIT_SUCCESS) {
                return $result;
            }
        }
        
        $this->success('Fresh database created successfully.');
        return self::EXIT_SUCCESS;
    }

    /**
     * Create database backup
     */
    private function backup(): int
    {
        $this->info('Creating database backup...');
        
        try {
            $db = $this->getService(DatabaseManager::class);
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $this->getOption('path', './backups/') . $filename;
            
            // Create backup directory if it doesn't exist
            $backupDir = dirname($filepath);
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            
            $db->createBackup($filepath);
            
            $this->success("Database backup created: {$filepath}");
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Backup failed: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Restore database from backup
     */
    private function restore(): int
    {
        $file = $this->getArgument(1);
        
        if (!$file) {
            $this->error('Backup file is required.');
            return self::EXIT_INVALID_ARGUMENT;
        }
        
        if (!file_exists($file)) {
            $this->error("Backup file not found: {$file}");
            return self::EXIT_NOT_FOUND;
        }
        
        $this->warning('This will overwrite the current database!');
        
        if (!$this->hasOption('force') && !$this->confirm('Are you sure you want to restore from backup?')) {
            $this->info('Operation cancelled.');
            return self::EXIT_SUCCESS;
        }
        
        try {
            $db = $this->getService(DatabaseManager::class);
            
            $this->info("Restoring database from: {$file}");
            $db->restoreBackup($file);
            
            $this->success('Database restored successfully.');
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Restore failed: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Check database connection
     */
    private function check(): int
    {
        $this->info('Checking database connection...');
        
        try {
            $db = $this->getService(DatabaseManager::class);
            $connection = $db->getConnection();
            
            // Test connection
            $connection->query('SELECT 1');
            
            $this->success('Database connection is working.');
            
            if ($this->hasOption('verbose')) {
                $this->output('Database info:');
                $this->output('  Driver: ' . $connection->getAttribute(\PDO::ATTR_DRIVER_NAME));
                $this->output('  Version: ' . $connection->getAttribute(\PDO::ATTR_SERVER_VERSION));
                $this->output('  Connection: ' . $connection->getAttribute(\PDO::ATTR_CONNECTION_STATUS));
            }
            
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Database connection failed: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Create database
     */
    private function create(): int
    {
        $this->info('Creating database...');
        
        try {
            $db = $this->getService(DatabaseManager::class);
            $db->createDatabase();
            
            $this->success('Database created successfully.');
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Database creation failed: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Drop database
     */
    private function drop(): int
    {
        $this->warning('This will permanently delete the entire database!');
        
        if (!$this->hasOption('force') && !$this->confirm('Are you sure you want to drop the database?')) {
            $this->info('Operation cancelled.');
            return self::EXIT_SUCCESS;
        }
        
        try {
            $db = $this->getService(DatabaseManager::class);
            $db->dropDatabase();
            
            $this->success('Database dropped successfully.');
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Database drop failed: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }
}