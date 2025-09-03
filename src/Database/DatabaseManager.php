<?php

declare(strict_types=1);

namespace BotMirzaPanel\Database;

use BotMirzaPanel\Config\ConfigManager;
use PDO;
use PDOException;

/**
 * Database manager with repository pattern and proper ORM-like interface
 * Handles all database operations with connection management
 */
class DatabaseManager
{
    private PDO $pdo;
    private \mysqli $mysqli;
    private ConfigManager $config;
    private array $repositories = [];

    public function __construct(ConfigManager $config): void
    {
        $this->config = $config;
        $this->initializeConnections();
    }

    /**
     * Initialize database connections (PDO and mysqli for backward compatibility)
     */
    private function initializeConnections(): void
    {
        $host = $this->config->get('database.host');
        $dbname = $this->config->get('database.name');
        $username = $this->config->get('database.username');
        $password = $this->config->get('database.password');
        $charset = $this->config->get('database.charset', 'utf8mb4');

        try {
            // PDO connection
            $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
            $this->pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            // mysqli connection for legacy compatibility
            $this->mysqli = new \mysqli($host, $username, $password, $dbname);
            if ($this->mysqli->connect_error) {
                throw new \Exception("mysqli connection failed: " . $this->mysqli->connect_error);
            }
            $this->mysqli->set_charset($charset);

        } catch (PDOException $e) {
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Get PDO instance
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Get mysqli instance (for legacy compatibility)
     */
    public function getMysqli(): \mysqli
    {
        return $this->mysqli;
    }

    /**
     * Get repository instance
     */
    public function getRepository(string $entityClass): BaseRepository
    {
        if (!isset($this->repositories[$entityClass])) {
            $repositoryClass = str_replace('Entity', 'Repository', $entityClass);
            if (!class_exists($repositoryClass)) {
                $repositoryClass = BaseRepository::class;
            }
            $this->repositories[$entityClass] = new $repositoryClass($this, $entityClass);
        }
        
        return $this->repositories[$entityClass];
    }

    /**
     * Execute a prepared statement
     */
    public function execute(string $sql, array $params = []): \PDOStatement
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new \Exception("Database query failed: " . $e->getMessage());
        }
    }

    /**
     * Fetch single row
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->execute($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Fetch all rows
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Insert record and return last insert ID
     */
    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->execute($sql, $data);
        
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update records
     */
    public function update(string $table, array $data, array $where): int
    {
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "{$column} = :{$column}";
        }
        $setClause = implode(', ', $setParts);
        
        $whereParts = [];
        foreach (array_keys($where) as $column) {
            $whereParts[] = "{$column} = :where_{$column}";
        }
        $whereClause = implode(' AND ', $whereParts);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$whereClause}";
        
        // Merge data and where parameters
        $params = $data;
        foreach ($where as $key => $value) {
            $params["where_{$key}"] = $value;
        }
        
        $stmt = $this->execute($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Delete records
     */
    public function delete(string $table, array $where): int
    {
        $whereParts = [];
        foreach (array_keys($where) as $column) {
            $whereParts[] = "{$column} = :{$column}";
        }
        $whereClause = implode(' AND ', $whereParts);
        
        $sql = "DELETE FROM {$table} WHERE {$whereClause}";
        $stmt = $this->execute($sql, $where);
        
        return $stmt->rowCount();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit(): void
    {
        $this->pdo->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback(): void
    {
        $this->pdo->rollBack();
    }

    /**
     * Initialize database tables
     */
    public function initializeTables(): void
    {
        // Load and execute table creation scripts
        $tableScript = dirname(__DIR__, 2) . '/table.php';
        if (file_exists($tableScript)) {
            // Set global variables for legacy table.php
            $GLOBALS['connect'] = $this->mysqli;
            $GLOBALS['pdo'] = $this->pdo;
            include $tableScript;
        }
    }

    /**
     * Legacy select function for backward compatibility
     */
    public function legacySelect(string $table, string $columns, string $whereColumn, $whereValue, string $mode = 'select'): array
    {
        $sql = "SELECT {$columns} FROM {$table} WHERE {$whereColumn} = :value";
        
        if ($mode === 'select') {
            return $this->fetchOne($sql, ['value' => $whereValue]) ?? [];
        } else {
            return $this->fetchAll($sql, ['value' => $whereValue]);
        }
    }

    /**
     * Execute a raw query and return all rows
     */
    public function query(string $sql, array $params = []): array
    {
        return $this->fetchAll($sql, $params);
    }

    /**
     * Build WHERE clause and params from criteria array
     * Supports operators in keys like `created_at >=` and IN/NOT IN with array values
     */
    private function buildWhere(array $criteria, array &$params): string
    {
        if (empty($criteria)) {
            return '';
        }

        $clauses = [];
        foreach ($criteria as $key => $value) {
            $paramKey = 'p_' . preg_replace('/[^a-zA-Z0-9_]+/', '_', $key) . '_' . count($params);

            // Detect operator in key (e.g., "created_at >=")
            $parts = preg_split('/\s+/', trim($key), 2);
            $column = $parts[0];
            $operator = $parts[1] ?? '=';
            $operator = strtoupper($operator);

            if (($operator === 'IN' || $operator === 'NOT IN') && is_array($value)) {
                $inParams = [];
                foreach ($value as $idx => $v) {
                    $inKey = $paramKey . '_' . $idx;
                    $params[$inKey] = $v;
                    $inParams[] = ":{$inKey}";
                }
                $clauses[] = sprintf('%s %s (%s)', $column, $operator, implode(', ', $inParams));
                continue;
            }

            if ($operator === 'LIKE') {
                $params[$paramKey] = $value;
                $clauses[] = sprintf('%s LIKE :%s', $column, $paramKey);
                continue;
            }

            // Default: comparison operator
            $params[$paramKey] = $value;
            $clauses[] = sprintf('%s %s :%s', $column, $operator, $paramKey);
        }

        return ' WHERE ' . implode(' AND ', $clauses);
    }

    /**
     * Find a single row by criteria
     */
    public function findOne(string $table, array $criteria = []): ?array
    {
        $params = [];
        $where = $this->buildWhere($criteria, $params);
        $sql = sprintf('SELECT * FROM %s%s LIMIT 1', $table, $where);
        return $this->fetchOne($sql, $params);
    }

    /**
     * Find all rows by criteria with optional ordering and pagination
     */
    public function findAll(string $table, array $criteria = [], array $orderBy = [], ?int $limit = null, ?int $offset = null): array
    {
        $params = [];
        $where = $this->buildWhere($criteria, $params);

        $orderClause = '';
        if (!empty($orderBy)) {
            $parts = [];
            foreach ($orderBy as $col => $dir) {
                $dir = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
                $parts[] = $col . ' ' . $dir;
            }
            $orderClause = ' ORDER BY ' . implode(', ', $parts);
        }

        $limitClause = '';
        if ($limit !== null) {
            $limitClause = ' LIMIT ' . (int)$limit;
            if ($offset !== null) {
                $limitClause .= ' OFFSET ' . (int)$offset;
            }
        }

        $sql = sprintf('SELECT * FROM %s%s%s%s', $table, $where, $orderClause, $limitClause);
        return $this->fetchAll($sql, $params);
    }

    /**
     * Count rows by criteria
     */
    public function count(string $table, array $criteria = []): int
    {
        $params = [];
        $where = $this->buildWhere($criteria, $params);
        $sql = sprintf('SELECT COUNT(*) AS cnt FROM %s%s', $table, $where);
        $row = $this->fetchOne($sql, $params);
        return (int)($row['cnt'] ?? 0);
    }
}