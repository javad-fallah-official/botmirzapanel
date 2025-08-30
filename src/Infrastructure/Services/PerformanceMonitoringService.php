<?php

declare(strict_types=1);

namespace BotMirzaPanel\Infrastructure\Services;

use BotMirzaPanel\Shared\Contracts\CacheInterface;
use RuntimeException;

/**
 * Performance Monitoring Service
 * 
 * Provides comprehensive performance monitoring including:
 * - Response time tracking
 * - Memory usage monitoring
 * - Database query performance
 * - API endpoint analytics
 * - System resource monitoring
 * - Performance alerts and notifications
 */
class PerformanceMonitoringService
{
    private array $metrics = [];
    private array $timers = [];
    private array $counters = [];
    private array $alerts = [];
    private float $requestStartTime;
    private bool $enableProfiling;
    private bool $enableAlerts;
    
    public function __construct(
        private CacheInterface $cache,
        private array $config = []
    ) {
        $this->requestStartTime = microtime(true);
        $this->enableProfiling = $config['enable_profiling'] ?? true;
        $this->enableAlerts = $config['enable_alerts'] ?? true;
        
        $this->initializeMetrics();
        $this->startSystemMonitoring();
    }

    /**
     * Start timing an operation
     */
    public function startTimer(string $name): void
    {
        $this->timers[$name] = [
            'start' => microtime(true),
            'memory_start' => memory_get_usage(true),
        ];
    }

    /**
     * Stop timing an operation and record the duration
     */
    public function stopTimer(string $name): float
    {
        if (!isset($this->timers[$name])) {
            throw new RuntimeException("Timer '{$name}' was not started");
        }
        
        $timer = $this->timers[$name];
        $duration = microtime(true) - $timer['start'];
        $memoryUsed = memory_get_usage(true) - $timer['memory_start'];
        
        $this->recordMetric('timer', $name, [
            'duration' => $duration,
            'memory_used' => $memoryUsed,
            'timestamp' => time(),
        ]);
        
        unset($this->timers[$name]);
        
        // Check for slow operations
        $slowThreshold = $this->config['slow_operation_threshold'] ?? 1.0; // 1 second
        if ($duration > $slowThreshold) {
            $this->recordSlowOperation($name, $duration, $memoryUsed);
        }
        
        return $duration;
    }

    /**
     * Increment a counter
     */
    public function incrementCounter(string $name, int $value = 1): void
    {
        if (!isset($this->counters[$name])) {
            $this->counters[$name] = 0;
        }
        
        $this->counters[$name] += $value;
        
        $this->recordMetric('counter', $name, [
            'value' => $this->counters[$name],
            'increment' => $value,
            'timestamp' => time(),
        ]);
    }

    /**
     * Record a custom metric
     */
    public function recordMetric(string $type, string $name, array $data): void
    {
        $metricKey = "{$type}.{$name}";
        
        if (!isset($this->metrics[$metricKey])) {
            $this->metrics[$metricKey] = [];
        }
        
        $this->metrics[$metricKey][] = array_merge($data, [
            'type' => $type,
            'name' => $name,
            'recorded_at' => microtime(true),
        ]);
        
        // Limit metric history to prevent memory bloat
        $maxHistory = $this->config['max_metric_history'] ?? 1000;
        if (count($this->metrics[$metricKey]) > $maxHistory) {
            array_shift($this->metrics[$metricKey]);
        }
        
        // Store in cache for persistence
        $this->storeMetricInCache($metricKey, $data);
    }

    /**
     * Track database query performance
     */
    public function trackDatabaseQuery(string $query, float $executionTime, int $resultCount = 0): void
    {
        $queryHash = md5($query);
        
        $this->recordMetric('database_query', $queryHash, [
            'query' => $query,
            'execution_time' => $executionTime,
            'result_count' => $resultCount,
            'timestamp' => time(),
        ]);
        
        // Check for slow queries
        $slowQueryThreshold = $this->config['slow_query_threshold'] ?? 0.5; // 500ms
        if ($executionTime > $slowQueryThreshold) {
            $this->recordSlowQuery($query, $executionTime);
        }
    }

    /**
     * Track API endpoint performance
     */
    public function trackApiEndpoint(string $endpoint, string $method, float $responseTime, int $statusCode): void
    {
        $endpointKey = "{$method} {$endpoint}";
        
        $this->recordMetric('api_endpoint', $endpointKey, [
            'endpoint' => $endpoint,
            'method' => $method,
            'response_time' => $responseTime,
            'status_code' => $statusCode,
            'timestamp' => time(),
        ]);
        
        // Track status code distribution
        $this->incrementCounter("http_status_{$statusCode}");
        
        // Check for slow endpoints
        $slowEndpointThreshold = $this->config['slow_endpoint_threshold'] ?? 2.0; // 2 seconds
        if ($responseTime > $slowEndpointThreshold) {
            $this->recordSlowEndpoint($endpointKey, $responseTime, $statusCode);
        }
    }

    /**
     * Track memory usage
     */
    public function trackMemoryUsage(string $context = 'general'): void
    {
        $memoryUsage = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        
        $this->recordMetric('memory_usage', $context, [
            'current_usage' => $memoryUsage,
            'peak_usage' => $peakMemory,
            'timestamp' => time(),
        ]);
        
        // Check memory thresholds
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        $warningThreshold = $memoryLimit * 0.8;
        $criticalThreshold = $memoryLimit * 0.9;
        
        if ($memoryUsage > $criticalThreshold) {
            $this->triggerAlert('memory_critical', [
                'current_usage' => $memoryUsage,
                'memory_limit' => $memoryLimit,
                'context' => $context,
            ]);
        } elseif ($memoryUsage > $warningThreshold) {
            $this->triggerAlert('memory_warning', [
                'current_usage' => $memoryUsage,
                'memory_limit' => $memoryLimit,
                'context' => $context,
            ]);
        }
    }

    /**
     * Get performance summary
     */
    public function getPerformanceSummary(): array
    {
        $currentTime = microtime(true);
        $requestDuration = $currentTime - $this->requestStartTime;
        
        return [
            'request_duration' => $requestDuration,
            'memory_usage' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'limit' => $this->parseMemoryLimit(ini_get('memory_limit')),
            ],
            'metrics_collected' => count($this->metrics),
            'active_timers' => count($this->timers),
            'counters' => $this->counters,
            'alerts_triggered' => count($this->alerts),
            'system_load' => $this->getSystemLoad(),
            'database_stats' => $this->getDatabaseStats(),
            'slow_operations' => $this->getSlowOperations(),
        ];
    }

    /**
     * Get detailed metrics for a specific type
     */
    public function getMetrics(string $type = null, int $limit = 100): array
    {
        if ($type === null) {
            return array_slice($this->metrics, -$limit, $limit, true);
        }
        
        $filteredMetrics = [];
        foreach ($this->metrics as $key => $metricData) {
            if (strpos($key, $type . '.') === 0) {
                $filteredMetrics[$key] = array_slice($metricData, -$limit, $limit);
            }
        }
        
        return $filteredMetrics;
    }

    /**
     * Get performance alerts
     */
    public function getAlerts(string $level = null): array
    {
        if ($level === null) {
            return $this->alerts;
        }
        
        return array_filter($this->alerts, function($alert) use ($level) {
            return $alert['level'] === $level;
        });
    }

    /**
     * Generate performance report
     */
    public function generateReport(int $timeRange = 3600): array
    {
        $endTime = time();
        $startTime = $endTime - $timeRange;
        
        $report = [
            'time_range' => [
                'start' => $startTime,
                'end' => $endTime,
                'duration' => $timeRange,
            ],
            'summary' => $this->getPerformanceSummary(),
            'top_slow_operations' => $this->getTopSlowOperations(10),
            'top_slow_queries' => $this->getTopSlowQueries(10),
            'endpoint_performance' => $this->getEndpointPerformance(),
            'memory_trends' => $this->getMemoryTrends($startTime, $endTime),
            'error_rates' => $this->getErrorRates($startTime, $endTime),
            'recommendations' => $this->generateRecommendations(),
        ];
        
        return $report;
    }

    /**
     * Clear old metrics
     */
    public function clearOldMetrics(int $olderThan = 86400): int
    {
        $cutoffTime = time() - $olderThan;
        $cleared = 0;
        
        foreach ($this->metrics as $key => &$metricData) {
            $originalCount = count($metricData);
            $metricData = array_filter($metricData, function($metric) use ($cutoffTime) {
                return ($metric['timestamp'] ?? 0) > $cutoffTime;
            });
            $cleared += $originalCount - count($metricData);
        }
        
        return $cleared;
    }

    /**
     * Export metrics to external monitoring system
     */
    public function exportMetrics(string $format = 'json'): string
    {
        $exportData = [
            'timestamp' => time(),
            'metrics' => $this->metrics,
            'counters' => $this->counters,
            'alerts' => $this->alerts,
            'summary' => $this->getPerformanceSummary(),
        ];
        
        switch ($format) {
            case 'json':
                return json_encode($exportData, JSON_PRETTY_PRINT);
            case 'csv':
                return $this->convertToCsv($exportData);
            default:
                throw new RuntimeException("Unsupported export format: {$format}");
        }
    }

    /**
     * Initialize default metrics
     */
    private function initializeMetrics(): void
    {
        $this->counters = [
            'requests_total' => 0,
            'database_queries' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
        ];
    }

    /**
     * Start system monitoring
     */
    private function startSystemMonitoring(): void
    {
        if (!$this->enableProfiling) {
            return;
        }
        
        // Register shutdown function to capture final metrics
        register_shutdown_function(function() {
            $this->recordFinalMetrics();
        });
    }

    /**
     * Record slow operation
     */
    private function recordSlowOperation(string $name, float $duration, int $memoryUsed): void
    {
        $this->recordMetric('slow_operation', $name, [
            'duration' => $duration,
            'memory_used' => $memoryUsed,
            'timestamp' => time(),
        ]);
        
        if ($this->enableAlerts) {
            $this->triggerAlert('slow_operation', [
                'operation' => $name,
                'duration' => $duration,
                'threshold' => $this->config['slow_operation_threshold'] ?? 1.0,
            ]);
        }
    }

    /**
     * Record slow database query
     */
    private function recordSlowQuery(string $query, float $executionTime): void
    {
        $this->recordMetric('slow_query', md5($query), [
            'query' => $query,
            'execution_time' => $executionTime,
            'timestamp' => time(),
        ]);
        
        if ($this->enableAlerts) {
            $this->triggerAlert('slow_query', [
                'query' => substr($query, 0, 100) . '...',
                'execution_time' => $executionTime,
                'threshold' => $this->config['slow_query_threshold'] ?? 0.5,
            ]);
        }
    }

    /**
     * Record slow API endpoint
     */
    private function recordSlowEndpoint(string $endpoint, float $responseTime, int $statusCode): void
    {
        $this->recordMetric('slow_endpoint', md5($endpoint), [
            'endpoint' => $endpoint,
            'response_time' => $responseTime,
            'status_code' => $statusCode,
            'timestamp' => time(),
        ]);
        
        if ($this->enableAlerts) {
            $this->triggerAlert('slow_endpoint', [
                'endpoint' => $endpoint,
                'response_time' => $responseTime,
                'status_code' => $statusCode,
                'threshold' => $this->config['slow_endpoint_threshold'] ?? 2.0,
            ]);
        }
    }

    /**
     * Trigger performance alert
     */
    private function triggerAlert(string $type, array $data): void
    {
        $alert = [
            'type' => $type,
            'level' => $this->getAlertLevel($type),
            'message' => $this->generateAlertMessage($type, $data),
            'data' => $data,
            'timestamp' => time(),
        ];
        
        $this->alerts[] = $alert;
        
        // Limit alert history
        $maxAlerts = $this->config['max_alerts'] ?? 100;
        if (count($this->alerts) > $maxAlerts) {
            array_shift($this->alerts);
        }
        
        // Store alert in cache
        $this->cache->set(
            "performance_alert_" . time() . "_" . uniqid(),
            $alert,
            3600
        );
    }

    /**
     * Store metric in cache for persistence
     */
    private function storeMetricInCache(string $metricKey, array $data): void
    {
        $cacheKey = "performance_metric_{$metricKey}_" . date('Y-m-d-H');
        
        $existingData = $this->cache->get($cacheKey) ?? [];
        $existingData[] = $data;
        
        // Limit cached metric data
        $maxCachedMetrics = $this->config['max_cached_metrics'] ?? 1000;
        if (count($existingData) > $maxCachedMetrics) {
            array_shift($existingData);
        }
        
        $this->cache->set($cacheKey, $existingData, 86400); // 24 hours
    }

    /**
     * Parse memory limit string to bytes
     */
    private function parseMemoryLimit(string $memoryLimit): int
    {
        $memoryLimit = trim($memoryLimit);
        $last = strtolower($memoryLimit[strlen($memoryLimit) - 1]);
        $value = (int)$memoryLimit;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }

    /**
     * Get system load average
     */
    private function getSystemLoad(): array
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return [
                '1min' => $load[0] ?? 0,
                '5min' => $load[1] ?? 0,
                '15min' => $load[2] ?? 0,
            ];
        }
        
        return ['1min' => 0, '5min' => 0, '15min' => 0];
    }

    /**
     * Get database statistics
     */
    private function getDatabaseStats(): array
    {
        $dbMetrics = $this->getMetrics('database_query');
        $totalQueries = 0;
        $totalTime = 0;
        
        foreach ($dbMetrics as $metrics) {
            foreach ($metrics as $metric) {
                $totalQueries++;
                $totalTime += $metric['execution_time'] ?? 0;
            }
        }
        
        return [
            'total_queries' => $totalQueries,
            'average_execution_time' => $totalQueries > 0 ? $totalTime / $totalQueries : 0,
            'slow_queries' => count($this->getMetrics('slow_query')),
        ];
    }

    /**
     * Get slow operations summary
     */
    private function getSlowOperations(): array
    {
        $slowOps = $this->getMetrics('slow_operation');
        $summary = [];
        
        foreach ($slowOps as $key => $operations) {
            $operationName = substr($key, strlen('slow_operation.'));
            $summary[$operationName] = [
                'count' => count($operations),
                'average_duration' => $this->calculateAverageDuration($operations),
                'max_duration' => $this->getMaxDuration($operations),
            ];
        }
        
        return $summary;
    }

    /**
     * Get alert level for alert type
     */
    private function getAlertLevel(string $type): string
    {
        $levels = [
            'memory_critical' => 'critical',
            'memory_warning' => 'warning',
            'slow_operation' => 'warning',
            'slow_query' => 'warning',
            'slow_endpoint' => 'warning',
        ];
        
        return $levels[$type] ?? 'info';
    }

    /**
     * Generate alert message
     */
    private function generateAlertMessage(string $type, array $data): string
    {
        switch ($type) {
            case 'memory_critical':
                return "Critical memory usage: {$data['current_usage']} bytes";
            case 'memory_warning':
                return "High memory usage: {$data['current_usage']} bytes";
            case 'slow_operation':
                return "Slow operation '{$data['operation']}': {$data['duration']}s";
            case 'slow_query':
                return "Slow database query: {$data['execution_time']}s";
            case 'slow_endpoint':
                return "Slow API endpoint '{$data['endpoint']}': {$data['response_time']}s";
            default:
                return "Performance alert: {$type}";
        }
    }

    /**
     * Record final metrics on shutdown
     */
    private function recordFinalMetrics(): void
    {
        $finalDuration = microtime(true) - $this->requestStartTime;
        $finalMemory = memory_get_peak_usage(true);
        
        $this->recordMetric('request_complete', 'final', [
            'total_duration' => $finalDuration,
            'peak_memory' => $finalMemory,
            'metrics_collected' => count($this->metrics),
            'alerts_triggered' => count($this->alerts),
        ]);
    }

    /**
     * Calculate average duration from operations
     */
    private function calculateAverageDuration(array $operations): float
    {
        if (empty($operations)) {
            return 0;
        }
        
        $total = array_sum(array_column($operations, 'duration'));
        return $total / count($operations);
    }

    /**
     * Get maximum duration from operations
     */
    private function getMaxDuration(array $operations): float
    {
        if (empty($operations)) {
            return 0;
        }
        
        return max(array_column($operations, 'duration'));
    }

    /**
     * Get top slow operations
     */
    private function getTopSlowOperations(int $limit): array
    {
        $slowOps = $this->getSlowOperations();
        
        uasort($slowOps, function($a, $b) {
            return $b['max_duration'] <=> $a['max_duration'];
        });
        
        return array_slice($slowOps, 0, $limit, true);
    }

    /**
     * Get top slow queries
     */
    private function getTopSlowQueries(int $limit): array
    {
        $slowQueries = $this->getMetrics('slow_query');
        $queries = [];
        
        foreach ($slowQueries as $queryMetrics) {
            foreach ($queryMetrics as $metric) {
                $queries[] = $metric;
            }
        }
        
        usort($queries, function($a, $b) {
            return $b['execution_time'] <=> $a['execution_time'];
        });
        
        return array_slice($queries, 0, $limit);
    }

    /**
     * Get endpoint performance statistics
     */
    private function getEndpointPerformance(): array
    {
        $endpointMetrics = $this->getMetrics('api_endpoint');
        $performance = [];
        
        foreach ($endpointMetrics as $key => $metrics) {
            $endpoint = substr($key, strlen('api_endpoint.'));
            $responseTimes = array_column($metrics, 'response_time');
            
            $performance[$endpoint] = [
                'request_count' => count($metrics),
                'average_response_time' => array_sum($responseTimes) / count($responseTimes),
                'max_response_time' => max($responseTimes),
                'min_response_time' => min($responseTimes),
            ];
        }
        
        return $performance;
    }

    /**
     * Get memory usage trends
     */
    private function getMemoryTrends(int $startTime, int $endTime): array
    {
        $memoryMetrics = $this->getMetrics('memory_usage');
        $trends = [];
        
        foreach ($memoryMetrics as $metrics) {
            foreach ($metrics as $metric) {
                if ($metric['timestamp'] >= $startTime && $metric['timestamp'] <= $endTime) {
                    $trends[] = [
                        'timestamp' => $metric['timestamp'],
                        'usage' => $metric['current_usage'],
                        'peak' => $metric['peak_usage'],
                    ];
                }
            }
        }
        
        return $trends;
    }

    /**
     * Get error rates
     */
    private function getErrorRates(int $startTime, int $endTime): array
    {
        $errorCodes = [400, 401, 403, 404, 500, 502, 503, 504];
        $rates = [];
        
        foreach ($errorCodes as $code) {
            $rates[$code] = $this->counters["http_status_{$code}"] ?? 0;
        }
        
        return $rates;
    }

    /**
     * Generate performance recommendations
     */
    private function generateRecommendations(): array
    {
        $recommendations = [];
        $summary = $this->getPerformanceSummary();
        
        // Memory recommendations
        $memoryUsagePercent = ($summary['memory_usage']['current'] / $summary['memory_usage']['limit']) * 100;
        if ($memoryUsagePercent > 80) {
            $recommendations[] = 'Consider optimizing memory usage or increasing memory limit';
        }
        
        // Slow operations recommendations
        if (!empty($summary['slow_operations'])) {
            $recommendations[] = 'Optimize slow operations to improve performance';
        }
        
        // Database recommendations
        if ($summary['database_stats']['slow_queries'] > 0) {
            $recommendations[] = 'Review and optimize slow database queries';
        }
        
        return $recommendations;
    }

    /**
     * Convert data to CSV format
     */
    private function convertToCsv(array $data): string
    {
        // Simplified CSV conversion
        $csv = "Type,Name,Value,Timestamp\n";
        
        foreach ($data['metrics'] as $key => $metrics) {
            foreach ($metrics as $metric) {
                $csv .= "{$metric['type']},{$metric['name']}," . json_encode($metric) . ",{$metric['timestamp']}\n";
            }
        }
        
        return $csv;
    }
}