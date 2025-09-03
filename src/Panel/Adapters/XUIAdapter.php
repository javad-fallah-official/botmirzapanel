<?php

namespace BotMirzaPanel\Panel\Adapters;

use BotMirzaPanel\Infrastructure\External\Panel\PanelAdapterInterface;

/**
 * X-UI panel adapter
 * Handles integration with X-UI panel
 */
class XUIAdapter implements PanelAdapterInterface
{
    private array $config = [];
    private string $baseUrl;
    private ?string $sessionId = null;
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
            $response = $this->makeRequest('POST', '/login', [
                'username' => $this->config['username'],
                'password' => $this->config['password']
            ]);
            return $response !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getPanelInfo(): array
    {
        try {
            $status = $this->makeRequest('POST', '/server/status');
            $settings = $this->makeRequest('POST', '/server/getConfigJson');
            
            return [
                'version' => $status['version'] ?? 'unknown',
                'uptime' => $status['uptime'] ?? 0,
                'cpu_usage' => $status['cpu'] ?? 0,
                'memory_usage' => $status['mem']['current'] ?? 0,
                'total_memory' => $status['mem']['total'] ?? 0,
                'network_up' => $status['netIO']['up'] ?? 0,
                'network_down' => $status['netIO']['down'] ?? 0,
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
            'protocols' => ['vmess', 'vless', 'trojan', 'shadowsocks', 'dokodemo-door']
        ];
    }

    public function createUser(array $userData): array
    {
        try {
            $inboundId = $userData['inbound_id'] ?? $this->getDefaultInboundId();
            
            $clientData = $this->prepareClientData($userData);
            
            $response = $this->makeRequest('POST', "/xui/inbound/addClient", [
                'id' => $inboundId,
                'settings' => json_encode([
                    'clients' => [$clientData]
                ])
            ]);
            
            if ($response && $response['success']) {
                return [
                    'success' => true,
                    'username' => $userData['username'],
                    'client_id' => $clientData['id'],
                    'inbound_id' => $inboundId,
                    'data' => $clientData
                ];
            }
            
            return ['success' => false, 'error' => $response['msg'] ?? 'Failed to create user'];
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
            
            $clientData = $this->prepareClientData($userData, $user['client']);
            
            if (!isset($user['client']['id'])) {
                return false;
            }
            
            $response = $this->makeRequest('POST', "/xui/inbound/updateClient/{$user['client']['id']}", [
                'id' => $user['inbound_id'],
                'settings' => json_encode([
                    'clients' => [$clientData]
                ])
            ]);
            
            if ($response && $response['success']) {
                return [
                    'success' => true,
                    'username' => $username,
                    'data' => $clientData
                ];
            }
            
            return ['success' => false, 'error' => $response['msg'] ?? 'Failed to update user'];
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
            
            $response = $this->makeRequest('POST', "/xui/inbound/delClient/{$user['client']['id']}", [
                'id' => $user['inbound_id']
            ]);
            
            return $response && $response['success'];
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getUser(string $username): ?array
    {
        try {
            $user = $this->findUserByUsername($username);
            return $user ? [
                'username' => $user['client']['email'],
                'client_id' => $user['client']['id'],
                'inbound_id' => $user['inbound_id'],
                'total_gb' => $user['client']['totalGB'] ?? 0,
                'expiry_time' => $user['client']['expiryTime'] ?? 0,
                'enable' => $user['client']['enable'] ?? true,
                'up' => $user['client']['up'] ?? 0,
                'down' => $user['client']['down'] ?? 0
            ] : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getAllUsers(): array
    {
        try {
            $inbounds = $this->getInbounds();
            $users = [];
            
            foreach ($inbounds as $inbound) {
                if (isset($inbound['settings'])) {
                    $settings = json_decode($inbound['settings'], true);
                    if (isset($settings['clients'])) {
                        foreach ($settings['clients'] as $client) {
                            $users[] = [
                                'username' => $client['email'],
                                'client_id' => $client['id'],
                                'inbound_id' => $inbound['id'],
                                'enable' => $client['enable'] ?? true,
                                'total_gb' => $client['totalGB'] ?? 0,
                                'up' => $client['up'] ?? 0,
                                'down' => $client['down'] ?? 0
                            ];
                        }
                    }
                }
            }
            
            return $users;
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
            
            $inbound = $this->getInbound($user['inbound_id']);
            if (!$inbound) {
                return null;
            }
            
            // Generate config based on protocol
            return $this->generateUserConfig($user['client'], $inbound);
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
                    'upload' => $user['up'],
                    'download' => $user['down'],
                    'total' => $user['up'] + $user['down'],
                    'limit' => $user['total_gb'],
                    'expiry' => $user['expiry_time'],
                    'enabled' => $user['enable'],
                    'remaining' => max(0, $user['total_gb'] - ($user['up'] + $user['down']))
                ];
            }
            return null;
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
            
            $response = $this->makeRequest('POST', "/xui/inbound/resetClientTraffic/{$user['client']['id']}", [
                'id' => $user['inbound_id']
            ]);
            
            return $response && $response['success'];
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getInbounds(): array
    {
        try {
            $response = $this->makeRequest('POST', '/xui/inbound/list');
            return $response['obj'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getSystemStats(): array
    {
        try {
            $status = $this->makeRequest('POST', '/server/status');
            return $status ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Authenticate with X-UI panel
     */
    private function authenticate(): void
    {
        try {
            $response = $this->makeRequest('POST', '/login', [
                'username' => $this->config['username'],
                'password' => $this->config['password']
            ], false);
            
            if ($response && $response['success']) {
                // X-UI uses session cookies, extract from response headers
                $this->sessionId = $this->extractSessionId($response);
                $this->headers = [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Cookie: session=' . $this->sessionId
                ];
            } else {
                throw new \Exception('Failed to authenticate with X-UI panel');
            }
        } catch (\Exception $e) {
            throw new \Exception('X-UI authentication failed: ' . $e->getMessage());
        }
    }

    /**
     * Find user by username across all inbounds
     */
    private function findUserByUsername(string $username): ?array
    {
        $inbounds = $this->getInbounds();
        
        foreach ($inbounds as $inbound) {
            if (isset($inbound['settings'])) {
                $settings = json_decode($inbound['settings'], true);
                if (isset($settings['clients'])) {
                    foreach ($settings['clients'] as $client) {
                        if ($client['email'] === $username) {
                            return [
                                'client' => $client,
                                'inbound_id' => $inbound['id'],
                                'inbound' => $inbound
                            ];
                        }
                    }
                }
            }
        }
        
        return null;
    }

    /**
     * Get specific inbound by ID
     */
    private function getInbound(int $inboundId): ?array
    {
        $inbounds = $this->getInbounds();
        
        foreach ($inbounds as $inbound) {
            if ($inbound['id'] == $inboundId) {
                return $inbound;
            }
        }
        
        return null;
    }

    /**
     * Get default inbound ID
     */
    private function getDefaultInboundId(): int
    {
        $inbounds = $this->getInbounds();
        return !empty($inbounds) ? $inbounds[0]['id'] : 1;
    }

    /**
     * Prepare client data for API requests
     */
    private function prepareClientData(array $userData, ?array $existingClient = null): array
    {
        $clientData = $existingClient ?? [];
        
        // Basic fields
        $clientData['email'] = $userData['username'];
        $clientData['id'] = $existingClient['id'] ?? $this->generateUUID();
        
        // Optional fields
        if (isset($userData['total_gb'])) {
            $clientData['totalGB'] = $userData['total_gb'];
        }
        
        if (isset($userData['expiry_time'])) {
            $clientData['expiryTime'] = $userData['expiry_time'];
        }
        
        if (isset($userData['enable'])) {
            $clientData['enable'] = $userData['enable'];
        }
        
        // Protocol specific settings
        if (isset($userData['flow'])) {
            $clientData['flow'] = $userData['flow'];
        }
        
        return $clientData;
    }

    /**
     * Set user enable/disable status
     */
    private function setUserStatus(string $username, bool $enable): bool
    {
        try {
            $user = $this->findUserByUsername($username);
            if (!$user || !isset($user['client'], $user['inbound_id'])) {
                return false;
            }
            
            $clientData = $user['client'];
            $clientData['enable'] = $enable;
            
            $response = $this->makeRequest('POST', "/xui/inbound/updateClient/{$user['client']['id']}", [
                'id' => $user['inbound_id'],
                'settings' => json_encode([
                    'clients' => [$clientData]
                ])
            ]);
            
            return $response && $response['success'];
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generate user configuration
     */
    private function generateUserConfig(array $client, array $inbound): string
    {
        if (!isset($inbound['protocol'], $inbound['port'])) {
            throw new \InvalidArgumentException('Invalid inbound configuration');
        }
        
        $protocol = $inbound['protocol'];
        $port = $inbound['port'];
        $host = parse_url($this->baseUrl, PHP_URL_HOST);
        
        switch ($protocol) {
            case 'vmess':
                return $this->generateVmessConfig($client, $inbound, $host, $port);
            case 'vless':
                return $this->generateVlessConfig($client, $inbound, $host, $port);
            case 'trojan':
                return $this->generateTrojanConfig($client, $inbound, $host, $port);
            default:
                return "# Configuration for {$protocol} not implemented";
        }
    }

    /**
     * Generate VMess configuration
     */
    private function generateVmessConfig(array $client, array $inbound, string $host, int $port): string
    {
        $config = [
            'v' => '2',
            'ps' => $client['email'],
            'add' => $host,
            'port' => $port,
            'id' => $client['id'],
            'aid' => '0',
            'net' => 'tcp',
            'type' => 'none',
            'host' => '',
            'path' => '',
            'tls' => ''
        ];
        
        return 'vmess://' . base64_encode(json_encode($config));
    }

    /**
     * Generate VLESS configuration
     */
    private function generateVlessConfig(array $client, array $inbound, string $host, int $port): string
    {
        $params = [
            'type' => 'tcp',
            'security' => 'none'
        ];
        
        $queryString = http_build_query($params);
        
        return "vless://{$client['id']}@{$host}:{$port}?{$queryString}#{$client['email']}";
    }

    /**
     * Generate Trojan configuration
     */
    private function generateTrojanConfig(array $client, array $inbound, string $host, int $port): string
    {
        return "trojan://{$client['password']}@{$host}:{$port}#{$client['email']}";
    }

    /**
     * Generate UUID
     */
    private function generateUUID(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
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
            return $user['enable'] ?? false;
        }));
    }

    /**
     * Extract session ID from response
     */
    private function extractSessionId(array $response): string
    {
        // This is a simplified implementation
        // In reality, you'd extract from Set-Cookie headers
        return 'session_' . time();
    }

    /**
     * Make HTTP request to X-UI API
     */
    private function makeRequest(string $method, string $endpoint, ?array $data = null, bool $useAuth = true): mixed
    {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        
        $headers = $useAuth ? $this->headers : ['Content-Type: application/x-www-form-urlencoded'];
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HEADER => true
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new \Exception("cURL error: {$error}");
        }
        
        if ($httpCode >= 400) {
            throw new \Exception("HTTP error {$httpCode}");
        }
        
        $body = substr($response, $headerSize);
        $decoded = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response: ' . json_last_error_msg());
        }
        
        return $decoded;
    }
}