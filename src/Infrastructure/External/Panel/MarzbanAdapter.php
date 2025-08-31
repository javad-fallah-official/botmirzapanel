<?php

declare(strict_types=1);

namespace BotMirzaPanel\Infrastructure\External\Panel;

use BotMirzaPanel\Domain\Entities\Panel\User as PanelUser;
use BotMirzaPanel\Domain\ValueObjects\Panel\PanelConfig;
use BotMirzaPanel\Domain\ValueObjects\Panel\TrafficUsage;
use BotMirzaPanel\Infrastructure\External\Panel\PanelAdapterInterface;

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

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->baseUrl = rtrim($config['url'] ?? '', '/');
        $this->authenticate();
    }

    public function getName(): string
    {
        return 'marzban';
    }

    public function getDisplayName(): string
    {
        return 'Marzban';
    }

    public function getDescription(): string
    {
        return 'Marzban VPN panel adapter for user and traffic management';
    }

    public function isConfigured(): bool
    {
        return !empty($this->config['url']) && 
               !empty($this->config['username']) && 
               !empty($this->config['password']);
    }

    public function testConnection(): array
    {
        try {
            $response = $this->makeRequest('GET', '/api/admin');
            return [
                'success' => $response !== false,
                'message' => $response !== false ? 'Connection successful' : 'Connection failed'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage()
            ];
        }
    }

    public function getConfigurationFields(): array
    {
        return [
            'url' => [
                'label' => 'Panel URL',
                'type' => 'url',
                'required' => true,
                'description' => 'Marzban panel URL (e.g., https://panel.example.com)'
            ],
            'username' => [
                'label' => 'Admin Username',
                'type' => 'text',
                'required' => true,
                'description' => 'Admin username for panel access'
            ],
            'password' => [
                'label' => 'Admin Password',
                'type' => 'password',
                'required' => true,
                'description' => 'Admin password for panel access'
            ]
        ];
    }

    public function validateConfiguration(array $config): bool
    {
        return !empty($config['url']) && 
               !empty($config['username']) && 
               !empty($config['password']);
    }

    public function createUser(PanelUser $user): array
    {
        $userData = [
            'username' => $user->getUsername(),
            'proxies' => $this->buildProxiesConfig($user),
            'data_limit' => $user->getDataLimit() ? $user->getDataLimit()->getBytes() : null,
            'expire' => $user->getExpiryDate() ? $user->getExpiryDate()->getTimestamp() : null,
            'status' => $user->isActive() ? 'active' : 'disabled'
        ];

        $response = $this->makeRequest('POST', '/api/user', $userData);
        
        if ($response === false) {
            throw new \Exception('Failed to create user on Marzban panel');
        }

        return [
            'success' => true,
            'user_id' => $response['username'] ?? $user->getUsername(),
            'subscription_url' => $response['subscription_url'] ?? null
        ];
    }

    public function updateUser(PanelUser $user): array
    {
        $userData = [
            'proxies' => $this->buildProxiesConfig($user),
            'data_limit' => $user->getDataLimit() ? $user->getDataLimit()->getBytes() : null,
            'expire' => $user->getExpiryDate() ? $user->getExpiryDate()->getTimestamp() : null,
            'status' => $user->isActive() ? 'active' : 'disabled'
        ];

        $response = $this->makeRequest('PUT', '/api/user/' . $user->getUsername(), $userData);
        
        if ($response === false) {
            throw new \Exception('Failed to update user on Marzban panel');
        }

        return [
            'success' => true,
            'user_id' => $user->getUsername()
        ];
    }

    public function deleteUser(string $username): array
    {
        $response = $this->makeRequest('DELETE', '/api/user/' . $username);
        
        return [
            'success' => $response !== false,
            'message' => $response !== false ? 'User deleted successfully' : 'Failed to delete user'
        ];
    }

    public function getUser(string $username): array
    {
        $response = $this->makeRequest('GET', '/api/user/' . $username);
        
        if ($response === false) {
            throw new \Exception('User not found');
        }

        return [
            'username' => $response['username'],
            'status' => $response['status'],
            'data_limit' => $response['data_limit'],
            'used_traffic' => $response['used_traffic'] ?? 0,
            'expire' => $response['expire'],
            'created_at' => $response['created_at'] ?? null,
            'subscription_url' => $response['subscription_url'] ?? null
        ];
    }

    public function getAllUsers(): array
    {
        $response = $this->makeRequest('GET', '/api/users');
        
        if ($response === false) {
            return [];
        }

        return $response['users'] ?? [];
    }

    public function enableUser(string $username): array
    {
        return $this->updateUserStatus($username, 'active');
    }

    public function disableUser(string $username): array
    {
        return $this->updateUserStatus($username, 'disabled');
    }

    public function resetUserTraffic(string $username): array
    {
        $response = $this->makeRequest('POST', '/api/user/' . $username . '/reset');
        
        return [
            'success' => $response !== false,
            'message' => $response !== false ? 'Traffic reset successfully' : 'Failed to reset traffic'
        ];
    }

    public function getUserTraffic(string $username): TrafficUsage
    {
        $user = $this->getUser($username);
        
        return new TrafficUsage(
            $user['used_traffic'] ?? 0,
            $user['data_limit'] ?? 0,
            new \DateTime()
        );
    }

    public function getSystemInfo(): array
    {
        $response = $this->makeRequest('GET', '/api/system');
        
        return $response ?: [];
    }

    public function getStatistics(): array
    {
        $response = $this->makeRequest('GET', '/api/system/stats');
        
        return $response ?: [];
    }

    public function getInbounds(): array
    {
        $response = $this->makeRequest('GET', '/api/inbounds');
        
        return $response ?: [];
    }

    public function createInbound(array $inboundData): array
    {
        $response = $this->makeRequest('POST', '/api/inbound', $inboundData);
        
        return [
            'success' => $response !== false,
            'inbound_id' => $response['id'] ?? null
        ];
    }

    public function updateInbound(int $inboundId, array $inboundData): array
    {
        $response = $this->makeRequest('PUT', '/api/inbound/' . $inboundId, $inboundData);
        
        return [
            'success' => $response !== false
        ];
    }

    public function deleteInbound(int $inboundId): array
    {
        $response = $this->makeRequest('DELETE', '/api/inbound/' . $inboundId);
        
        return [
            'success' => $response !== false
        ];
    }

    public function getPanelConfig(): PanelConfig
    {
        $response = $this->makeRequest('GET', '/api/core/config');
        
        return new PanelConfig($response ?: []);
    }

    public function updatePanelConfig(PanelConfig $config): array
    {
        $response = $this->makeRequest('PUT', '/api/core/config', $config->toArray());
        
        return [
            'success' => $response !== false
        ];
    }

    public function generateUserConfig(string $username, array $options = []): array
    {
        $user = $this->getUser($username);
        
        return [
            'subscription_url' => $user['subscription_url'] ?? null,
            'qr_code' => $this->generateQrCode($user['subscription_url'] ?? ''),
            'configs' => $this->getUserConfigs($username)
        ];
    }

    public function getUserSubscriptionLink(string $username): string
    {
        $user = $this->getUser($username);
        return $user['subscription_url'] ?? '';
    }

    public function backup(): array
    {
        $response = $this->makeRequest('GET', '/api/system/backup');
        
        return [
            'success' => $response !== false,
            'backup_data' => $response
        ];
    }

    public function restore(string $backupData): array
    {
        $response = $this->makeRequest('POST', '/api/system/restore', ['data' => $backupData]);
        
        return [
            'success' => $response !== false
        ];
    }

    public function getLogs(int $limit = 100): array
    {
        $response = $this->makeRequest('GET', '/api/system/logs?limit=' . $limit);
        
        return $response ?: [];
    }

    public function clearLogs(): array
    {
        $response = $this->makeRequest('DELETE', '/api/system/logs');
        
        return [
            'success' => $response !== false
        ];
    }

    public function restartPanel(): array
    {
        $response = $this->makeRequest('POST', '/api/system/restart');
        
        return [
            'success' => $response !== false
        ];
    }

    public function updatePanel(): array
    {
        $response = $this->makeRequest('POST', '/api/system/update');
        
        return [
            'success' => $response !== false
        ];
    }

    public function getSupportedFeatures(): array
    {
        return [
            'user_management',
            'traffic_monitoring',
            'inbound_management',
            'system_stats',
            'backup_restore',
            'log_management',
            'subscription_links'
        ];
    }

    public function supportsFeature(string $feature): bool
    {
        return in_array($feature, $this->getSupportedFeatures());
    }

    /**
     * Authenticate with the panel
     */
    private function authenticate(): void
    {
        if (!$this->isConfigured()) {
            return;
        }

        $loginData = [
            'username' => $this->config['username'],
            'password' => $this->config['password']
        ];

        $response = $this->makeRequest('POST', '/api/admin/token', $loginData, false);
        
        if ($response && isset($response['access_token'])) {
            $this->token = $response['access_token'];
            $this->headers = [
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json'
            ];
        }
    }

    /**
     * Update user status
     */
    private function updateUserStatus(string $username, string $status): array
    {
        $response = $this->makeRequest('PUT', '/api/user/' . $username, ['status' => $status]);
        
        return [
            'success' => $response !== false,
            'message' => $response !== false ? 'User status updated' : 'Failed to update user status'
        ];
    }

    /**
     * Build proxies configuration for user
     */
    private function buildProxiesConfig(PanelUser $user): array
    {
        // Default proxy configuration for Marzban
        return [
            'vmess' => [],
            'vless' => [],
            'trojan' => [],
            'shadowsocks' => []
        ];
    }

    /**
     * Generate QR code for subscription URL
     */
    private function generateQrCode(string $url): string
    {
        // Simple QR code generation - in production, use a proper QR library
        return 'data:image/png;base64,' . base64_encode('QR_CODE_PLACEHOLDER');
    }

    /**
     * Get user configuration files
     */
    private function getUserConfigs(string $username): array
    {
        $response = $this->makeRequest('GET', '/api/user/' . $username . '/config');
        return $response ?: [];
    }

    /**
     * Make HTTP request to Marzban API
     */
    private function makeRequest(string $method, string $endpoint, array $data = [], bool $useAuth = true): array|false
    {
        $url = $this->baseUrl . $endpoint;
        
        $headers = $useAuth ? $this->headers : ['Content-Type: application/json'];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        if (in_array($method, ['POST', 'PUT']) && !empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception('cURL error: ' . $error);
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true) ?: [];
        }
        
        return false;
    }
}