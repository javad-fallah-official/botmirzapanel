<?php

/**
 * Helper functions for BotMirzaPanel
 * These functions provide convenient access to common operations
 */

if (!function_exists('env')) {
    /**
     * Get environment variable with optional default
     */
    function env(string $key, $default = null)
    {
        $value = $_ENV[$key] ?? getenv($key);
        
        if ($value === false) {
            return $default;
        }
        
        // Convert string representations to actual types
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }
        
        // Handle quoted strings
        if (strlen($value) > 1 && $value[0] === '"' && $value[-1] === '"') {
            return substr($value, 1, -1);
        }
        
        return $value;
    }
}

if (!function_exists('logger')) {
    /**
     * Simple logging function
     */
    function logger(string $message, string $level = 'info', array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logMessage = "[{$timestamp}] {$level}: {$message}{$contextStr}" . PHP_EOL;
        
        $logFile = defined('BOTMIRZAPANEL_LOGS') ? BOTMIRZAPANEL_LOGS . '/app.log' : 'app.log';
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}

if (!function_exists('formatBytes')) {
    /**
     * Format bytes to human readable format
     */
    function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

if (!function_exists('formatDuration')) {
    /**
     * Format duration in seconds to human readable format
     */
    function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' seconds';
        }
        
        if ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $remainingSeconds = $seconds % 60;
            return $minutes . ' minutes' . ($remainingSeconds > 0 ? ' ' . $remainingSeconds . ' seconds' : '');
        }
        
        if ($seconds < 86400) {
            $hours = floor($seconds / 3600);
            $remainingMinutes = floor(($seconds % 3600) / 60);
            return $hours . ' hours' . ($remainingMinutes > 0 ? ' ' . $remainingMinutes . ' minutes' : '');
        }
        
        $days = floor($seconds / 86400);
        $remainingHours = floor(($seconds % 86400) / 3600);
        return $days . ' days' . ($remainingHours > 0 ? ' ' . $remainingHours . ' hours' : '');
    }
}

if (!function_exists('generateRandomString')) {
    /**
     * Generate a random string
     */
    function generateRandomString(int $length = 32, string $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'): string
    {
        $charactersLength = strlen($characters);
        $randomString = '';
        
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        
        return $randomString;
    }
}

if (!function_exists('generateUuid')) {
    /**
     * Generate a UUID v4
     */
    function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Set bits 6-7 to 10
        
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

if (!function_exists('sanitizeInput')) {
    /**
     * Sanitize user input
     */
    function sanitizeInput(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('validateEmail')) {
    /**
     * Validate email address
     */
    function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('validateUrl')) {
    /**
     * Validate URL
     */
    function validateUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}

if (!function_exists('validateIp')) {
    /**
     * Validate IP address
     */
    function validateIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }
}

if (!function_exists('encryptData')) {
    /**
     * Encrypt data using AES-256-GCM
     */
    function encryptData(string $data, string $key): array
    {
        $iv = random_bytes(12); // 96-bit IV for GCM
        $tag = '';
        
        $encrypted = openssl_encrypt(
            $data,
            'aes-256-gcm',
            hash('sha256', $key, true),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        return [
            'data' => base64_encode($encrypted),
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag)
        ];
    }
}

if (!function_exists('decryptData')) {
    /**
     * Decrypt data using AES-256-GCM
     */
    function decryptData(array $encryptedData, string $key): ?string
    {
        if (!isset($encryptedData['data'], $encryptedData['iv'], $encryptedData['tag'])) {
            return null;
        }
        
        $decrypted = openssl_decrypt(
            base64_decode($encryptedData['data']),
            'aes-256-gcm',
            hash('sha256', $key, true),
            OPENSSL_RAW_DATA,
            base64_decode($encryptedData['iv']),
            base64_decode($encryptedData['tag'])
        );
        
        return $decrypted !== false ? $decrypted : null;
    }
}

if (!function_exists('hashPassword')) {
    /**
     * Hash password using PHP's password_hash
     */
    function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID);
    }
}

if (!function_exists('verifyPassword')) {
    /**
     * Verify password against hash
     */
    function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}

if (!function_exists('formatCurrency')) {
    /**
     * Format currency amount
     */
    function formatCurrency(float $amount, string $currency = 'USD', string $locale = 'en_US'): string
    {
        if (class_exists('NumberFormatter')) {
            $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
            return $formatter->formatCurrency($amount, $currency);
        }
        
        // Fallback formatting
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'IRR' => 'ریال',
            'IRT' => 'تومان'
        ];
        
        $symbol = $symbols[$currency] ?? $currency;
        return $symbol . number_format($amount, 2);
    }
}

if (!function_exists('parseUserAgent')) {
    /**
     * Parse user agent string
     */
    function parseUserAgent(string $userAgent): array
    {
        $result = [
            'browser' => 'Unknown',
            'version' => 'Unknown',
            'platform' => 'Unknown'
        ];
        
        // Simple user agent parsing
        if (preg_match('/Chrome\/([0-9.]+)/', $userAgent, $matches)) {
            $result['browser'] = 'Chrome';
            $result['version'] = $matches[1];
        } elseif (preg_match('/Firefox\/([0-9.]+)/', $userAgent, $matches)) {
            $result['browser'] = 'Firefox';
            $result['version'] = $matches[1];
        } elseif (preg_match('/Safari\/([0-9.]+)/', $userAgent, $matches)) {
            $result['browser'] = 'Safari';
            $result['version'] = $matches[1];
        }
        
        if (strpos($userAgent, 'Windows') !== false) {
            $result['platform'] = 'Windows';
        } elseif (strpos($userAgent, 'Mac') !== false) {
            $result['platform'] = 'macOS';
        } elseif (strpos($userAgent, 'Linux') !== false) {
            $result['platform'] = 'Linux';
        } elseif (strpos($userAgent, 'Android') !== false) {
            $result['platform'] = 'Android';
        } elseif (strpos($userAgent, 'iOS') !== false) {
            $result['platform'] = 'iOS';
        }
        
        return $result;
    }
}

if (!function_exists('getClientIp')) {
    /**
     * Get client IP address
     */
    function getClientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                if (validateIp($ip) && !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}

if (!function_exists('isBot')) {
    /**
     * Check if request is from a bot
     */
    function isBot(string $userAgent = null): bool
    {
        $userAgent = $userAgent ?? $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $bots = [
            'googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider',
            'yandexbot', 'facebookexternalhit', 'twitterbot', 'rogerbot',
            'linkedinbot', 'embedly', 'quora link preview', 'showyoubot',
            'outbrain', 'pinterest', 'developers.google.com/+/web/snippet'
        ];
        
        foreach ($bots as $bot) {
            if (stripos($userAgent, $bot) !== false) {
                return true;
            }
        }
        
        return false;
    }
}

if (!function_exists('rateLimitCheck')) {
    /**
     * Simple rate limiting check
     */
    function rateLimitCheck(string $key, int $maxRequests = 60, int $timeWindow = 3600): bool
    {
        $cacheFile = sys_get_temp_dir() . '/rate_limit_' . md5($key) . '.json';
        
        $data = [];
        if (file_exists($cacheFile)) {
            $content = file_get_contents($cacheFile);
            $data = json_decode($content, true) ?: [];
        }
        
        $now = time();
        $windowStart = $now - $timeWindow;
        
        // Clean old entries
        $data = array_filter($data, function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });
        
        // Check if limit exceeded
        if (count($data) >= $maxRequests) {
            return false;
        }
        
        // Add current request
        $data[] = $now;
        
        // Save to cache
        file_put_contents($cacheFile, json_encode($data), LOCK_EX);
        
        return true;
    }
}

if (!function_exists('arrayGet')) {
    /**
     * Get an item from an array using "dot" notation
     */
    function arrayGet(array $array, string $key, $default = null)
    {
        if (isset($array[$key])) {
            return $array[$key];
        }
        
        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }
        
        return $array;
    }
}

if (!function_exists('arraySet')) {
    /**
     * Set an array item to a given value using "dot" notation
     */
    function arraySet(array &$array, string $key, $value): void
    {
        $keys = explode('.', $key);
        
        while (count($keys) > 1) {
            $key = array_shift($keys);
            
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }
            
            $array = &$array[$key];
        }
        
        $array[array_shift($keys)] = $value;
    }
}

if (!function_exists('slugify')) {
    /**
     * Generate a URL-friendly slug from a string
     */
    function slugify(string $text, string $separator = '-'): string
    {
        // Replace non-alphanumeric characters with separator
        $text = preg_replace('/[^\p{L}\p{Nd}]+/u', $separator, $text);
        
        // Trim separators from beginning and end
        $text = trim($text, $separator);
        
        // Convert to lowercase
        $text = mb_strtolower($text, 'UTF-8');
        
        return $text;
    }
}