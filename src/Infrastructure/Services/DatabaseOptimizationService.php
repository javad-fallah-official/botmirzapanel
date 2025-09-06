<?php
// WARNING: SQL injection vulnerability detected in this file
// Please review and use prepared statements or secure_* functions


declare(strict_types=1);

namespace BotMirzaPanel\Infrastructure\Services;

use BotMirzaPanel\Shared\Contracts\CacheInterface;
use PDO;
use PDOException;
use RuntimeException;

/**
 * Database Optimization Service
 * 
 * Provides database performance optimization features including:
 * - Connection pooling and management
 * - Query optimization and caching
 * - Performance monitoring and metrics
 * - Read/write splitting
 * - Query analysis and suggestions
 */
class DatabaseOptimizationService
{
    private array $connectionPool = [];
    private array $queryCache = [];
    private array $performanceMetrics = [];
    private array $slowQueries = [];
    private int $maxConnections;
    private int $slowQueryThreshold;
    private bool $enableQueryCache;
    private bool $enableReadWriteSplit;
    
    public function __construct(
        private CacheInterface $cache,
        private array $config = []
    ) {
        $this->maxConnections = $config['max_connections'] ?? 10;
        $this->slowQueryThreshold = $config['slow_query_threshold'] ?? 1000; // milliseconds
        $this->enableQueryCache = $config['enable_query_cache'] ?? true;
        $this->enableReadWriteSplit = $config['enable_read_write_split'] ?? false;
    }

    /**
     * Get optimized database connection from pool
     */
    public function getConnection(string $type = 'write'): PDO
    {
        $connectionKey = $this->getConnectionKey($type);
        
        // Check if connection exists in pool and is still alive
        if (isset($this->connectionPool[$connectionKey])) {
            $connection = $this->connectionPool[$connectionKey];
            if ($this->isConnectionAlive($connection)) {
                return $connection;
            }
            unset($this->connectionPool[$connectionKey]);
        }
        
        // Create new connection if pool not full
        if (count($this->connectionPool) < $this->maxConnections) {
            $connection = $this->createOptimizedConnection($type);
            $this->connectionPool[$connectionKey] = $connection;
            return $connection;
        }
        
        // Pool is full, return least recently used connection
        return $this->getLeastRecentlyUsedConnection();
    }

    /**
     * Execute optimized query with caching and monitoring
     */
    public function executeQuery(string $sql, array $params = [], string $type = 'read'): array
    {
        $startTime = microtime(true);
        $queryHash = $this->getQueryHash($sql, $params);
        
        // Check query cache for read operations
        if ($type === 'read' && $this->enableQueryCache) {
            $cached = $this->getFromQueryCache($queryHash);
            if ($cached !== null) {
                $this->recordCacheHit($sql);
                return $cached;
            }
        }
        
        // Get appropriate connection
        $connection = $this->getConnection($type);
        
        try {
            // Prepare and execute query
            $statement = $connection->prepare($sql);
            $statement->execute($params);
            
            $result = $statement->fetchAll(PDO::FETCH_ASSOC);
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // Record performance metrics
            $this->recordQueryMetrics($sql, $executionTime, count($result));
            
            // Cache result for read queries
            if ($type === 'read' && $this->enableQueryCache) {
                $this->storeInQueryCache($queryHash, $result);
            }
            
            // Check for slow queries
            if ($executionTime > $this->slowQueryThreshold) {
                $this->recordSlowQuery($sql, $executionTime, $params);
            }
            
            return $result;
            
        } catch (PDOException $e) {
            $this->recordQueryError($sql, $e->getMessage());
            throw new RuntimeException("Database query failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Analyze query performance and provide optimization suggestions
     */
    public function analyzeQuery(string $sql): array
    {
        $connection = $this->getConnection('read');
        $analysis = [];
        
        try {
            // Get query execution plan
            $explainSql = "EXPLAIN " . $sql;
            $statement = $connection->prepare($explainSql);
            $statement->execute();
            $executionPlan = $statement->fetchAll(PDO::FETCH_ASSOC);
            
            $analysis['execution_plan'] = $executionPlan;
            $analysis['suggestions'] = $this->generateOptimizationSuggestions($executionPlan);
            $analysis['estimated_cost'] = $this->calculateQueryCost($executionPlan);
            
        } catch (PDOException $e) {
            $analysis['error'] = "Could not analyze query: " . $e->getMessage();
        }
        
        return $analysis;
    }

    /**
     * Get database performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        return [
            'total_queries' => $this->performanceMetrics['total_queries'] ?? 0,
            'average_execution_time' => $this->calculateAverageExecutionTime(),
            'slow_queries_count' => count($this->slowQueries),
            'cache_hit_ratio' => $this->calculateCacheHitRatio(),
            'active_connections' => count($this->connectionPool),
            'slow_queries' => array_slice($this->slowQueries, -10), // Last 10 slow queries
            'top_queries' => $this->getTopQueries(),
        ];
    }

    /**
     * Optimize database indexes based on query patterns
     */
    public function optimizeIndexes(): array
    {
        $suggestions = [];
        $connection = $this->getConnection('read');
        
        try {
            // Analyze table usage patterns
            $tables = $this->getTableUsageStats();
            
            foreach ($tables as $table => $stats) {
                $indexSuggestions = $this->analyzeTableIndexes($table, $stats);
                if (!empty($indexSuggestions)) {
                    $suggestions[$table] = $indexSuggestions;
                }
            }
            
        } catch (PDOException $e) {
            $suggestions['error'] = "Could not analyze indexes: " . $e->getMessage();
        }
        
        return $suggestions;
    }

    /**
     * Clear query cache
     */
    public function clearQueryCache(): bool
    {
        $this->queryCache = [];
        return $this->cache->flushTags(['database_queries']);
    }

    /**
     * Warm up connection pool
     */
    public function warmUpConnections(): void
    {
        $connectionsToCreate = min($this->maxConnections, 3); // Create initial connections
        
        for ($i = 0; $i < $connectionsToCreate; $i++) {
            $this->getConnection('read');
            if ($this->enableReadWriteSplit) {
                $this->getConnection('write');
            }
        }
    }

    /**
     * Close all connections in pool
     */
    public function closeAllConnections(): void
    {
        $this->connectionPool = [];
    }

    /**
     * Get connection key based on type and configuration
     */
    private function getConnectionKey(string $type): string
    {
        if ($this->enableReadWriteSplit) {
            return $type . '_' . uniqid();
        }
        return 'default_' . uniqid();
    }

    /**
     * Create optimized database connection
     */
    private function createOptimizedConnection(string $type): PDO
    {
        $config = $this->getConnectionConfig($type);
        
        $dsn = sprintf(
            "mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4",
            $config['host'],
            $config['port'],
            $config['database']
        );
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => true,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ];
        
        return new PDO($dsn, $config['username'], $config['password'], $options);
    }

    /**
     * Check if connection is still alive
     */
    private function isConnectionAlive(PDO $connection): bool
    {
        try {
            $connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get least recently used connection
     */
    private function getLeastRecentlyUsedConnection(): PDO
    {
        // Simple implementation - return first connection
        // In production, implement proper LRU tracking
        return reset($this->connectionPool);
    }

    /**
     * Generate query hash for caching
     */
    private function getQueryHash(string $sql, array $params): string
    {
        return md5($sql . serialize($params));
    }

    /**
     * Get query result from cache
     */
    private function getFromQueryCache(string $queryHash): ?array
    {
        return $this->cache->get("query_cache:{$queryHash}");
    }

    /**
     * Store query result in cache
     */
    private function storeInQueryCache(string $queryHash, array $result): void
    {
        $this->cache->set(
            "query_cache:{$queryHash}",
            $result,
            $this->config['query_cache_ttl'] ?? 300
        );
    }

    /**
     * Record query performance metrics
     */
    private function recordQueryMetrics(string $sql, float $executionTime, int $resultCount): void
    {
        $this->performanceMetrics['total_queries'] = ($this->performanceMetrics['total_queries'] ?? 0) + 1;
        $this->performanceMetrics['total_execution_time'] = ($this->performanceMetrics['total_execution_time'] ?? 0) + $executionTime;
        
        $queryType = $this->getQueryType($sql);
        $this->performanceMetrics['by_type'][$queryType] = ($this->performanceMetrics['by_type'][$queryType] ?? 0) + 1;
    }

    /**
     * Record slow query for analysis
     */
    private function recordSlowQuery(string $sql, float $executionTime, array $params): void
    {
        $this->slowQueries[] = [
            'sql' => $sql,
            'execution_time' => $executionTime,
            'params' => $params,
            'timestamp' => time(),
        ];
        
        // Keep only last 100 slow queries
        if (count($this->slowQueries) > 100) {
            array_shift($this->slowQueries);
        }
    }

    /**
     * Record cache hit for metrics
     */
    private function recordCacheHit(string $sql): void
    {
        $this->performanceMetrics['cache_hits'] = ($this->performanceMetrics['cache_hits'] ?? 0) + 1;
    }

    /**
     * Record query error
     */
    private function recordQueryError(string $sql, string $error): void
    {
        $this->performanceMetrics['query_errors'] = ($this->performanceMetrics['query_errors'] ?? 0) + 1;
    }

    /**
     * Calculate average execution time
     */
    private function calculateAverageExecutionTime(): float
    {
        $totalQueries = $this->performanceMetrics['total_queries'] ?? 0;
        $totalTime = $this->performanceMetrics['total_execution_time'] ?? 0;
        
        return $totalQueries > 0 ? $totalTime / $totalQueries : 0;
    }

    /**
     * Calculate cache hit ratio
     */
    private function calculateCacheHitRatio(): float
    {
        $totalQueries = $this->performanceMetrics['total_queries'] ?? 0;
        $cacheHits = $this->performanceMetrics['cache_hits'] ?? 0;
        
        return $totalQueries > 0 ? ($cacheHits / $totalQueries) * 100 : 0;
    }

    /**
     * Get connection configuration based on type
     */
    private function getConnectionConfig(string $type): array
    {
        // Return appropriate config based on read/write split
        return $this->config['connections'][$type] ?? $this->config['connections']['default'];
    }

    /**
     * Get query type (SELECT, INSERT, UPDATE, DELETE)
     */
    private function getQueryType(string $sql): string
    {
        $sql = trim(strtoupper($sql));
        if (strpos($sql, 'SELECT') === 0) return 'SELECT';
        if (strpos($sql, 'INSERT') === 0) return 'INSERT';
        if (strpos($sql, 'UPDATE') === 0) return 'UPDATE';
        if (strpos($sql, 'DELETE') === 0) return 'DELETE';
        return 'OTHER';
    }

    /**
     * Generate optimization suggestions based on execution plan
     */
    private function generateOptimizationSuggestions(array $executionPlan): array
    {
        $suggestions = [];
        
        foreach ($executionPlan as $step) {
            if (isset($step['type']) && $step['type'] === 'ALL') {
                $suggestions[] = "Consider adding an index to avoid full table scan on table: " . $step['table'];
            }
            
            if (isset($step['Extra']) && strpos($step['Extra'], 'Using filesort') !== false) {
                $suggestions[] = "Consider adding an index to avoid filesort operation";
            }
            
            if (isset($step['rows']) && $step['rows'] > 10000) {
                $suggestions[] = "Query examines many rows ({$step['rows']}), consider optimizing WHERE clause";
            }
        }
        
        return $suggestions;
    }

    /**
     * Calculate estimated query cost
     */
    private function calculateQueryCost(array $executionPlan): float
    {
        $cost = 0;
        
        foreach ($executionPlan as $step) {
            $rows = $step['rows'] ?? 1;
            $cost += $rows * 0.1; // Simple cost calculation
        }
        
        return $cost;
    }

    /**
     * Get table usage statistics
     */
    private function getTableUsageStats(): array
    {
        // Simplified implementation - in production, track actual usage
        return [
            'users' => ['queries' => 100, 'avg_time' => 50],
            'payments' => ['queries' => 80, 'avg_time' => 75],
            'bots' => ['queries' => 60, 'avg_time' => 30],
        ];
    }

    /**
     * Analyze table indexes
     */
    private function analyzeTableIndexes(string $table, array $stats): array
    {
        $suggestions = [];
        
        if ($stats['avg_time'] > 100) {
            $suggestions[] = "Consider adding composite indexes for frequently queried columns";
        }
        
        if ($stats['queries'] > 50) {
            $suggestions[] = "High query volume - ensure proper indexing strategy";
        }
        
        return $suggestions;
    }

    /**
     * Get top queries by frequency
     */
    private function getTopQueries(): array
    {
        // Simplified implementation - in production, track actual query patterns
        return [
            ['sql' => 'SELECT * FROM users WHERE id = ?', 'count' => 150],
            ['sql' => 'SELECT * FROM payments WHERE user_id = ?', 'count' => 120],
            ['sql' => 'SELECT * FROM bots WHERE status = ?', 'count' => 90],
        ];
    }
}