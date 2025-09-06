<?php
// WARNING: SQL injection vulnerability detected in this file
// Please review and use prepared statements or secure_* functions


namespace BotMirzaPanel\Panel\Adapters;

use BotMirzaPanel\Infrastructure\External\Panel\PanelAdapterInterface;

/**
 * MikroTik RouterOS adapter
 * Handles integration with MikroTik RouterOS API
 */
class MikroTikAdapter implements PanelAdapterInterface
{
    private array $config = [];
    private mixed $socket = null;
    private bool $connected = false;
    private string $host;
    private int $port;
    private string $username;
    private string $password;

    public function configure(array $config): void
    {
        $this->config = $config;
        $this->host = $config['host'] ?? $config['url'];
        $this->port = $config['port'] ?? 8728;
        $this->username = $config['username'];
        $this->password = $config['password'];
        
        // Remove protocol from host if present
        $this->host = preg_replace('/^https?:\/\//', '', $this->host);
        $this->host = preg_replace('/:\d+$/', '', $this->host);
    }

    public function testConnection(): bool
    {
        try {
            $this->connect();
            $result = $this->sendCommand('/system/identity/print');
            $this->disconnect();
            return !empty($result);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getPanelInfo(): array
    {
        try {
            $this->connect();
            
            $identity = $this->sendCommand('/system/identity/print');
            $resource = $this->sendCommand('/system/resource/print');
            $version = $this->sendCommand('/system/package/print', ['?name=system']);
            
            $this->disconnect();
            
            return [
                'identity' => $identity[0]['name'] ?? 'Unknown',
                'version' => $version[0]['version'] ?? 'Unknown',
                'uptime' => $resource[0]['uptime'] ?? '0s',
                'cpu_load' => $resource[0]['cpu-load'] ?? 0,
                'free_memory' => $resource[0]['free-memory'] ?? 0,
                'total_memory' => $resource[0]['total-memory'] ?? 0,
                'architecture' => $resource[0]['architecture-name'] ?? 'Unknown',
                'board_name' => $resource[0]['board-name'] ?? 'Unknown'
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
            'get_user_config' => false, // MikroTik doesn't provide config files
            'get_user_stats' => true,
            'enable_disable_user' => true,
            'reset_user_data' => false,
            'bulk_operations' => true,
            'inbound_management' => false,
            'system_stats' => true,
            'protocols' => ['ppp', 'hotspot', 'vpn']
        ];
    }

    public function createUser(array $userData): array
    {
        try {
            $this->connect();
            
            $userType = $userData['type'] ?? 'ppp';
            
            switch ($userType) {
                case 'ppp':
                    $result = $this->createPPPUser($userData);
                    break;
                case 'hotspot':
                    $result = $this->createHotspotUser($userData);
                    break;
                default:
                    throw new \Exception("Unsupported user type: {$userType}");
            }
            
            $this->disconnect();
            
            return [
                'success' => true,
                'username' => $userData['username'],
                'type' => $userType,
                'data' => $result
            ];
        } catch (\Exception $e) {
            $this->disconnect();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function updateUser(string $username, array $userData): array
    {
        try {
            $this->connect();
            
            $userType = $userData['type'] ?? 'ppp';
            
            switch ($userType) {
                case 'ppp':
                    $result = $this->updatePPPUser($username, $userData);
                    break;
                case 'hotspot':
                    $result = $this->updateHotspotUser($username, $userData);
                    break;
                default:
                    throw new \Exception("Unsupported user type: {$userType}");
            }
            
            $this->disconnect();
            
            return [
                'success' => true,
                'username' => $username,
                'data' => $result
            ];
        } catch (\Exception $e) {
            $this->disconnect();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function deleteUser(string $username): bool
    {
        try {
            $this->connect();
            
            // Try to delete from PPP secrets
            $pppUsers = $this->sendCommand('/ppp/secret/print', ['?name=' . $username]);
            if (!empty($pppUsers)) {
                $this->sendCommand('/ppp/secret/remove', ['=.id=' . $pppUsers[0]['.id']]);
            }
            
            // Try to delete from Hotspot users
            $hotspotUsers = $this->sendCommand('/ip/hotspot/user/print', ['?name=' . $username]);
            if (!empty($hotspotUsers)) {
                $this->sendCommand('/ip/hotspot/user/remove', ['=.id=' . $hotspotUsers[0]['.id']]);
            }
            
            $this->disconnect();
            return true;
        } catch (\Exception $e) {
            $this->disconnect();
            return false;
        }
    }

    public function getUser(string $username): ?array
    {
        try {
            $this->connect();
            
            // Check PPP secrets first
            $pppUsers = $this->sendCommand('/ppp/secret/print', ['?name=' . $username]);
            if (!empty($pppUsers) && isset($pppUsers[0])) {
                $user = $pppUsers[0];
                $this->disconnect();
                return [
                    'username' => $user['name'],
                    'type' => 'ppp',
                    'profile' => $user['profile'] ?? null,
                    'local_address' => $user['local-address'] ?? null,
                    'remote_address' => $user['remote-address'] ?? null,
                    'disabled' => $user['disabled'] ?? false
                ];
            }
            
            // Check Hotspot users
            $hotspotUsers = $this->sendCommand('/ip/hotspot/user/print', ['?name=' . $username]);
            if (!empty($hotspotUsers) && isset($hotspotUsers[0])) {
                $user = $hotspotUsers[0];
                $this->disconnect();
                return [
                    'username' => $user['name'],
                    'type' => 'hotspot',
                    'profile' => $user['profile'] ?? null,
                    'limit_uptime' => $user['limit-uptime'] ?? null,
                    'limit_bytes_in' => $user['limit-bytes-in'] ?? null,
                    'limit_bytes_out' => $user['limit-bytes-out'] ?? null,
                    'disabled' => $user['disabled'] ?? false
                ];
            }
            
            $this->disconnect();
            return null;
        } catch (\Exception $e) {
            $this->disconnect();
            return null;
        }
    }

    public function getAllUsers(): array
    {
        try {
            $this->connect();
            
            $users = [];
            
            // Get PPP users
            $pppUsers = $this->sendCommand('/ppp/secret/print');
            foreach ($pppUsers as $user) {
                $users[] = [
                    'username' => $user['name'],
                    'type' => 'ppp',
                    'profile' => $user['profile'] ?? null,
                    'disabled' => $user['disabled'] ?? false
                ];
            }
            
            // Get Hotspot users
            $hotspotUsers = $this->sendCommand('/ip/hotspot/user/print');
            foreach ($hotspotUsers as $user) {
                $users[] = [
                    'username' => $user['name'],
                    'type' => 'hotspot',
                    'profile' => $user['profile'] ?? null,
                    'disabled' => $user['disabled'] ?? false
                ];
            }
            
            $this->disconnect();
            return $users;
        } catch (\Exception $e) {
            $this->disconnect();
            return [];
        }
    }

    public function getUserConfig(string $username): ?string
    {
        // MikroTik doesn't provide downloadable config files
        return null;
    }

    public function getUserStats(string $username): ?array
    {
        try {
            $this->connect();
            
            // Get active PPP sessions
            $activeSessions = $this->sendCommand('/ppp/active/print', ['?name=' . $username]);
            if (!empty($activeSessions) && isset($activeSessions[0])) {
                $session = $activeSessions[0];
                $this->disconnect();
                return [
                    'username' => $username,
                    'type' => 'ppp',
                    'active' => true,
                    'uptime' => $session['uptime'] ?? '0s',
                    'bytes_in' => $session['bytes-in'] ?? 0,
                    'bytes_out' => $session['bytes-out'] ?? 0,
                    'address' => $session['address'] ?? null,
                    'caller_id' => $session['caller-id'] ?? null
                ];
            }
            
            // Get Hotspot active sessions
            $hotspotActive = $this->sendCommand('/ip/hotspot/active/print', ['?user=' . $username]);
            if (!empty($hotspotActive) && isset($hotspotActive[0])) {
                $session = $hotspotActive[0];
                $this->disconnect();
                return [
                    'username' => $username,
                    'type' => 'hotspot',
                    'active' => true,
                    'uptime' => $session['uptime'] ?? '0s',
                    'bytes_in' => $session['bytes-in'] ?? 0,
                    'bytes_out' => $session['bytes-out'] ?? 0,
                    'address' => $session['address'] ?? null,
                    'mac_address' => $session['mac-address'] ?? null
                ];
            }
            
            $this->disconnect();
            return [
                'username' => $username,
                'active' => false,
                'uptime' => '0s',
                'bytes_in' => 0,
                'bytes_out' => 0
            ];
        } catch (\Exception $e) {
            $this->disconnect();
            return null;
        }
    }

    public function enableUser(string $username): bool
    {
        return $this->setUserStatus($username, false); // false = enabled
    }

    public function disableUser(string $username): bool
    {
        return $this->setUserStatus($username, true); // true = disabled
    }

    public function resetUserData(string $username): bool
    {
        // MikroTik doesn't have a direct reset data function
        return false;
    }

    public function getInbounds(): array
    {
        try {
            $this->connect();
            
            $interfaces = $this->sendCommand('/interface/print');
            $pppProfiles = $this->sendCommand('/ppp/profile/print');
            $hotspotProfiles = $this->sendCommand('/ip/hotspot/user/profile/print');
            
            $this->disconnect();
            
            return [
                'interfaces' => $interfaces,
                'ppp_profiles' => $pppProfiles,
                'hotspot_profiles' => $hotspotProfiles
            ];
        } catch (\Exception $e) {
            $this->disconnect();
            return [];
        }
    }

    public function getSystemStats(): array
    {
        try {
            $this->connect();
            
            $resource = $this->sendCommand('/system/resource/print');
            $health = $this->sendCommand('/system/health/print');
            $interfaces = $this->sendCommand('/interface/print');
            
            $this->disconnect();
            
            return [
                'resource' => $resource[0] ?? [],
                'health' => $health[0] ?? [],
                'interface_count' => count($interfaces)
            ];
        } catch (\Exception $e) {
            $this->disconnect();
            return [];
        }
    }

    /**
     * Connect to MikroTik API
     */
    private function connect(): void
    {
        if ($this->connected) {
            return;
        }
        
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$this->socket) {
            throw new \Exception('Failed to create socket');
        }
        
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 30, 'usec' => 0]);
        socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => 30, 'usec' => 0]);
        
        if (!socket_connect($this->socket, $this->host, $this->port)) {
            throw new \Exception('Failed to connect to MikroTik: ' . socket_strerror(socket_last_error()));
        }
        
        // Login
        $this->login();
        $this->connected = true;
    }

    /**
     * Disconnect from MikroTik API
     */
    private function disconnect(): void
    {
        if ($this->socket) {
            socket_close($this->socket);
            $this->socket = null;
        }
        $this->connected = false;
    }

    /**
     * Login to MikroTik API
     */
    private function login(): void
    {
        $this->write('/login');
        $response = $this->read();
        
        if (isset($response[0]['=ret'])) {
            // New login method (v6.43+)
            $challenge = $response[0]['=ret'];
            $hash = md5(chr(0) . $this->password . pack('H*', $challenge));
            
            $this->write('/login', ['=name=' . $this->username, '=response=00' . $hash]);
        } else {
            // Old login method
            $this->write('/login', ['=name=' . $this->username, '=password=' . $this->password]);
        }
        
        $response = $this->read();
        
        if (!isset($response[0]['!done'])) {
            throw new \Exception('Login failed');
        }
    }

    /**
     * Send command to MikroTik API
     */
    private function sendCommand(string $command, array $arguments = []): array
    {
        $this->write($command, $arguments);
        return $this->read();
    }

    /**
     * Write data to MikroTik API
     */
    private function write(string $command, array $arguments = []): void
    {
        $data = $this->encodeLength(strlen($command)) . $command;
        
        foreach ($arguments as $arg) {
            $data .= $this->encodeLength(strlen($arg)) . $arg;
        }
        
        $data .= $this->encodeLength(0);
        
        socket_write($this->socket, $data, strlen($data));
    }

    /**
     * Read data from MikroTik API
     */
    private function read(): array
    {
        $response = [];
        
        while (true) {
            $length = $this->decodeLength();
            
            if ($length === 0) {
                break;
            }
            
            $data = socket_read($this->socket, $length);
            
            if ($data === false) {
                throw new \Exception('Failed to read from socket');
            }
            
            $response[] = $data;
        }
        
        return $this->parseResponse($response);
    }

    /**
     * Encode length for MikroTik API
     */
    private function encodeLength(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        } elseif ($length < 0x4000) {
            return chr(($length >> 8) | 0x80) . chr($length & 0xFF);
        } elseif ($length < 0x200000) {
            return chr(($length >> 16) | 0xC0) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } elseif ($length < 0x10000000) {
            return chr(($length >> 24) | 0xE0) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } else {
            return chr(0xF0) . chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        }
    }

    /**
     * Decode length from MikroTik API
     */
    private function decodeLength(): int
    {
        $byte = socket_read($this->socket, 1);
        
        if ($byte === false) {
            throw new \Exception('Failed to read length byte');
        }
        
        $byte = ord($byte);
        
        if ($byte < 0x80) {
            return $byte;
        } elseif ($byte < 0xC0) {
            $byte2 = ord(socket_read($this->socket, 1));
            return (($byte & 0x7F) << 8) + $byte2;
        } elseif ($byte < 0xE0) {
            $byte2 = ord(socket_read($this->socket, 1));
            $byte3 = ord(socket_read($this->socket, 1));
            return (($byte & 0x3F) << 16) + ($byte2 << 8) + $byte3;
        } elseif ($byte < 0xF0) {
            $byte2 = ord(socket_read($this->socket, 1));
            $byte3 = ord(socket_read($this->socket, 1));
            $byte4 = ord(socket_read($this->socket, 1));
            return (($byte & 0x1F) << 24) + ($byte2 << 16) + ($byte3 << 8) + $byte4;
        } else {
            $byte2 = ord(socket_read($this->socket, 1));
            $byte3 = ord(socket_read($this->socket, 1));
            $byte4 = ord(socket_read($this->socket, 1));
            $byte5 = ord(socket_read($this->socket, 1));
            return ($byte2 << 24) + ($byte3 << 16) + ($byte4 << 8) + $byte5;
        }
    }

    /**
     * Parse API response
     */
    private function parseResponse(array $response): array
    {
        $parsed = [];
        $current = [];
        
        foreach ($response as $line) {
            if (strpos($line, '!done') === 0) {
                if (!empty($current)) {
                    $parsed[] = $current;
                    $current = [];
                }
                $current['!done'] = true;
            } elseif (strpos($line, '!re') === 0) {
                if (!empty($current)) {
                    $parsed[] = $current;
                    $current = [];
                }
            } elseif (strpos($line, '=') === 0) {
                $pos = strpos($line, '=', 1);
                if ($pos !== false) {
                    $key = substr($line, 1, $pos - 1);
                    $value = substr($line, $pos + 1);
                    $current[$key] = $value;
                }
            }
        }
        
        if (!empty($current)) {
            $parsed[] = $current;
        }
        
        return $parsed;
    }

    /**
     * Create PPP user
     */
    private function createPPPUser(array $userData): array
    {
        $params = ['=name=' . $userData['username']];
        
        if (isset($userData['password'])) {
            $params[] = '=password=' . $userData['password'];
        }
        
        if (isset($userData['profile'])) {
            $params[] = '=profile=' . $userData['profile'];
        }
        
        if (isset($userData['local_address'])) {
            $params[] = '=local-address=' . $userData['local_address'];
        }
        
        if (isset($userData['remote_address'])) {
            $params[] = '=remote-address=' . $userData['remote_address'];
        }
        
        return $this->sendCommand('/ppp/secret/add', $params);
    }

    /**
     * Update PPP user
     */
    private function updatePPPUser(string $username, array $userData): array
    {
        $users = $this->sendCommand('/ppp/secret/print', ['?name=' . $username]);
        
        if (empty($users) || !isset($users[0]['.id'])) {
            throw new \Exception("PPP user not found: {$username}");
        }
        
        $userId = $users[0]['.id'];
        $params = ['=.id=' . $userId];
        
        if (isset($userData['password'])) {
            $params[] = '=password=' . $userData['password'];
        }
        
        if (isset($userData['profile'])) {
            $params[] = '=profile=' . $userData['profile'];
        }
        
        return $this->sendCommand('/ppp/secret/set', $params);
    }

    /**
     * Create Hotspot user
     */
    private function createHotspotUser(array $userData): array
    {
        $params = ['=name=' . $userData['username']];
        
        if (isset($userData['password'])) {
            $params[] = '=password=' . $userData['password'];
        }
        
        if (isset($userData['profile'])) {
            $params[] = '=profile=' . $userData['profile'];
        }
        
        if (isset($userData['limit_uptime'])) {
            $params[] = '=limit-uptime=' . $userData['limit_uptime'];
        }
        
        return $this->sendCommand('/ip/hotspot/user/add', $params);
    }

    /**
     * Update Hotspot user
     */
    private function updateHotspotUser(string $username, array $userData): array
    {
        $users = $this->sendCommand('/ip/hotspot/user/print', ['?name=' . $username]);
        
        if (empty($users) || !isset($users[0]['.id'])) {
            throw new \Exception("Hotspot user not found: {$username}");
        }
        
        $userId = $users[0]['.id'];
        $params = ['=.id=' . $userId];
        
        if (isset($userData['password'])) {
            $params[] = '=password=' . $userData['password'];
        }
        
        if (isset($userData['profile'])) {
            $params[] = '=profile=' . $userData['profile'];
        }
        
        return $this->sendCommand('/ip/hotspot/user/set', $params);
    }

    /**
     * Set user status (enabled/disabled)
     */
    private function setUserStatus(string $username, bool $disabled): bool
    {
        try {
            $this->connect();
            
            // Try PPP user first
            $pppUsers = $this->sendCommand('/ppp/secret/print', ['?name=' . $username]);
            if (!empty($pppUsers) && isset($pppUsers[0]['.id'])) {
                $userId = $pppUsers[0]['.id'];
                $params = ['=.id=' . $userId, '=disabled=' . ($disabled ? 'yes' : 'no')];
                $this->sendCommand('/ppp/secret/set', $params);
                $this->disconnect();
                return true;
            }
            
            // Try Hotspot user
            $hotspotUsers = $this->sendCommand('/ip/hotspot/user/print', ['?name=' . $username]);
            if (!empty($hotspotUsers) && isset($hotspotUsers[0]['.id'])) {
                $userId = $hotspotUsers[0]['.id'];
                $params = ['=.id=' . $userId, '=disabled=' . ($disabled ? 'yes' : 'no')];
                $this->sendCommand('/ip/hotspot/user/set', $params);
                $this->disconnect();
                return true;
            }
            
            $this->disconnect();
            return false;
        } catch (\Exception $e) {
            $this->disconnect();
            return false;
        }
    }
}