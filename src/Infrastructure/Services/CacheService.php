<?php
// WARNING: SQL injection vulnerability detected in this file
// Please review and use prepared statements or secure_* functions


declare(strict_types=1);

namespace BotMirzaPanel\Infrastructure\Services;

use BotMirzaPanel\Shared\Contracts\CacheInterface;
use Psr\Log\LoggerInterface;
use Redis;
use Exception;
use InvalidArgumentException;

/**
 * Cache Service
 * 
 * Provides high-performance caching capabilities with Redis backend,
 * including cache tagging, serialization, compression, and statistics.
 * 
 * Features:
 * - Multiple cache strategies (write-through, write-behind, read-through)
 * - Cache tagging for bulk invalidation
 * - Automatic serialization and compression
 * - Cache statistics and monitoring
 * - Distributed cache support
 * - Cache warming and preloading
 */
class CacheService implements CacheInterface
{
    private Redis $redis;
    private LoggerInterface $logger;
    private array $config;
    private array $stats = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
        'deletes' => 0,
        'flushes' => 0,
    ];

    public function __construct(
        Redis $redis,
        LoggerInterface $logger,
        array $config = []
    ) {
        $this->redis = $redis;
        $this->logger = $logger;
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    /**
     * Get value from cache
     */
    public function get(string $key, mixed $default = null): mixed
    {
        try {
            $fullKey = $this->buildKey($key);
            $value = $this->redis->get($fullKey);

            if ($value === false) {
                $this->stats['misses']++;
                $this->logCacheOperation('miss', $key);
                return $default;
            }

            $this->stats['hits']++;
            $this->logCacheOperation('hit', $key);
            
            return $this->unserialize($value);
        } catch (Exception $e) {
            $this->logger->error('Cache get failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return $default;
        }
    }

    /**
     * Store value in cache
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        try {
            $fullKey = $this->buildKey($key);
            $serializedValue = $this->serialize($value);
            $ttl = $ttl ?? $this->config['default_ttl'];

            $result = $this->redis->setex($fullKey, $ttl, $serializedValue);
            
            if ($result) {
                $this->stats['sets']++;
                $this->logCacheOperation('set', $key, ['ttl' => $ttl]);
                
                // Update cache tags if configured
                $this->updateCacheTags($key, $ttl);
            }

            return $result;
        } catch (Exception $e) {
            $this->logger->error('Cache set failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if key exists in cache
     */
    public function has(string $key): bool
    {
        try {
            $fullKey = $this->buildKey($key);
            return $this->redis->exists($fullKey) > 0;
        } catch (Exception $e) {
            $this->logger->error('Cache has failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Delete key from cache
     */
    public function delete(string $key): bool
    {
        try {
            $fullKey = $this->buildKey($key);
            $result = $this->redis->del($fullKey) > 0;
            
            if ($result) {
                $this->stats['deletes']++;
                $this->logCacheOperation('delete', $key);
                
                // Remove from cache tags
                $this->removeCacheTags($key);
            }

            return $result;
        } catch (Exception $e) {
            $this->logger->error('Cache delete failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Clear all cache
     */
    public function clear(): bool
    {
        try {
            $pattern = $this->config['prefix'] . '*';
            $keys = $this->redis->keys($pattern);
            
            if (!empty($keys)) {
                $result = $this->redis->del($keys) > 0;
            } else {
                $result = true;
            }
            
            if ($result) {
                $this->stats['flushes']++;
                $this->logCacheOperation('flush');
            }

            return $result;
        } catch (Exception $e) {
            $this->logger->error('Cache clear failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get multiple values from cache
     */
    public function getMultiple(array $keys, mixed $default = null): array
    {
        $result = [];
        $fullKeys = array_map([$this, 'buildKey'], $keys);
        
        try {
            $values = $this->redis->mget($fullKeys);
            
            foreach ($keys as $index => $key) {
                if ($values[$index] !== false) {
                    $result[$key] = $this->unserialize($values[$index]);
                    $this->stats['hits']++;
                } else {
                    $result[$key] = $default;
                    $this->stats['misses']++;
                }
            }
        } catch (Exception $e) {
            $this->logger->error('Cache getMultiple failed', [
                'keys' => $keys,
                'error' => $e->getMessage()
            ]);
            
            // Return default values for all keys
            foreach ($keys as $key) {
                $result[$key] = $default;
            }
        }

        return $result;
    }

    /**
     * Set multiple values in cache
     */
    public function setMultiple(array $values, ?int $ttl = null): bool
    {
        try {
            $ttl = $ttl ?? $this->config['default_ttl'];
            $pipeline = $this->redis->multi();
            
            foreach ($values as $key => $value) {
                $fullKey = $this->buildKey($key);
                $serializedValue = $this->serialize($value);
                $pipeline->setex($fullKey, $ttl, $serializedValue);
            }
            
            $results = $pipeline->exec();
            $success = !in_array(false, $results, true);
            
            if ($success) {
                $this->stats['sets'] += count($values);
                $this->logCacheOperation('setMultiple', implode(',', array_keys($values)));
            }

            return $success;
        } catch (Exception $e) {
            $this->logger->error('Cache setMultiple failed', [
                'keys' => array_keys($values),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Delete multiple keys from cache
     */
    public function deleteMultiple(array $keys): bool
    {
        try {
            $fullKeys = array_map([$this, 'buildKey'], $keys);
            $result = $this->redis->del($fullKeys) > 0;
            
            if ($result) {
                $this->stats['deletes'] += count($keys);
                $this->logCacheOperation('deleteMultiple', implode(',', $keys));
            }

            return $result;
        } catch (Exception $e) {
            $this->logger->error('Cache deleteMultiple failed', [
                'keys' => $keys,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Remember (get or set) cache value
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }

    /**
     * Remember forever (get or set with no expiration)
     */
    public function rememberForever(string $key, callable $callback): mixed
    {
        return $this->remember($key, $callback, 0); // 0 = no expiration
    }

    /**
     * Increment cache value
     */
    public function increment(string $key, int $value = 1): int|false
    {
        try {
            $fullKey = $this->buildKey($key);
            return $this->redis->incrBy($fullKey, $value);
        } catch (Exception $e) {
            $this->logger->error('Cache increment failed', [
                'key' => $key,
                'value' => $value,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Decrement cache value
     */
    public function decrement(string $key, int $value = 1): int|false
    {
        try {
            $fullKey = $this->buildKey($key);
            return $this->redis->decrBy($fullKey, $value);
        } catch (Exception $e) {
            $this->logger->error('Cache decrement failed', [
                'key' => $key,
                'value' => $value,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Add value to cache only if key doesn't exist
     */
    public function add(string $key, mixed $value, ?int $ttl = null): bool
    {
        try {
            $fullKey = $this->buildKey($key);
            $serializedValue = $this->serialize($value);
            $ttl = $ttl ?? $this->config['default_ttl'];
            
            // Use SET with NX (only if not exists) and EX (expiration)
            $result = $this->redis->set($fullKey, $serializedValue, ['NX', 'EX' => $ttl]);
            
            if ($result) {
                $this->stats['sets']++;
                $this->logCacheOperation('add', $key, ['ttl' => $ttl]);
            }

            return $result !== false;
        } catch (Exception $e) {
            $this->logger->error('Cache add failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        $hitRate = $this->stats['hits'] + $this->stats['misses'] > 0 
            ? round(($this->stats['hits'] / ($this->stats['hits'] + $this->stats['misses'])) * 100, 2)
            : 0;

        return array_merge($this->stats, [
            'hit_rate' => $hitRate,
            'memory_usage' => $this->getMemoryUsage(),
            'connection_info' => $this->getConnectionInfo(),
        ]);
    }

    /**
     * Flush cache by tags
     */
    public function flushTags(array $tags): bool
    {
        try {
            $keys = [];
            
            foreach ($tags as $tag) {
                $tagKey = $this->buildTagKey($tag);
                $taggedKeys = $this->redis->sMembers($tagKey);
                $keys = array_merge($keys, $taggedKeys);
                
                // Remove the tag set itself
                $this->redis->del($tagKey);
            }
            
            if (!empty($keys)) {
                $this->redis->del(array_unique($keys));
            }
            
            $this->logCacheOperation('flushTags', implode(',', $tags));
            return true;
        } catch (Exception $e) {
            $this->logger->error('Cache flushTags failed', [
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Warm up cache with predefined data
     */
    public function warmUp(array $data): bool
    {
        try {
            $pipeline = $this->redis->multi();
            
            foreach ($data as $key => $config) {
                $value = is_callable($config['value']) ? $config['value']() : $config['value'];
                $ttl = $config['ttl'] ?? $this->config['default_ttl'];
                
                $fullKey = $this->buildKey($key);
                $serializedValue = $this->serialize($value);
                $pipeline->setex($fullKey, $ttl, $serializedValue);
            }
            
            $results = $pipeline->exec();
            $success = !in_array(false, $results, true);
            
            if ($success) {
                $this->logCacheOperation('warmUp', 'keys: ' . count($data));
            }

            return $success;
        } catch (Exception $e) {
            $this->logger->error('Cache warmUp failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Build full cache key with prefix
     */
    private function buildKey(string $key): string
    {
        if (empty($key)) {
            throw new InvalidArgumentException('Cache key cannot be empty');
        }
        
        return $this->config['prefix'] . $key;
    }

    /**
     * Build tag key
     */
    private function buildTagKey(string $tag): string
    {
        return $this->config['prefix'] . 'tags:' . $tag;
    }

    /**
     * Serialize value for storage
     */
    private function serialize(mixed $value): string
    {
        $serialized = serialize($value);
        
        if ($this->config['compression']['enabled'] && 
            strlen($serialized) > $this->config['compression']['threshold']) {
            $compressed = gzcompress($serialized, $this->config['compression']['level']);
            return 'compressed:' . $compressed;
        }
        
        return $serialized;
    }

    /**
     * Unserialize value from storage
     */
    private function unserialize(string $value): mixed
    {
        if (str_starts_with($value, 'compressed:')) {
            $compressed = substr($value, 11);
            $value = gzuncompress($compressed);
        }
        
        return unserialize($value);
    }

    /**
     * Update cache tags for a key
     */
    private function updateCacheTags(string $key, int $ttl): void
    {
        if (!isset($this->config['tags'])) {
            return;
        }
        
        $fullKey = $this->buildKey($key);
        
        foreach ($this->config['tags'] as $tag) {
            $tagKey = $this->buildTagKey($tag);
            $this->redis->sAdd($tagKey, $fullKey);
            $this->redis->expire($tagKey, $ttl);
        }
    }

    /**
     * Remove cache tags for a key
     */
    private function removeCacheTags(string $key): void
    {
        if (!isset($this->config['tags'])) {
            return;
        }
        
        $fullKey = $this->buildKey($key);
        
        foreach ($this->config['tags'] as $tag) {
            $tagKey = $this->buildTagKey($tag);
            $this->redis->sRem($tagKey, $fullKey);
        }
    }

    /**
     * Get memory usage information
     */
    private function getMemoryUsage(): array
    {
        try {
            $info = $this->redis->info('memory');
            return [
                'used_memory' => $info['used_memory'] ?? 0,
                'used_memory_human' => $info['used_memory_human'] ?? '0B',
                'used_memory_peak' => $info['used_memory_peak'] ?? 0,
                'used_memory_peak_human' => $info['used_memory_peak_human'] ?? '0B',
            ];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get connection information
     */
    private function getConnectionInfo(): array
    {
        try {
            $info = $this->redis->info('server');
            return [
                'redis_version' => $info['redis_version'] ?? 'unknown',
                'connected_clients' => $info['connected_clients'] ?? 0,
                'uptime_in_seconds' => $info['uptime_in_seconds'] ?? 0,
            ];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Log cache operation
     */
    private function logCacheOperation(string $operation, string $key = '', array $context = []): void
    {
        if (!$this->config['logging']['enabled']) {
            return;
        }
        
        $this->logger->debug('Cache operation', array_merge([
            'operation' => $operation,
            'key' => $key,
        ], $context));
    }

    /**
     * Get default configuration
     */
    private function getDefaultConfig(): array
    {
        return [
            'prefix' => 'bmp:',
            'default_ttl' => 3600,
            'compression' => [
                'enabled' => true,
                'threshold' => 1024, // 1KB
                'level' => 6,
            ],
            'logging' => [
                'enabled' => false,
            ],
        ];
    }
}