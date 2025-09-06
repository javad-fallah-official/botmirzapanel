<?php
// WARNING: SQL injection vulnerability detected in this file
// Please review and use prepared statements or secure_* functions


declare(strict_types=1);

namespace BotMirzaPanel\Panel;

use BotMirzaPanel\Config\ConfigManager;
use BotMirzaPanel\Database\DatabaseManager;
use BotMirzaPanel\Infrastructure\External\Panel\PanelAdapterInterface;
use BotMirzaPanel\Panel\Adapters\MarzbanAdapter;
use BotMirzaPanel\Panel\Adapters\MikroTikAdapter;
use BotMirzaPanel\Panel\Adapters\XUIAdapter;
use BotMirzaPanel\Panel\Adapters\SUIAdapter;
use BotMirzaPanel\Panel\Adapters\WireGuardAdapter;

/**
 * Panel service that manages different panel integrations
 * Uses factory pattern to create appropriate panel adapters
 */
class PanelService
{
    private ConfigManager $config;
    private DatabaseManager $db;
    private array $adapters = [];
    private array $panelConfigs = [];

    public function __construct()
    {
        $this->config = $config;
        $this->db = $db;
        $this->loadPanelConfigs();
        $this->initializeAdapters();
    }

    /**
     * Get available panel types
     */
    public function getAvailablePanelTypes(): array
    {
        return [
            'marzban' => 'Marzban Panel',
            'mikrotik' => 'MikroTik RouterOS',
            'xui' => 'X-UI Panel',
            'sui' => 'S-UI Panel',
            'wireguard' => 'WireGuard Dashboard'
        ];
    }

    /**
     * Get configured panels
     */
    public function getConfiguredPanels(): array
    {
        $panels = [];
        
        foreach ($this->panelConfigs as $panelId => $config) {
            if ($config['enabled']) {
                $adapter = $this->getAdapter($config['type']);
                $panels[$panelId] = [
                    'id' => $panelId,
                    'type' => $config['type'],
                    'name' => $config['name'],
                    'url' => $config['url'],
                    'status' => $this->getPanelStatus($panelId),
                    'capabilities' => $adapter ? $adapter->getCapabilities() : []
                ];
            }
        }
        
        return $panels;
    }

    /**
     * Create user on panel
     */
    public function createUser(string $panelId, array $userData): array
    {
        $panelConfig = $this->getPanelConfig($panelId);
        $adapter = $this->getAdapter($panelConfig['type']);
        
        if (!$adapter) {
            throw new \Exception("Panel adapter not found for type: {$panelConfig['type']}");
        }
        
        // Configure adapter with panel settings
        $adapter->configure($panelConfig);
        
        // Create user on panel
        $result = $adapter->createUser($userData);
        
        // Log the operation
        $this->logPanelOperation($panelId, 'create_user', $userData, $result);
        
        return $result;
    }

    /**
     * Update user on panel
     */
    public function updateUser(string $panelId, string $username, array $userData): array
    {
        $panelConfig = $this->getPanelConfig($panelId);
        $adapter = $this->getAdapter($panelConfig['type']);
        
        if (!$adapter) {
            throw new \Exception("Panel adapter not found for type: {$panelConfig['type']}");
        }
        
        $adapter->configure($panelConfig);
        $result = $adapter->updateUser($username, $userData);
        
        $this->logPanelOperation($panelId, 'update_user', ['username' => $username] + $userData, $result);
        
        return $result;
    }

    /**
     * Delete user from panel
     */
    public function deleteUser(string $panelId, string $username): bool
    {
        $panelConfig = $this->getPanelConfig($panelId);
        $adapter = $this->getAdapter($panelConfig['type']);
        
        if (!$adapter) {
            throw new \Exception("Panel adapter not found for type: {$panelConfig['type']}");
        }
        
        $adapter->configure($panelConfig);
        $result = $adapter->deleteUser($username);
        
        $this->logPanelOperation($panelId, 'delete_user', ['username' => $username], ['success' => $result]);
        
        return $result;
    }

    /**
     * Get user from panel
     */
    public function getUser(string $panelId, string $username): ?array
    {
        $panelConfig = $this->getPanelConfig($panelId);
        $adapter = $this->getAdapter($panelConfig['type']);
        
        if (!$adapter) {
            return null;
        }
        
        $adapter->configure($panelConfig);
        return $adapter->getUser($username);
    }

    /**
     * Get user configuration/config file
     */
    public function getUserConfig(string $panelId, string $username): ?string
    {
        $panelConfig = $this->getPanelConfig($panelId);
        $adapter = $this->getAdapter($panelConfig['type']);
        
        if (!$adapter) {
            return null;
        }
        
        $adapter->configure($panelConfig);
        return $adapter->getUserConfig($username);
    }

    /**
     * Get user statistics
     */
    public function getUserStats(string $panelId, string $username): ?array
    {
        $panelConfig = $this->getPanelConfig($panelId);
        $adapter = $this->getAdapter($panelConfig['type']);
        
        if (!$adapter) {
            return null;
        }
        
        $adapter->configure($panelConfig);
        return $adapter->getUserStats($username);
    }

    /**
     * Enable user on panel
     */
    public function enableUser(string $panelId, string $username): bool
    {
        $panelConfig = $this->getPanelConfig($panelId);
        $adapter = $this->getAdapter($panelConfig['type']);
        
        if (!$adapter) {
            return false;
        }
        
        $adapter->configure($panelConfig);
        $result = $adapter->enableUser($username);
        
        $this->logPanelOperation($panelId, 'enable_user', ['username' => $username], ['success' => $result]);
        
        return $result;
    }

    /**
     * Disable user on panel
     */
    public function disableUser(string $panelId, string $username): bool
    {
        $panelConfig = $this->getPanelConfig($panelId);
        $adapter = $this->getAdapter($panelConfig['type']);
        
        if (!$adapter) {
            return false;
        }
        
        $adapter->configure($panelConfig);
        $result = $adapter->disableUser($username);
        
        $this->logPanelOperation($panelId, 'disable_user', ['username' => $username], ['success' => $result]);
        
        return $result;
    }

    /**
     * Test panel connection
     */
    public function testPanelConnection(string $panelId): bool
    {
        $panelConfig = $this->getPanelConfig($panelId);
        $adapter = $this->getAdapter($panelConfig['type']);
        
        if (!$adapter) {
            return false;
        }
        
        $adapter->configure($panelConfig);
        return $adapter->testConnection();
    }

    /**
     * Get panel status
     */
    public function getPanelStatus(string $panelId): array
    {
        $panelConfig = $this->getPanelConfig($panelId);
        $adapter = $this->getAdapter($panelConfig['type']);
        
        if (!$adapter) {
            return [
                'status' => 'error',
                'message' => 'Adapter not found'
            ];
        }
        
        $adapter->configure($panelConfig);
        
        try {
            $connected = $adapter->testConnection();
            $info = $adapter->getPanelInfo();
            
            return [
                'status' => $connected ? 'online' : 'offline',
                'info' => $info,
                'last_check' => date('Y-m-d H:i:s')
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'last_check' => date('Y-m-d H:i:s')
            ];
        }
    }

    /**
     * Get all panel statuses
     */
    public function getAllPanelStatus(): array
    {
        $statuses = [];
        
        foreach ($this->panelConfigs as $panelId => $config) {
            if ($config['enabled']) {
                $statuses[$panelId] = $this->getPanelStatus($panelId);
            }
        }
        
        return $statuses;
    }

    /**
     * Sync users from panel
     */
    public function syncPanelUsers(string $panelId): array
    {
        $panelConfig = $this->getPanelConfig($panelId);
        $adapter = $this->getAdapter($panelConfig['type']);
        
        if (!$adapter) {
            throw new \Exception("Panel adapter not found for type: {$panelConfig['type']}");
        }
        
        $adapter->configure($panelConfig);
        $panelUsers = $adapter->getAllUsers();
        
        $syncResults = [
            'total' => count($panelUsers),
            'updated' => 0,
            'created' => 0,
            'errors' => []
        ];
        
        foreach ($panelUsers as $panelUser) {
            try {
                $existingUser = $this->db->findOne('user_services', [
                    'panel_id' => $panelId,
                    'username' => $panelUser['username']
                ]);
                
                if ($existingUser) {
                    // Update existing user
                    $this->db->update('user_services', [
                        'status' => $panelUser['status'] ?? 'active',
                        'data_usage' => $panelUser['data_usage'] ?? 0,
                        'last_sync' => date('Y-m-d H:i:s')
                    ], ['id' => $existingUser['id']]);
                    
                    $syncResults['updated']++;
                } else {
                    // Create new user record
                    $this->db->insert('user_services', [
                        'panel_id' => $panelId,
                        'username' => $panelUser['username'],
                        'status' => $panelUser['status'] ?? 'active',
                        'data_usage' => $panelUser['data_usage'] ?? 0,
                        'created_at' => date('Y-m-d H:i:s'),
                        'last_sync' => date('Y-m-d H:i:s')
                    ]);
                    
                    $syncResults['created']++;
                }
            } catch (\Exception $e) {
                $syncResults['errors'][] = "Error syncing user {$panelUser['username']}: " . $e->getMessage();
            }
        }
        
        return $syncResults;
    }

    /**
     * Get panel configuration
     */
    private function getPanelConfig(string $panelId): array
    {
        if (!isset($this->panelConfigs[$panelId])) {
            throw new \Exception("Panel configuration not found: {$panelId}");
        }
        
        return $this->panelConfigs[$panelId];
    }

    /**
     * Get panel adapter
     */
    private function getAdapter(string $type): ?PanelAdapterInterface
    {
        return $this->adapters[$type] ?? null;
    }

    /**
     * Load panel configurations from database
     */
    private function loadPanelConfigs(): void
    {
        // Load from database or config files
        // For now, use config manager
        $this->panelConfigs = $this->config->get('panels', []);
        
        // If no config in ConfigManager, load from database
        if (empty($this->panelConfigs)) {
            $panels = $this->db->findAll('panel_configs', ['enabled' => 1]);
            
            foreach ($panels as $panel) {
                $this->panelConfigs[$panel['id']] = [
                    'type' => $panel['type'],
                    'name' => $panel['name'],
                    'url' => $panel['url'],
                    'username' => $panel['username'],
                    'password' => $panel['password'],
                    'api_key' => $panel['api_key'],
                    'enabled' => $panel['enabled'],
                    'settings' => json_decode($panel['settings'] ?? '{}', true)
                ];
            }
        }
    }

    /**
     * Initialize panel adapters
     */
    private function initializeAdapters(): void
    {
        $this->adapters = [
            'marzban' => new MarzbanAdapter(),
            'mikrotik' => new MikroTikAdapter(),
            'xui' => new XUIAdapter(),
            'sui' => new SUIAdapter(),
            'wireguard' => new WireGuardAdapter()
        ];
    }

    /**
     * Log panel operation
     */
    private function logPanelOperation(string $panelId, string $operation, array $input, array $result): void
    {
        $this->db->insert('panel_logs', [
            'panel_id' => $panelId,
            'operation' => $operation,
            'input_data' => json_encode($input),
            'result_data' => json_encode($result),
            'success' => $result['success'] ?? false,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}