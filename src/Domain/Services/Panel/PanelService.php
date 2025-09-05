<?php

declare(strict_types=1);

namespace BotMirzaPanel\Domain\Services\Panel;

use BotMirzaPanel\Domain\Entities\Panel\Panel;
use BotMirzaPanel\Domain\Entities\Panel\PanelUser;
use BotMirzaPanel\Domain\ValueObjects\Panel\PanelId;
use BotMirzaPanel\Domain\ValueObjects\Panel\PanelType;
use BotMirzaPanel\Domain\ValueObjects\User\UserId;
use BotMirzaPanel\Domain\ValueObjects\Common\Url;
use BotMirzaPanel\Domain\ValueObjects\Common\DataLimit;
use BotMirzaPanel\Domain\Exceptions\ValidationException;
use Exception;
use DateTimeImmutable;

/**
 * Panel domain service for handling panel-related business logic
 */
class PanelService
{
    /**
     * Create a new panel with validation
     */
    public function createPanel(
        string $name,
        PanelType $type,
        Url $url,
        string $username,
        string $password,
        ?string $apiKey = null,
        ?array $settings = null
    ): Panel {
        $this->validatePanelConfiguration($type, $url, $username, $password, $apiKey);
        
        $panelId = PanelId::generate();
        
        return Panel::create(
            $panelId,
            $name,
            $type,
            $url,
            $username,
            $password,
            $apiKey,
            $settings ?? []
        );
    }
    
    /**
     * Test panel connection
     */
    public function testPanelConnection(Panel $panel): bool
    {
        if (!$panel->isActive()) {
            throw new Exception(
                'Cannot test connection for inactive panel'
            );
        }
        
        // This would typically use an adapter to test the actual connection
        // For now, we'll simulate the test based on panel configuration
        return $this->validatePanelEndpoint($panel);
    }
    
    /**
     * Create a panel user
     */
    public function createPanelUser(
        Panel $panel,
        UserId $userId,
        string $username,
        string $password,
        ?DataLimit $dataLimit = null,
        ?DateTimeImmutable $expiresAt = null,
        ?array $protocols = null
    ): PanelUser {
        if (!$panel->isActive()) {
            throw new ValidationException(
                'Cannot create user on inactive panel'
            );
        }
        
        $this->validatePanelUserData($username, $password, $protocols);
        
        return PanelUser::create(
            $panel->getId(),
            $userId,
            $username,
            $password,
            $dataLimit,
            $expiresAt,
            $protocols ?? $this->getDefaultProtocols($panel->getType())
        );
    }
    
    /**
     * Update panel user
     */
    public function updatePanelUser(
        PanelUser $panelUser,
        ?string $password = null,
        ?DataLimit $dataLimit = null,
        ?DateTimeImmutable $expiresAt = null,
        ?array $protocols = null
    ): PanelUser {
        if (!$panelUser->isActive()) {
            throw new ValidationException(
                'Cannot update inactive panel user'
            );
        }
        
        if ($password !== null) {
            $this->validatePassword($password);
            $panelUser->updatePassword($password);
        }
        
        if ($dataLimit !== null) {
            $panelUser->updateDataLimit($dataLimit);
        }
        
        if ($expiresAt !== null) {
            $panelUser->updateExpiration($expiresAt);
        }
        
        if ($protocols !== null) {
            $this->validateProtocols($protocols);
            $panelUser->updateProtocols($protocols);
        }
        
        return $panelUser;
    }
    
    /**
     * Suspend panel user
     */
    public function suspendPanelUser(PanelUser $panelUser, string $reason): PanelUser
    {
        if (!$panelUser->canBeSuspended()) {
            throw new ValidationException(
                'Panel user cannot be suspended in current status'
            );
        }
        
        $panelUser->suspend($reason);
        
        return $panelUser;
    }
    
    /**
     * Activate panel user
     */
    public function activatePanelUser(PanelUser $panelUser): PanelUser
    {
        if (!$panelUser->canBeActivated()) {
            throw new ValidationException(
                'Panel user cannot be activated in current status'
            );
        }
        
        $panelUser->activate();
        
        return $panelUser;
    }
    
    /**
     * Delete panel user
     */
    public function deletePanelUser(PanelUser $panelUser): PanelUser
    {
        $panelUser->delete();
        
        return $panelUser;
    }
    
    /**
     * Check if panel user is expired
     */
    public function isPanelUserExpired(PanelUser $panelUser): bool
    {
        $expiresAt = $panelUser->getExpiresAt();
        
        if ($expiresAt === null) {
            return false;
        }
        
        return new DateTimeImmutable() > $expiresAt;
    }
    
    /**
     * Check if panel user has exceeded data limit
     */
    public function hasPanelUserExceededDataLimit(PanelUser $panelUser): bool
    {
        $dataLimit = $panelUser->getDataLimit();
        
        if ($dataLimit === null || $dataLimit->isUnlimited()) {
            return false;
        }
        
        $usedData = $panelUser->getUsedData();
        
        return $usedData->isGreaterThanOrEqual($dataLimit);
    }
    
    /**
     * Get panel user connection config
     */
    public function getPanelUserConnectionConfig(Panel $panel, PanelUser $panelUser): array
    {
        if (!$panelUser->isActive()) {
            throw new ValidationException(
                'Cannot get connection config for inactive user'
            );
        }
        
        $baseUrl = $panel->getUrl();
        $protocols = $panelUser->getProtocols();
        
        $configs = [];
        
        foreach ($protocols as $protocol) {
            $configs[$protocol] = $this->generateProtocolConfig(
                $panel,
                $panelUser,
                $protocol
            );
        }
        
        return $configs;
    }
    
    /**
     * Get panel statistics
     */
    public function getPanelStatistics(Panel $panel): array
    {
        // This would typically use a repository to fetch data
        return [
            'total_users' => 0,
            'active_users' => 0,
            'suspended_users' => 0,
            'expired_users' => 0,
            'total_data_usage' => DataLimit::zero(),
            'average_data_usage' => DataLimit::zero(),
            'connection_success_rate' => 100.0,
        ];
    }
    
    /**
     * Validate panel configuration
     */
    private function validatePanelConfiguration(
        PanelType $type,
        Url $url,
        string $username,
        string $password,
        ?string $apiKey
    ): void {
        if (!$url->isSecure() && !$url->isLocalhost()) {
            throw new ValidationException(
                'Panel URL must use HTTPS for non-localhost connections'
            );
        }
        
        if (empty($username) || strlen($username) < 3) {
            throw new ValidationException(
                'Panel username must be at least 3 characters long'
            );
        }
        
        if (empty($password) || strlen($password) < 6) {
            throw new ValidationException(
                'Panel password must be at least 6 characters long'
            );
        }
        
        if ($type->requiresApiKey() && empty($apiKey)) {
            throw new ValidationException(
                'API key is required for panel type: ' . $type->getValue()
            );
        }
    }
    
    /**
     * Validate panel endpoint
     */
    private function validatePanelEndpoint(Panel $panel): bool
    {
        $url = $panel->getUrl();
        
        // Basic URL validation
        if (!$url->isAbsolute()) {
            return false;
        }
        
        // Check if URL is reachable (simplified)
        $expectedPort = $panel->getType()->getDefaultPort();
        $actualPort = $url->getPort();
        
        return $actualPort === null || $actualPort === $expectedPort;
    }
    
    /**
     * Validate panel user data
     */
    private function validatePanelUserData(string $username, string $password, ?array $protocols): void
    {
        if (empty($username) || strlen($username) < 3) {
            throw new ValidationException(
                'Panel user username must be at least 3 characters long'
            );
        }
        
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            throw new ValidationException(
                'Panel user username can only contain letters, numbers, underscores, and hyphens'
            );
        }
        
        $this->validatePassword($password);
        
        if ($protocols !== null) {
            $this->validateProtocols($protocols);
        }
    }
    
    /**
     * Validate password
     */
    private function validatePassword(string $password): void
    {
        if (empty($password) || strlen($password) < 8) {
            throw new ValidationException(
                'Panel user password must be at least 8 characters long'
            );
        }
    }
    
    /**
     * Validate protocols
     */
    private function validateProtocols(array $protocols): void
    {
        $allowedProtocols = ['vmess', 'vless', 'trojan', 'shadowsocks', 'wireguard'];
        
        foreach ($protocols as $protocol) {
            if (!in_array($protocol, $allowedProtocols, true)) {
                throw new ValidationException(
                    'Invalid protocol: ' . $protocol
                );
            }
        }
    }
    
    /**
     * Get default protocols for panel type
     */
    private function getDefaultProtocols(PanelType $type): array
    {
        return $type->getSupportedProtocols();
    }
    
    /**
     * Generate protocol configuration
     */
    private function generateProtocolConfig(Panel $panel, PanelUser $panelUser, string $protocol): array
    {
        $baseConfig = [
            'protocol' => $protocol,
            'server' => $panel->getUrl()->getHost(),
            'port' => $this->getProtocolPort($panel, $protocol),
            'username' => $panelUser->getUsername(),
            'password' => $panelUser->getPassword(),
        ];
        
        // Add protocol-specific configurations
        switch ($protocol) {
            case 'vmess':
                $baseConfig['alterId'] = 0;
                $baseConfig['security'] = 'auto';
                break;
            case 'vless':
                $baseConfig['encryption'] = 'none';
                break;
            case 'trojan':
                $baseConfig['sni'] = $panel->getUrl()->getHost();
                break;
        }
        
        return $baseConfig;
    }
    
    /**
     * Get protocol port
     */
    private function getProtocolPort(Panel $panel, string $protocol): int
    {
        $defaultPorts = [
            'vmess' => 443,
            'vless' => 443,
            'trojan' => 443,
            'shadowsocks' => 8388,
            'wireguard' => 51820,
        ];
        
        return $defaultPorts[$protocol] ?? 443;
    }
}