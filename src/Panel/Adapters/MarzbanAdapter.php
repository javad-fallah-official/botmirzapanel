<?php

namespace BotMirzaPanel\Panel\Adapters;

/**
 * Marzban panel adapter
 * Handles integration with Marzban VPN panel
 */
class MarzbanAdapter implements PanelAdapterInterface
{
    private array $config = [];
    private ?string $token = null;
    private string $baseUrl;
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
            $response = $this->makeRequest('GET', '/api/admin');
            return $response !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getPanelInfo(): array
    {
        try {
            $systemInfo = $this->makeRequest('GET', '/api/system');
            $adminInfo = $this->makeRequest('GET', '/api/admin');
            
            return [
                'version' => $systemInfo['version'] ?? 'unknown',
                'uptime' => $systemInfo['uptime'] ?? 0,
                'memory_usage' => $systemInfo['memory_usage'] ?? 0,
                'cpu_usage' => $systemInfo['cpu_usage'] ?? 0,
                'admin_username' => $adminInfo['username'] ?? 'unknown',
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
        $userPayload = $this->prepareUserPayload($userData);
        
        try {
            $response = $this->makeRequest('POST', '/api/user', $userPayload);
            
            if ($response) {
                return [
                    'success' => true,
                    'username' => $response['username'],
                    'subscription_url' => $response['subscription_url'] ?? null,
                    'data' => $response
                ];
            }
            
            return ['success' => false, 'error' => 'Failed to create user'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function updateUser(string $username, array $userData): array
    {
        $userPayload = $this->prepareUserPayload($userData, true);
        
        try {
            $response = $this->makeRequest('PUT', "/api/user/{$username}", $userPayload);
            
            if ($response) {
                return [
                    'success' => true,
                    'username' => $response['username'],
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
            $response = $this->makeRequest('DELETE', "/api/user/{$username}");
            return $response !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getUser(string $username): ?array
    {
        try {
            $response = $this->makeRequest('GET', "/api/user/{$username}");
            return $response ?: null;
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
            $user = $this->getUser($username);
            if ($user && isset($user['subscription_url'])) {
                // Get subscription content
                $configResponse = $this->makeRequest('GET', $user['subscription_url'], null, false);
                return $configResponse ?: null;
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getUserStats(string $username): ?array
    {
        try {
            $user = $this->getUser($username);
            if ($user) {
                return [
                    'username' => $user['username'],
                    'status' => $user['status'],
                    'used_traffic' => $user['used_traffic'] ?? 0,
                    'data_limit' => $user['data_limit'] ?? 0,
                    'expire' => $user['expire'] ?? null,
                    'online' => $user['online'] ?? false,
                    'created_at' => $user['created_at'] ?? null,
                    'links' => $user['links'] ?? []
                ];
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function enableUser(string $username): bool
    {
        return $this->setUserStatus($username, 'active');
    }

    public function disableUser(string $username): bool
    {
        return $this->setUserStatus($username, 'disabled');
    }

    public function resetUserData(string $username): bool
    {
        try {
            $response = $this->makeRequest('POST', "/api/user/{$username}/reset");
            return $response !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getInbounds(): array
    {
        try {
            $response = $this->makeRequest('GET', '/api/inbounds');
            return $response ?: [];
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getSystemStats(): array
    {
        try {
            $response = $this->makeRequest('GET', '/api/system');
            return $response ?: [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Authenticate with Marzban panel
     */
    private function authenticate(): void
    {
        try {
            $loginData = [
                'username' => $this->config['username'],
                'password' => $this->config['password']
            ];
            
            $response = $this->makeRequest('POST', '/api/admin/token', $loginData, true, false);
            
            if ($response && isset($response['access_token'])) {
                $this->token = $response['access_token'];
                $this->headers = [
                    'Authorization: Bearer ' . $this->token,
                    'Content-Type: application/json'
                ];
            } else {
                throw new \Exception('Failed to authenticate with Marzban panel');
            }
        } catch (\Exception $e) {
            throw new \Exception('Marzban authentication failed: ' . $e->getMessage());
        }
    }

    /**
     * Set user status
     */
    private function setUserStatus(string $username, string $status): bool
    {
        try {
            $response = $this->makeRequest('PUT', "/api/user/{$username}", ['status' => $status]);
            return $response !== false;
        } catch (\Exception $e) {
            return false;
        }
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
            'expire' => 'expire',
            'status' => 'status',
            'note' => 'note'
        ];
        
        foreach ($fieldMapping as $key => $apiKey) {
            if (isset($userData[$key])) {
                $payload[$apiKey] = $userData[$key];
            }
        }
        
        // Handle inbounds
        if (isset($userData['inbounds'])) {
            $payload['inbounds'] = $userData['inbounds'];
        } else {
            // Use default inbounds from config
            $payload['inbounds'] = $this->config['default_inbounds'] ?? [];
        }
        
        // Handle proxies (protocols)
        if (isset($userData['proxies'])) {
            $payload['proxies'] = $userData['proxies'];
        }
        
        return $payload;
    }

    /**
     * Get total users count
     */
    private function getTotalUsersCount(): int
    {
        try {
            $users = $this->getAllUsers();
            return count($users);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get active users count
     */
    private function getActiveUsersCount(): int
    {
        try {
            $users = $this->getAllUsers();
            return count(array_filter($users, function($user) {
                return ($user['status'] ?? '') === 'active';
            }));
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Make HTTP request to Marzban API
     */
    private function makeRequest(string $method, string $endpoint, ?array $data = null, bool $jsonDecode = true, bool $useAuth = true): mixed
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
        
        if ($jsonDecode) {
            $decoded = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response: ' . json_last_error_msg());
            }
            return $decoded;
        }
        
        return $response;
    }
}