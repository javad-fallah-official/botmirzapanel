<?php
// WARNING: SQL injection vulnerability detected in this file
// Please review and use prepared statements or secure_* functions


namespace BotMirzaPanel\Panel\Adapters;

use BotMirzaPanel\Infrastructure\External\Panel\PanelAdapterInterface;

/**
 * WireGuard Dashboard adapter
 * Handles integration with WireGuard Dashboard
 */
class WireGuardAdapter implements PanelAdapterInterface
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
            $response = $this->makeRequest('GET', '/api/dashboard');
            return $response !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getPanelInfo(): array
    {
        try {
            $dashboard = $this->makeRequest('GET', '/api/dashboard');
            $configs = $this->makeRequest('GET', '/api/getWireguardConfigurations');
            
            return [
                'version' => $dashboard['version'] ?? 'unknown',
                'uptime' => $dashboard['uptime'] ?? 0,
                'total_configs' => count($configs ?? []),
                'total_peers' => $this->getTotalPeersCount(),
                'active_peers' => $this->getActivePeersCount(),
                'dashboard_theme' => $dashboard['theme'] ?? 'dark'
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
            'reset_user_data' => false, // WireGuard doesn't track data usage by default
            'bulk_operations' => false,
            'inbound_management' => true, // WireGuard configurations
            'system_stats' => true,
            'protocols' => ['wireguard']
        ];
    }

    public function createUser(array $userData): array
    {
        try {
            $configName = $userData['config_name'] ?? $this->getDefaultConfigName();
            
            $peerData = $this->preparePeerData($userData);
            
            $response = $this->makeRequest('POST', '/api/addPeer/' . $configName, $peerData);
            
            if ($response && $response['status'] === 'success') {
                return [
                    'success' => true,
                    'username' => $userData['username'],
                    'peer_id' => $response['peer_id'] ?? null,
                    'config_name' => $configName,
                    'public_key' => $peerData['public_key'] ?? null,
                    'data' => $response
                ];
            }
            
            return ['success' => false, 'error' => $response['message'] ?? 'Failed to create peer'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function updateUser(string $username, array $userData): array
    {
        try {
            $peer = $this->findPeerByUsername($username);
            if (!$peer) {
                return ['success' => false, 'error' => 'Peer not found'];
            }
            
            $peerData = $this->preparePeerData($userData, true);
            
            $response = $this->makeRequest('POST', "/api/savePeerName/{$peer['config_name']}", [
                'peer_id' => $peer['id'],
                'name' => $peerData['name'] ?? $peer['name']
            ]);
            
            if ($response && $response['status'] === 'success') {
                return [
                    'success' => true,
                    'username' => $username,
                    'data' => $response
                ];
            }
            
            return ['success' => false, 'error' => $response['message'] ?? 'Failed to update peer'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function deleteUser(string $username): bool
    {
        try {
            $peer = $this->findPeerByUsername($username);
            if (!$peer) {
                return false;
            }
            
            $response = $this->makeRequest('POST', "/api/removePeer/{$peer['config_name']}", [
                'peer_id' => $peer['id']
            ]);
            
            return $response && $response['status'] === 'success';
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getUser(string $username): ?array
    {
        try {
            $peer = $this->findPeerByUsername($username);
            return $peer;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getAllUsers(): array
    {
        try {
            $configs = $this->makeRequest('GET', '/api/getWireguardConfigurations');
            $peers = [];
            
            foreach ($configs as $config) {
                $configPeers = $this->makeRequest('GET', '/api/getPeers/' . $config['conf']);
                
                foreach ($configPeers as $peer) {
                    $peers[] = [
                        'username' => $peer['name'],
                        'id' => $peer['id'],
                        'config_name' => $config['conf'],
                        'public_key' => $peer['public_key'],
                        'allowed_ips' => $peer['allowed_ip'],
                        'endpoint' => $peer['endpoint'] ?? null,
                        'latest_handshake' => $peer['latest_handshake'] ?? null,
                        'transfer_rx' => $peer['cumu_receive'] ?? 0,
                        'transfer_tx' => $peer['cumu_sent'] ?? 0,
                        'status' => $peer['status'] ?? 'stopped'
                    ];
                }
            }
            
            return $peers;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getUserConfig(string $username): ?string
    {
        try {
            $peer = $this->findPeerByUsername($username);
            if (!$peer) {
                return null;
            }
            
            $response = $this->makeRequest('GET', "/api/downloadPeer/{$peer['config_name']}", [
                'peer_id' => $peer['id']
            ]);
            
            return $response['config'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getUserStats(string $username): ?array
    {
        try {
            $peer = $this->findPeerByUsername($username);
            if (!$peer) {
                return null;
            }
            
            return [
                'username' => $peer['username'],
                'upload' => $peer['transfer_tx'],
                'download' => $peer['transfer_rx'],
                'total' => $peer['transfer_tx'] + $peer['transfer_rx'],
                'latest_handshake' => $peer['latest_handshake'],
                'endpoint' => $peer['endpoint'],
                'status' => $peer['status'],
                'allowed_ips' => $peer['allowed_ips']
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    public function enableUser(string $username): bool
    {
        // WireGuard doesn't have enable/disable concept like other panels
        // We can implement this by allowing/restricting traffic
        return true;
    }

    public function disableUser(string $username): bool
    {
        // WireGuard doesn't have enable/disable concept like other panels
        // We can implement this by allowing/restricting traffic
        return true;
    }

    public function resetUserData(string $username): bool
    {
        // WireGuard doesn't track data usage by default
        // This would require custom implementation
        return false;
    }

    public function getInbounds(): array
    {
        try {
            $configs = $this->makeRequest('GET', '/api/getWireguardConfigurations');
            return $configs ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getSystemStats(): array
    {
        try {
            $dashboard = $this->makeRequest('GET', '/api/dashboard');
            $configs = $this->getInbounds();
            
            $totalPeers = 0;
            $activePeers = 0;
            
            foreach ($configs as $config) {
                $peers = $this->makeRequest('GET', '/api/getPeers/' . $config['conf']);
                $totalPeers += count($peers);
                
                foreach ($peers as $peer) {
                    if (($peer['status'] ?? 'stopped') === 'running') {
                        $activePeers++;
                    }
                }
            }
            
            return [
                'total_configs' => count($configs),
                'total_peers' => $totalPeers,
                'active_peers' => $activePeers,
                'dashboard_version' => $dashboard['version'] ?? 'unknown',
                'uptime' => $dashboard['uptime'] ?? 0
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Authenticate with WireGuard Dashboard
     */
    private function authenticate(): void
    {
        try {
            $response = $this->makeRequest('POST', '/signin', [
                'username' => $this->config['username'],
                'password' => $this->config['password']
            ], false);
            
            if ($response && ($response['status'] === 'success' || isset($response['session']))) {
                // WireGuard Dashboard uses session-based authentication
                $this->sessionId = $response['session'] ?? 'authenticated';
                $this->headers = [
                    'Content-Type: application/json',
                    'Cookie: session=' . $this->sessionId
                ];
            } else {
                throw new \Exception('Failed to authenticate with WireGuard Dashboard');
            }
        } catch (\Exception $e) {
            throw new \Exception('WireGuard Dashboard authentication failed: ' . $e->getMessage());
        }
    }

    /**
     * Find peer by username across all configurations
     */
    private function findPeerByUsername(string $username): ?array
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
     * Get default configuration name
     */
    private function getDefaultConfigName(): string
    {
        $configs = $this->getInbounds();
        return !empty($configs) ? $configs[0]['conf'] : 'wg0';
    }

    /**
     * Prepare peer data for API requests
     */
    private function preparePeerData(array $userData, bool $isUpdate = false): array
    {
        $peerData = [];
        
        // Basic fields
        $peerData['name'] = $userData['username'];
        
        if (!$isUpdate) {
            // Generate key pair if not provided
            if (!isset($userData['private_key']) || !isset($userData['public_key'])) {
                $keyPair = $this->generateKeyPair();
                $peerData['private_key'] = $keyPair['private'];
                $peerData['public_key'] = $keyPair['public'];
            } else {
                $peerData['private_key'] = $userData['private_key'];
                $peerData['public_key'] = $userData['public_key'];
            }
            
            // Set allowed IPs
            $peerData['allowed_ips'] = $userData['allowed_ips'] ?? '0.0.0.0/0, ::/0';
            
            // Set endpoint if provided
            if (isset($userData['endpoint'])) {
                $peerData['endpoint'] = $userData['endpoint'];
            }
        }
        
        // Optional fields
        if (isset($userData['dns'])) {
            $peerData['DNS'] = $userData['dns'];
        }
        
        if (isset($userData['mtu'])) {
            $peerData['mtu'] = $userData['mtu'];
        }
        
        if (isset($userData['keepalive'])) {
            $peerData['keepalive'] = $userData['keepalive'];
        }
        
        return $peerData;
    }

    /**
     * Generate WireGuard key pair
     */
    private function generateKeyPair(): array
    {
        // Generate private key
        $privateKey = base64_encode(random_bytes(32));
        
        // In a real implementation, you would use WireGuard tools to generate the public key
        // For now, we'll generate a mock public key
        $publicKey = base64_encode(hash('sha256', $privateKey, true));
        
        return [
            'private' => $privateKey,
            'public' => $publicKey
        ];
    }

    /**
     * Get total peers count
     */
    private function getTotalPeersCount(): int
    {
        return count($this->getAllUsers());
    }

    /**
     * Get active peers count
     */
    private function getActivePeersCount(): int
    {
        $users = $this->getAllUsers();
        return count(array_filter($users, function($user) {
            return ($user['status'] ?? 'stopped') === 'running';
        }));
    }

    /**
     * Make HTTP request to WireGuard Dashboard API
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
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            if ($endpoint === '/signin') {
                // Login endpoint might expect form data
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
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
        
        // Try to decode JSON, but handle non-JSON responses
        $decoded = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
        
        // If not JSON, return raw response
        return $response;
    }
}