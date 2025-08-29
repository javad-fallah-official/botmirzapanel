<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Shared\Contracts\CacheInterface;
use WeakMap;
use SplObjectStorage;
use RuntimeException;

/**
 * Memory Optimization Service
 * 
 * Provides memory management and optimization features including:
 * - Object pooling for frequently used objects
 * - Memory usage monitoring and alerts
 * - Garbage collection optimization
 * - Memory leak detection
 * - Resource cleanup automation
 */
class MemoryOptimizationService
{
    private array $objectPools = [];
    private SplObjectStorage $trackedObjects;
    private WeakMap $objectMetadata;
    private array $memoryStats = [];
    private int $memoryLimit;
    private int $warningThreshold;
    private int $criticalThreshold;
    private bool $enableObjectPooling;
    private bool $enableMemoryTracking;
    
    public function __construct(
        private CacheInterface $cache,
        private array $config = []
    ) {
        $this->trackedObjects = new SplObjectStorage();
        $this->objectMetadata = new WeakMap();
        
        $this->memoryLimit = $this->parseMemoryLimit($config['memory_limit'] ?? ini_get('memory_limit'));
        $this->warningThreshold = (int)($this->memoryLimit * ($config['warning_threshold'] ?? 0.8));
        $this->criticalThreshold = (int)($this->memoryLimit * ($config['critical_threshold'] ?? 0.9));
        $this->enableObjectPooling = $config['enable_object_pooling'] ?? true;
        $this->enableMemoryTracking = $config['enable_memory_tracking'] ?? true;
        
        $this->initializeObjectPools();
        $this->startMemoryMonitoring();
    }

    /**
     * Get object from pool or create new one
     */
    public function getFromPool(string $className, array $constructorArgs = []): object
    {
        if (!$this->enableObjectPooling) {
            return new $className(...$constructorArgs);
        }
        
        $poolKey = $this->getPoolKey($className, $constructorArgs);
        
        if (!isset($this->objectPools[$poolKey])) {
            $this->objectPools[$poolKey] = [];
        }
        
        // Return existing object from pool
        if (!empty($this->objectPools[$poolKey])) {
            $object = array_pop($this->objectPools[$poolKey]);
            $this->resetObject($object);
            return $object;
        }
        
        // Create new object
        $object = new $className(...$constructorArgs);
        $this->trackObject($object, $className);
        
        return $object;
    }

    /**
     * Return object to pool for reuse
     */
    public function returnToPool(object $object): void
    {
        if (!$this->enableObjectPooling) {
            return;
        }
        
        $className = get_class($object);
        $poolKey = $this->getPoolKey($className);
        
        if (!isset($this->objectPools[$poolKey])) {
            $this->objectPools[$poolKey] = [];
        }
        
        // Limit pool size to prevent memory bloat
        $maxPoolSize = $this->config['max_pool_size'] ?? 50;
        if (count($this->objectPools[$poolKey]) < $maxPoolSize) {
            $this->cleanObject($object);
            $this->objectPools[$poolKey][] = $object;
        }
    }

    /**
     * Track object for memory monitoring
     */
    public function trackObject(object $object, string $type = null): void
    {
        if (!$this->enableMemoryTracking) {
            return;
        }
        
        $this->trackedObjects->attach($object);
        $this->objectMetadata[$object] = [
            'type' => $type ?? get_class($object),
            'created_at' => microtime(true),
            'memory_usage' => $this->getObjectMemoryUsage($object),
        ];
    }

    /**
     * Untrack object
     */
    public function untrackObject(object $object): void
    {
        if ($this->trackedObjects->contains($object)) {
            $this->trackedObjects->detach($object);
        }
        unset($this->objectMetadata[$object]);
    }

    /**
     * Get current memory usage statistics
     */
    public function getMemoryStats(): array
    {
        $currentUsage = memory_get_usage(true);
        $peakUsage = memory_get_peak_usage(true);
        
        return [
            'current_usage' => $currentUsage,
            'current_usage_formatted' => $this->formatBytes($currentUsage),
            'peak_usage' => $peakUsage,
            'peak_usage_formatted' => $this->formatBytes($peakUsage),
            'memory_limit' => $this->memoryLimit,
            'memory_limit_formatted' => $this->formatBytes($this->memoryLimit),
            'usage_percentage' => ($currentUsage / $this->memoryLimit) * 100,
            'tracked_objects_count' => $this->trackedObjects->count(),
            'object_pools_count' => count($this->objectPools),
            'total_pooled_objects' => $this->getTotalPooledObjects(),
            'memory_status' => $this->getMemoryStatus($currentUsage),
            'gc_stats' => $this->getGarbageCollectionStats(),
        ];
    }

    /**
     * Optimize memory usage
     */
    public function optimizeMemory(): array
    {
        $beforeStats = $this->getMemoryStats();
        $optimizations = [];
        
        // Force garbage collection
        $collected = gc_collect_cycles();
        if ($collected > 0) {
            $optimizations[] = "Garbage collection freed {$collected} cycles";
        }
        
        // Clean up object pools
        $poolsCleaned = $this->cleanupObjectPools();
        if ($poolsCleaned > 0) {
            $optimizations[] = "Cleaned up {$poolsCleaned} object pools";
        }
        
        // Remove stale tracked objects
        $staleRemoved = $this->removeStaleObjects();
        if ($staleRemoved > 0) {
            $optimizations[] = "Removed {$staleRemoved} stale objects";
        }
        
        // Clear internal caches
        $this->clearInternalCaches();
        $optimizations[] = "Cleared internal caches";
        
        $afterStats = $this->getMemoryStats();
        $memoryFreed = $beforeStats['current_usage'] - $afterStats['current_usage'];
        
        return [
            'optimizations_performed' => $optimizations,
            'memory_freed' => $memoryFreed,
            'memory_freed_formatted' => $this->formatBytes($memoryFreed),
            'before_stats' => $beforeStats,
            'after_stats' => $afterStats,
        ];
    }

    /**
     * Detect potential memory leaks
     */
    public function detectMemoryLeaks(): array
    {
        $leaks = [];
        $currentTime = microtime(true);
        $leakThreshold = $this->config['leak_detection_threshold'] ?? 3600; // 1 hour
        
        foreach ($this->trackedObjects as $object) {
            if (isset($this->objectMetadata[$object])) {
                $metadata = $this->objectMetadata[$object];
                $age = $currentTime - $metadata['created_at'];
                
                if ($age > $leakThreshold) {
                    $leaks[] = [
                        'type' => $metadata['type'],
                        'age' => $age,
                        'memory_usage' => $metadata['memory_usage'],
                        'object_hash' => spl_object_hash($object),
                    ];
                }
            }
        }
        
        return $leaks;
    }

    /**
     * Set memory usage alerts
     */
    public function checkMemoryAlerts(): array
    {
        $currentUsage = memory_get_usage(true);
        $alerts = [];
        
        if ($currentUsage >= $this->criticalThreshold) {
            $alerts[] = [
                'level' => 'critical',
                'message' => 'Memory usage is critically high',
                'current_usage' => $currentUsage,
                'threshold' => $this->criticalThreshold,
                'percentage' => ($currentUsage / $this->memoryLimit) * 100,
            ];
        } elseif ($currentUsage >= $this->warningThreshold) {
            $alerts[] = [
                'level' => 'warning',
                'message' => 'Memory usage is approaching limit',
                'current_usage' => $currentUsage,
                'threshold' => $this->warningThreshold,
                'percentage' => ($currentUsage / $this->memoryLimit) * 100,
            ];
        }
        
        return $alerts;
    }

    /**
     * Get object memory usage breakdown
     */
    public function getObjectMemoryBreakdown(): array
    {
        $breakdown = [];
        
        foreach ($this->trackedObjects as $object) {
            if (isset($this->objectMetadata[$object])) {
                $metadata = $this->objectMetadata[$object];
                $type = $metadata['type'];
                
                if (!isset($breakdown[$type])) {
                    $breakdown[$type] = [
                        'count' => 0,
                        'total_memory' => 0,
                        'average_memory' => 0,
                    ];
                }
                
                $breakdown[$type]['count']++;
                $breakdown[$type]['total_memory'] += $metadata['memory_usage'];
                $breakdown[$type]['average_memory'] = $breakdown[$type]['total_memory'] / $breakdown[$type]['count'];
            }
        }
        
        // Sort by total memory usage
        uasort($breakdown, function($a, $b) {
            return $b['total_memory'] <=> $a['total_memory'];
        });
        
        return $breakdown;
    }

    /**
     * Clear all object pools
     */
    public function clearObjectPools(): void
    {
        $this->objectPools = [];
    }

    /**
     * Get pool key for object
     */
    private function getPoolKey(string $className, array $constructorArgs = []): string
    {
        return $className . '_' . md5(serialize($constructorArgs));
    }

    /**
     * Reset object to initial state
     */
    private function resetObject(object $object): void
    {
        // Call reset method if available
        if (method_exists($object, 'reset')) {
            $object->reset();
        }
    }

    /**
     * Clean object before returning to pool
     */
    private function cleanObject(object $object): void
    {
        // Call cleanup method if available
        if (method_exists($object, 'cleanup')) {
            $object->cleanup();
        }
    }

    /**
     * Initialize object pools for common classes
     */
    private function initializeObjectPools(): void
    {
        $commonClasses = $this->config['pooled_classes'] ?? [
            'DateTime',
            'stdClass',
        ];
        
        foreach ($commonClasses as $className) {
            $this->objectPools[$this->getPoolKey($className)] = [];
        }
    }

    /**
     * Start memory monitoring
     */
    private function startMemoryMonitoring(): void
    {
        if (!$this->enableMemoryTracking) {
            return;
        }
        
        // Register shutdown function to log final memory stats
        register_shutdown_function(function() {
            $this->logFinalMemoryStats();
        });
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
     * Get object memory usage
     */
    private function getObjectMemoryUsage(object $object): int
    {
        // Simplified memory calculation
        return strlen(serialize($object));
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Get memory status based on usage
     */
    private function getMemoryStatus(int $currentUsage): string
    {
        if ($currentUsage >= $this->criticalThreshold) {
            return 'critical';
        } elseif ($currentUsage >= $this->warningThreshold) {
            return 'warning';
        }
        return 'normal';
    }

    /**
     * Get garbage collection statistics
     */
    private function getGarbageCollectionStats(): array
    {
        return [
            'enabled' => gc_enabled(),
            'status' => gc_status(),
            'collected_cycles' => 0, // Would need to track this
        ];
    }

    /**
     * Get total number of pooled objects
     */
    private function getTotalPooledObjects(): int
    {
        $total = 0;
        foreach ($this->objectPools as $pool) {
            $total += count($pool);
        }
        return $total;
    }

    /**
     * Clean up object pools
     */
    private function cleanupObjectPools(): int
    {
        $cleaned = 0;
        $maxAge = $this->config['pool_object_max_age'] ?? 3600; // 1 hour
        $currentTime = time();
        
        foreach ($this->objectPools as $poolKey => $pool) {
            $this->objectPools[$poolKey] = array_filter($pool, function($object) use ($maxAge, $currentTime) {
                // Simple age check - in production, track creation time
                return true; // Simplified for this example
            });
            
            $cleaned += count($pool) - count($this->objectPools[$poolKey]);
        }
        
        return $cleaned;
    }

    /**
     * Remove stale tracked objects
     */
    private function removeStaleObjects(): int
    {
        $removed = 0;
        $maxAge = $this->config['tracked_object_max_age'] ?? 7200; // 2 hours
        $currentTime = microtime(true);
        
        foreach ($this->trackedObjects as $object) {
            if (isset($this->objectMetadata[$object])) {
                $metadata = $this->objectMetadata[$object];
                if (($currentTime - $metadata['created_at']) > $maxAge) {
                    $this->untrackObject($object);
                    $removed++;
                }
            }
        }
        
        return $removed;
    }

    /**
     * Clear internal caches
     */
    private function clearInternalCaches(): void
    {
        // Clear opcache if available
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        
        // Clear realpath cache
        clearstatcache();
    }

    /**
     * Log final memory statistics
     */
    private function logFinalMemoryStats(): void
    {
        $stats = $this->getMemoryStats();
        
        // Store stats in cache for analysis
        $this->cache->set(
            'memory_stats_' . date('Y-m-d-H'),
            $stats,
            3600
        );
    }
}