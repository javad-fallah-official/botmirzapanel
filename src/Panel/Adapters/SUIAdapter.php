<?php

namespace BotMirzaPanel\Panel\Adapters;

/**
 * S-UI panel adapter
 * Handles integration with S-UI panel (similar to X-UI but different API)
 */
class SUIAdapter implements PanelAdapterInterface
{
    private array $config = [];
    private string $baseUrl;
    private ?string $token = null;
    private array $headers = [];

    public function configure(array $config): void
    {
        $this->config = $config;
        $this->baseUrl = rtrim($config['url'], '/');
        $this->authenticate();
    }

    public function testConnection(): bool
    {
        try {
            $response = $this->makeRequest('GET', '/api/status');
            return $response !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getPanelInfo(): array
    {
        try {
            $status = $this->makeRequest('GET', '/api/status');
            $info = $this->makeRequest('GET', '/api/info');
            
            return [
                'version' => $info['version'] ?? 'unknown',
                'uptime' => $status['uptime'] ?? 0,
                'cpu_usage' => $status['cpu'] ?? 0,
                'memory_usage' => $status['memory']['used'] ?? 0,
                'total_memory' => $status['memory']['total'] ?? 0,
                'disk_usage' => $status['disk']['used'] ?? 0,
                'total_disk' => $status['disk']['total'] ?? 0,
                'total_users' => $this->getTotalUsersCount(),
                'active_users' => $this->getActiveUsersCount()
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    public function getCapabilities(): array
    {
        return [
            'create_user' => true,
            'update_user' => true,
            'delete_user' => true,
            'get_user_config' => true,
            'get_user_stats' => true,
            'enable_disable_user' => true,
            'reset_user_data' => true,
            'bulk_operations' => true,
            'inbound_management' => true,
            'system_stats' => true,
            'protocols' => ['vmess', 'vless', 'trojan', 'shadowsocks']
        ];
    }

    public function createUser(array $userData): array
    {
        try {
            $userPayload = $this->prepareUserPayload($userData);
            
            $response = $this->makeRequest('POST', '/api/users', $userPayload);
            
            if ($response && isset($response['id'])) {
                return [
                    'success' => true,
                    'username' => $userData['username'],
                    'user_id' => $response['id'],
                    'subscription_url' => $response['subscription_url'] ?? null,
                    'data' => $response
                ];
            }
            
            return ['success' => false, 'error' => $response['message'] ?? 'Failed to create user'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function updateUser(string $username, array $userData): array
    {
        try {
            $user = $this->findUserByUsername($username);
            if (!$user) {
                return ['success' => false, 'error' => 'User not found'];
            }
            
            $userPayload = $this->prepareUserPayload($userData, true);
            
            $response = $this->makeRequest('PUT', "/api/users/{$user['id']}", $userPayload);
            
            if ($response) {
                return [
                    'success' => true,
                    'username' => $username,
                    'data' => $response
                ];
            }
            
            return ['success' => false, 'error' => 'Failed to update user'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function deleteUser(string $username): bool
    {
        try {
            $user = $this->findUserByUsername($username);
            if (!$user) {
                return false;
            }
            
            $response = $this->makeRequest('DELETE', "/api/users/{$user['id']}");
            return $response !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getUser(string $username): ?array
    {
        try {
            $user = $this->findUserByUsername($username);
            return $user;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getAllUsers(): array
    {
        try {
            $response = $this->makeRequest('GET', '/api/users');
            return $response['users'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getUserConfig(string $username): ?string
    {
        try {
            $user = $this->findUserByUsername($username);
            if (!$user) {
                return null;
            }
            
            $response = $this->makeRequest('GET', "/api/users/{$user['id']}/config");
            return $response['config'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getUserStats(string $username): ?array
    {
        try {
            $user = $this->findUserByUsername($username);
            if (!$user) {
                return null;
            }
            
            $stats = $this->makeRequest('GET', "/api/users/{$user['id']}/stats");
            
            return [
                'username' => $user['username'],
                'upload' => $stats['upload'] ?? 0,
                'download' => $stats['download'] ?? 0,
                'total' => $stats['total'] ?? 0,
                'limit' => $user['data_limit'] ?? 0,
                'expiry' => $user['expiry_date'] ?? null,
                'enabled' => $user['enabled'] ?? true,
                'online' => $stats['online'] ?? false,
                'last_connection' => $stats['last_connection'] ?? null
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    public function enableUser(string $username): bool
    {
        return $this->setUserStatus($username, true);
    }

    public function disableUser(string $username): bool
    {
        return $this->setUserStatus($username, false);
    }

    public function resetUserData(string $username): bool
    {
        try {
            $user = $this->findUserByUsername($username);
            if (!$user) {
                return false;
            }
            
            $response = $this->makeRequest('POST', "/api/users/{$user['id']}/reset-traffic");
            return $response !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getInbounds(): array
    {
        try {
            $response = $this->makeRequest('GET', '/api/inbounds');
            return $response['inbounds'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getSystemStats(): array
    {
        try {
            $response = $this->makeRequest('GET', '/api/status');
            return $response ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Authenticate with S-UI panel
     */
    private function authenticate(): void
    {
        try {
            $response = $this->makeRequest('POST', '/api/auth/login', [
                'username' => $this->config['username'],
                'password' => $this->config['password']
            ], false);
            
            if ($response && isset($response['token'])) {
                $this->token = $response['token'];
                $this->headers = [
                    'Authorization: Bearer ' . $this->token,
                    'Content-Type: application/json'
                ];
            } else {
                throw new \Exception('Failed to authenticate with S-UI panel');
            }
        } catch (\Exception $e) {
            throw new \Exception('S-UI authentication failed: ' . $e->getMessage());
        }
    }

    /**
     * Find user by username
     */
    private function findUserByUsername(string $username): ?array
    {
        $users = $this->getAllUsers();
        
        foreach ($users as $user) {
            if ($user['username'] === $username) {
                return $user;
            }
        }
        
        return null;
    }

    /**
     * Prepare user payload for API requests
     */
    private function prepareUserPayload(array $userData, bool $isUpdate = false): array
    {
        $payload = [];
        
        if (!$isUpdate) {
            $payload['username'] = $userData['username'];
        }
        
        // Map common fields
        $fieldMapping = [
            'data_limit' => 'data_limit',
            'expiry_date' => 'expiry_date',
            'enabled' => 'enabled',
            'note' => 'note',
            'protocol' => 'protocol'
        ];
        
        foreach ($fieldMapping as $key => $apiKey) {
            if (isset($userData[$key])) {
                $payload[$apiKey] = $userData[$key];
            }
        }
        
        // Handle inbound settings
        if (isset($userData['inbound_id'])) {
            $payload['inbound_id'] = $userData['inbound_id'];
        }
        
        // Handle protocol specific settings
        if (isset($userData['settings'])) {
            $payload['settings'] = $userData['settings'];
        }
        
        return $payload;
    }

    /**
     * Set user enable/disable status
     */
    private function setUserStatus(string $username, bool $enabled): bool
    {
        try {
            $user = $this->findUserByUsername($username);
            if (!$user) {
                return false;
            }
            
            $response = $this->makeRequest('PUT', "/api/users/{$user['id']}", [
                'enabled' => $enabled
            ]);
            
            return $response !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get total users count
     */
    private function getTotalUsersCount(): int
    {
        return count($this->getAllUsers());
    }

    /**
     * Get active users count
     */
    private function getActiveUsersCount(): int
    {
        $users = $this->getAllUsers();
        return count(array_filter($users, function($user) {
            return $user['enabled'] ?? false;
        }));
    }

    /**
     * Make HTTP request to S-UI API
     */
    private function makeRequest(string $method, string $endpoint, ?array $data = null, bool $useAuth = true): mixed
    {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        
        $headers = $useAuth ? $this->headers : ['Content-Type: application/json'];
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new \Exception("cURL error: {$error}");
        }
        
        if ($httpCode >= 400) {
            throw new \Exception("HTTP error {$httpCode}: {$response}");
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response: ' . json_last_error_msg());
        }
        
        return $decoded;
    }
}