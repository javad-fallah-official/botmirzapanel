<?php

declare(strict_types=1);

namespace BotMirzaPanel\Web\Middleware;

use BotMirzaPanel\Infrastructure\Services\MemoryOptimizationService;
use BotMirzaPanel\Infrastructure\Services\PerformanceMonitoringService;
use BotMirzaPanel\Shared\Contracts\CacheInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Performance Middleware
 * 
 * Applies performance optimizations and monitoring to HTTP requests.
 * Handles response compression, caching headers, and performance metrics.
 */
class PerformanceMiddleware implements MiddlewareInterface
{
    private PerformanceMonitoringService $monitoring;
    private MemoryOptimizationService $memoryOptimization;
    private CacheInterface $cache;
    private array $config;

    public function __construct(
        PerformanceMonitoringService $monitoring,
        MemoryOptimizationService $memoryOptimization,
        CacheInterface $cache,
        array $config = []
    ) {
        $this->monitoring = $monitoring;
        $this->memoryOptimization = $memoryOptimization;
        $this->cache = $cache;
        $this->config = $config;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        $requestId = $this->generateRequestId();
        
        // Start monitoring this request
        $this->monitoring->startTimer('request_' . $requestId);
        $this->monitoring->trackMemoryUsage('request_start');
        
        try {
            // Check for cached response
            $cacheKey = $this->generateCacheKey($request);
            if ($this->shouldCache($request)) {
                $cachedResponse = $this->cache->get($cacheKey);
                if ($cachedResponse !== null) {
                    $this->monitoring->incrementCounter('cache_hits');
                    return $this->addPerformanceHeaders($cachedResponse, $startTime, true);
                }
                $this->monitoring->incrementCounter('cache_misses');
            }
            
            // Process the request
            $response = $handler->handle($request);
            
            // Apply performance optimizations
            $response = $this->optimizeResponse($response, $request);
            
            // Cache the response if appropriate
            if ($this->shouldCache($request) && $response->getStatusCode() === 200) {
                $ttl = $this->getCacheTtl($request);
                $this->cache->set($cacheKey, $response, $ttl);
            }
            
            return $response;
            
        } finally {
            // Record performance metrics
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);
            $responseTime = $endTime - $startTime;
            $memoryUsed = $endMemory - $startMemory;
            
            $this->monitoring->stopTimer('request_' . $requestId);
            $this->monitoring->recordMetric('response_time', $request->getUri()->getPath(), [
                'response_time' => $responseTime,
                'timestamp' => time(),
            ]);
            $this->monitoring->trackMemoryUsage('request_end');
            $this->monitoring->recordMetric('memory_usage_diff', 'request', [
                'diff' => $memoryUsed,
                'timestamp' => time(),
            ]);
            
            // Check for performance alerts
            $this->checkPerformanceAlerts($responseTime, $memoryUsed);
            
            // Optimize memory if needed
            $this->memoryOptimization->optimizeMemory();
        }
    }

    /**
     * Optimize the HTTP response
     */
    private function optimizeResponse(ResponseInterface $response, ServerRequestInterface $request): ResponseInterface
    {
        // Add performance headers
        $response = $this->addPerformanceHeaders($response, microtime(true));
        
        // Add caching headers
        $response = $this->addCachingHeaders($response, $request);
        
        // Compress response if enabled
        if ($this->shouldCompress($request, $response)) {
            $response = $this->compressResponse($response, $request);
        }
        
        return $response;
    }

    /**
     * Add performance-related headers
     */
    private function addPerformanceHeaders(ResponseInterface $response, float $startTime, bool $fromCache = false): ResponseInterface
    {
        $responseTime = microtime(true) - $startTime;
        $memoryUsage = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        
        return $response
            ->withHeader('X-Response-Time', sprintf('%.3fms', $responseTime * 1000))
            ->withHeader('X-Memory-Usage', $this->formatBytes($memoryUsage))
            ->withHeader('X-Memory-Peak', $this->formatBytes($peakMemory))
            ->withHeader('X-From-Cache', $fromCache ? 'true' : 'false');
    }

    /**
     * Add caching headers
     */
    private function addCachingHeaders(ResponseInterface $response, ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->shouldCache($request)) {
            return $response->withHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
        }
        
        $ttl = $this->getCacheTtl($request);
        $etag = $this->generateETag($response);
        
        return $response
            ->withHeader('Cache-Control', sprintf('public, max-age=%d', $ttl))
            ->withHeader('ETag', $etag)
            ->withHeader('Vary', 'Accept-Encoding');
    }

    /**
     * Compress response content
     */
    private function compressResponse(ResponseInterface $response, ServerRequestInterface $request): ResponseInterface
    {
        $acceptEncoding = $request->getHeaderLine('Accept-Encoding');
        $content = (string) $response->getBody();
        
        if (strpos($acceptEncoding, 'gzip') !== false && function_exists('gzencode')) {
            $compressed = gzencode($content, $this->config['assets']['compression']['gzip_level'] ?? 6);
            if ($compressed !== false) {
                $response = $response
                    ->withHeader('Content-Encoding', 'gzip')
                    ->withHeader('Content-Length', (string) strlen($compressed));
                
                $response->getBody()->rewind();
                $response->getBody()->write($compressed);
            }
        }
        
        return $response;
    }

    /**
     * Check if request should be cached
     */
    private function shouldCache(ServerRequestInterface $request): bool
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();
        
        // Only cache GET requests
        if ($method !== 'GET') {
            return false;
        }
        
        // Don't cache API endpoints by default
        if (strpos($path, '/api/') === 0) {
            return false;
        }
        
        // Don't cache admin pages
        if (strpos($path, '/admin/') === 0) {
            return false;
        }
        
        return true;
    }

    /**
     * Check if response should be compressed
     */
    private function shouldCompress(ServerRequestInterface $request, ResponseInterface $response): bool
    {
        if (!($this->config['assets']['compression']['enabled'] ?? true)) {
            return false;
        }
        
        $contentType = $response->getHeaderLine('Content-Type');
        $compressibleTypes = [
            'text/html',
            'text/css',
            'text/javascript',
            'application/javascript',
            'application/json',
            'application/xml',
            'text/xml',
        ];
        
        foreach ($compressibleTypes as $type) {
            if (strpos($contentType, $type) === 0) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get cache TTL for request
     */
    private function getCacheTtl(ServerRequestInterface $request): int
    {
        $path = $request->getUri()->getPath();
        
        // Static assets get longer cache
        if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2)$/', $path)) {
            return 86400; // 24 hours
        }
        
        // Regular pages get shorter cache
        return 3600; // 1 hour
    }

    /**
     * Generate cache key for request
     */
    private function generateCacheKey(ServerRequestInterface $request): string
    {
        $uri = $request->getUri();
        $key = sprintf('%s:%s', $request->getMethod(), $uri->getPath());
        
        if ($uri->getQuery()) {
            $key .= '?' . $uri->getQuery();
        }
        
        return 'http_cache:' . md5($key);
    }

    /**
     * Generate ETag for response
     */
    private function generateETag(ResponseInterface $response): string
    {
        $content = (string) $response->getBody();
        return '"' . md5($content) . '"';
    }

    /**
     * Generate unique request ID
     */
    private function generateRequestId(): string
    {
        return uniqid('req_', true);
    }

    /**
     * Check for performance alerts
     */
    private function checkPerformanceAlerts(float $responseTime, int $memoryUsed): void
    {
        $thresholds = $this->config['monitoring']['thresholds'] ?? [];
        
        if ($responseTime > ($thresholds['slow_operation_threshold'] ?? 1.0)) {
            $this->monitoring->recordMetric('alert', 'slow_response', [
                'response_time' => $responseTime,
                'threshold' => $thresholds['slow_operation_threshold'] ?? 1.0,
                'timestamp' => time(),
            ]);
        }
        
        $memoryLimit = $this->parseMemoryLimit($this->config['memory']['memory_limit'] ?? '256M');
        $memoryUsageRatio = $memoryUsed / $memoryLimit;
        
        if ($memoryUsageRatio > ($thresholds['memory_warning_threshold'] ?? 0.8)) {
            $this->monitoring->recordMetric('alert', 'high_memory_usage', [
                'memory_used' => $memoryUsed,
                'memory_limit' => $memoryLimit,
                'usage_ratio' => $memoryUsageRatio,
                'timestamp' => time(),
            ]);
        }
    }

    /**
     * Parse memory limit string to bytes
     */
    private function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $unit = strtolower(substr($limit, -1));
        $value = (int) substr($limit, 0, -1);
        
        switch ($unit) {
            case 'g':
                return $value * 1024 * 1024 * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'k':
                return $value * 1024;
            default:
                return (int) $limit;
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return sprintf('%.2f%s', $bytes, $units[$unitIndex]);
    }
}