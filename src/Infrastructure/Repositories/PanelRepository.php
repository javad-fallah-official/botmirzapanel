<?php

declare(strict_types=1);

namespace BotMirzaPanel\Infrastructure\Repositories;

use BotMirzaPanel\Database\BaseRepository;
use BotMirzaPanel\Database\DatabaseManager;
use BotMirzaPanel\Domain\Entities\Panel\Panel;
use BotMirzaPanel\Domain\Entities\Panel\PanelConfiguration;
use BotMirzaPanel\Domain\Entities\Panel\PanelUser;
use BotMirzaPanel\Domain\Repositories\PanelRepositoryInterface;
use BotMirzaPanel\Domain\ValueObjects\Common\DateTimeRange;
use BotMirzaPanel\Domain\ValueObjects\Common\Url;
use BotMirzaPanel\Domain\ValueObjects\Panel\ConnectionConfig;
use BotMirzaPanel\Domain\ValueObjects\Panel\PanelId;
use BotMirzaPanel\Domain\ValueObjects\Panel\PanelType;
use BotMirzaPanel\Domain\ValueObjects\User\UserId;

/**
 * Panel repository implementation for data persistence operations
 */
class PanelRepository extends BaseRepository implements PanelRepositoryInterface
{
    protected string $table = 'panels';
    protected string $primaryKey = 'id';
    protected array $fillable = [
        'name', 'type', 'url', 'api_key', 'username', 'password',
        'is_active', 'is_healthy', 'last_health_check', 'last_health_check_error',
        'capabilities', 'statistics', 'created_at', 'updated_at'
    ];
    
    public function __construct(DatabaseManager $db)
    {
        parent::__construct($db, 'panels');
    }

    /**
     * Find panel by ID
     */
    public function findById(PanelId $id): ?Panel
    {
        $data = $this->db->fetchOne(
            "SELECT * FROM {$this->table} WHERE id = :id",
            ['id' => $id->getValue()]
        );

        if (!$data) {
            return null;
        }

        return $this->mapToEntity($data);
    }

    /**
     * Find panel by name
     */
    public function findByName(string $name): ?Panel
    {
        $data = $this->db->fetchOne(
            "SELECT * FROM {$this->table} WHERE name = :name",
            ['name' => $name]
        );

        if (!$data) {
            return null;
        }

        return $this->mapToEntity($data);
    }
    
    /**
     * Find panel by URL
     */
    public function findByUrl(Url $url): ?Panel
    {
        $data = $this->db->fetchOne(
            "SELECT * FROM {$this->table} WHERE url = :url",
            ['url' => $url->getValue()]
        );

        if (!$data) {
            return null;
        }

        return $this->mapToEntity($data);
    }
    
    /**
     * Find panels by type
     */
    public function findByType(PanelType $type, int $limit = 100, int $offset = 0): array
    {
        $data = $this->db->fetchAll(
            "SELECT * FROM {$this->table} WHERE type = :type LIMIT :limit OFFSET :offset",
            ['type' => $type->getValue(), 'limit' => $limit, 'offset' => $offset]
        );

        return array_map([$this, 'mapToEntity'], $data);
    }
    
    /**
     * Find active panels
     */
    public function findActivePanels(int $limit = 100, int $offset = 0): array
    {
        $data = $this->db->fetchAll(
            "SELECT * FROM {$this->table} WHERE is_active = 1 LIMIT :limit OFFSET :offset",
            ['limit' => $limit, 'offset' => $offset]
        );

        return array_map([$this, 'mapToEntity'], $data);
    }
    
    /**
     * Find inactive panels
     */
    public function findInactivePanels(int $limit = 100, int $offset = 0): array
    {
        $data = $this->db->fetchAll(
            "SELECT * FROM {$this->table} WHERE is_active = 0 LIMIT :limit OFFSET :offset",
            ['limit' => $limit, 'offset' => $offset]
        );

        return array_map([$this, 'mapToEntity'], $data);
    }

    /**
     * Check if panel name exists
     */
    public function nameExists(string $name): bool
    {
        return $this->exists(['name' => $name]);
    }
    
    /**
     * Check if panel URL exists
     */
    public function urlExists(Url $url): bool
    {
        return $this->exists(['url' => $url->getValue()]);
    }
    
    /**
     * Save panel (create or update)
     */
    public function save(Panel $panel): void
    {
        $data = [
            'name' => $panel->getName(),
            'type' => $panel->getType()->getValue(),
            'url' => $panel->getConnectionConfig()->getUrl()->getValue(),
            'api_key' => $panel->getConnectionConfig()->getApiKey(),
            'username' => $panel->getConnectionConfig()->getUsername(),
            'password' => $panel->getConnectionConfig()->getPassword(),
            'is_active' => $panel->isActive() ? 1 : 0,
            'is_healthy' => $panel->isHealthy() ? 1 : 0,
            'capabilities' => json_encode($panel->getCapabilities()),
            'statistics' => json_encode($panel->getStatistics()),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $lastHealthCheck = $panel->getLastHealthCheck();
        if ($lastHealthCheck) {
            $data['last_health_check'] = $lastHealthCheck->format('Y-m-d H:i:s');
            $data['last_health_check_error'] = $panel->getLastHealthCheckError();
        }

        $panelId = $panel->getId()->getValue();
        $exists = $this->exists(['id' => $panelId]);

        if ($exists) {
            $this->db->update($this->table, $data, ['id' => $panelId]);
        } else {
            $data['id'] = $panelId;
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert($this->table, $data);

            // Save panel configuration if available
            if ($panel->getConfiguration()) {
                $this->savePanelConfiguration($panel->getConfiguration());
            }
        }
    }

    /**
     * Delete panel
     */
    public function delete(PanelId $id): void
    {
        // First delete related panel users
        $this->db->delete('panel_users', ['panel_id' => $id->getValue()]);
        
        // Delete panel configuration
        $this->db->delete('panel_configurations', ['panel_id' => $id->getValue()]);
        
        // Delete panel
        $this->db->delete($this->table, ['id' => $id->getValue()]);
    }

    /**
     * Find panel user by ID
     */
    public function findPanelUserById(string $panelUserId): ?PanelUser
    {
        $data = $this->db->fetchOne(
            "SELECT * FROM panel_users WHERE id = :id",
            ['id' => $panelUserId]
        );

        if (!$data) {
            return null;
        }

        return $this->mapToPanelUserEntity($data);
    }
    
    /**
     * Find panel user by panel and user ID
     */
    public function findPanelUserByPanelAndUser(PanelId $panelId, UserId $userId): ?PanelUser
    {
        $data = $this->db->fetchOne(
            "SELECT * FROM panel_users WHERE panel_id = :panel_id AND user_id = :user_id",
            ['panel_id' => $panelId->getValue(), 'user_id' => $userId->getValue()]
        );

        if (!$data) {
            return null;
        }

        return $this->mapToPanelUserEntity($data);
    }
    
    /**
     * Find panel user by username
     */
    public function findPanelUserByUsername(PanelId $panelId, string $username): ?PanelUser
    {
        $data = $this->db->fetchOne(
            "SELECT * FROM panel_users WHERE panel_id = :panel_id AND panel_username = :username",
            ['panel_id' => $panelId->getValue(), 'username' => $username]
        );

        if (!$data) {
            return null;
        }

        return $this->mapToPanelUserEntity($data);
    }
    
    /**
     * Find panel users by panel
     */
    public function findPanelUsersByPanel(PanelId $panelId, int $limit = 100, int $offset = 0): array
    {
        $data = $this->db->fetchAll(
            "SELECT * FROM panel_users WHERE panel_id = :panel_id LIMIT :limit OFFSET :offset",
            ['panel_id' => $panelId->getValue(), 'limit' => $limit, 'offset' => $offset]
        );

        return array_map([$this, 'mapToPanelUserEntity'], $data);
    }
    
    /**
     * Find panel users by user
     */
    public function findPanelUsersByUser(UserId $userId, int $limit = 100, int $offset = 0): array
    {
        $data = $this->db->fetchAll(
            "SELECT * FROM panel_users WHERE user_id = :user_id LIMIT :limit OFFSET :offset",
            ['user_id' => $userId->getValue(), 'limit' => $limit, 'offset' => $offset]
        );

        return array_map([$this, 'mapToPanelUserEntity'], $data);
    }
    
    /**
     * Find active panel users
     */
    public function findActivePanelUsers(PanelId $panelId, int $limit = 100, int $offset = 0): array
    {
        $data = $this->db->fetchAll(
            "SELECT * FROM panel_users WHERE panel_id = :panel_id AND is_enabled = 1 LIMIT :limit OFFSET :offset",
            ['panel_id' => $panelId->getValue(), 'limit' => $limit, 'offset' => $offset]
        );

        return array_map([$this, 'mapToPanelUserEntity'], $data);
    }
    
    /**
     * Find suspended panel users
     */
    public function findSuspendedPanelUsers(PanelId $panelId, int $limit = 100, int $offset = 0): array
    {
        $data = $this->db->fetchAll(
            "SELECT * FROM panel_users WHERE panel_id = :panel_id AND is_enabled = 0 LIMIT :limit OFFSET :offset",
            ['panel_id' => $panelId->getValue(), 'limit' => $limit, 'offset' => $offset]
        );

        return array_map([$this, 'mapToPanelUserEntity'], $data);
    }
    
    /**
     * Find expired panel users
     */
    public function findExpiredPanelUsers(PanelId $panelId, \DateTimeImmutable $asOf, int $limit = 100): array
    {
        $data = $this->db->fetchAll(
            "SELECT * FROM panel_users WHERE panel_id = :panel_id AND expiry_date < :expiry_date LIMIT :limit",
            [
                'panel_id' => $panelId->getValue(),
                'expiry_date' => $asOf->format('Y-m-d H:i:s'),
                'limit' => $limit
            ]
        );

        return array_map([$this, 'mapToPanelUserEntity'], $data);
    }
    
    /**
     * Find panel users expiring soon
     */
    public function findPanelUsersExpiringSoon(PanelId $panelId, \DateTimeImmutable $before, int $limit = 100): array
    {
        $now = new \DateTimeImmutable();
        
        $data = $this->db->fetchAll(
            "SELECT * FROM panel_users WHERE panel_id = :panel_id AND expiry_date > :now AND expiry_date < :before LIMIT :limit",
            [
                'panel_id' => $panelId->getValue(),
                'now' => $now->format('Y-m-d H:i:s'),
                'before' => $before->format('Y-m-d H:i:s'),
                'limit' => $limit
            ]
        );

        return array_map([$this, 'mapToPanelUserEntity'], $data);
    }
    
    /**
     * Check if panel username exists
     */
    public function panelUsernameExists(PanelId $panelId, string $username): bool
    {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM panel_users WHERE panel_id = :panel_id AND panel_username = :username",
            ['panel_id' => $panelId->getValue(), 'username' => $username]
        );
        return (int) ($result['count'] ?? 0) > 0;
    }
    
    /**
     * Save panel user (create or update)
     */
    public function savePanelUser(PanelUser $panelUser): void
    {
        $data = [
            'panel_id' => $panelUser->getPanelId()->getValue(),
            'user_id' => $panelUser->getUserId()->getValue(),
            'panel_username' => $panelUser->getPanelUsername(),
            'panel_user_id' => $panelUser->getPanelUserId(),
            'is_enabled' => $panelUser->isEnabled() ? 1 : 0,
            'data_limit' => $panelUser->getDataLimit(),
            'data_used' => $panelUser->getDataUsed(),
            'config_url' => $panelUser->getConfigUrl(),
            'subscription_url' => $panelUser->getSubscriptionUrl(),
            'inbounds' => json_encode($panelUser->getInbounds()),
            'statistics' => json_encode($panelUser->getStatistics()),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $expiryDate = $panelUser->getExpiryDate();
        if ($expiryDate) {
            $data['expiry_date'] = $expiryDate->format('Y-m-d H:i:s');
        }

        $lastSyncAt = $panelUser->getLastSyncAt();
        if ($lastSyncAt) {
            $data['last_sync_at'] = $lastSyncAt->format('Y-m-d H:i:s');
        }

        $panelUserId = $panelUser->getId();
        $exists = $this->db->exists('panel_users', ['id' => $panelUserId]);

        if ($exists) {
            $this->db->update('panel_users', $data, ['id' => $panelUserId]);
        } else {
            $data['id'] = $panelUserId;
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('panel_users', $data);
        }
    }
    
    /**
     * Delete panel user
     */
    public function deletePanelUser(string $panelUserId): void
    {
        $this->db->delete('panel_users', ['id' => $panelUserId]);
    }
    
    /**
     * Count total panels
     */
    public function countTotal(): int
    {
        $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM {$this->table}");
        return (int) ($result['count'] ?? 0);
    }
    
    /**
     * Count panels by type
     */
    public function countByType(PanelType $type): int
    {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE type = :type",
            ['type' => $type->getValue()]
        );
        return (int) ($result['count'] ?? 0);
    }
    
    /**
     * Count active panels
     */
    public function countActivePanels(): int
    {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE is_active = 1"
        );
        return (int) ($result['count'] ?? 0);
    }
    
    /**
     * Count panel users by panel
     */
    public function countPanelUsersByPanel(PanelId $panelId): int
    {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM panel_users WHERE panel_id = :panel_id",
            ['panel_id' => $panelId->getValue()]
        );
        return (int) ($result['count'] ?? 0);
    }
    
    /**
     * Count active panel users by panel
     */
    public function countActivePanelUsersByPanel(PanelId $panelId): int
    {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM panel_users WHERE panel_id = :panel_id AND is_enabled = 1",
            ['panel_id' => $panelId->getValue()]
        );
        return (int) ($result['count'] ?? 0);
    }
    
    /**
     * Count panel users by user
     */
    public function countPanelUsersByUser(UserId $userId): int
    {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM panel_users WHERE user_id = :user_id",
            ['user_id' => $userId->getValue()]
        );
        return (int) ($result['count'] ?? 0);
    }

    /**
     * Get panel statistics
     */
    public function getPanelStatistics(PanelId $panelId): array
    {
        $panel = $this->findById($panelId);
        if (!$panel) {
            return [];
        }
        
        return $panel->getStatistics();
    }
    
    /**
     * Get panel user statistics
     */
    public function getPanelUserStatistics(PanelId $panelId, DateTimeRange $dateRange): array
    {
        $startDate = $dateRange->getStart()->format('Y-m-d H:i:s');
        $endDate = $dateRange->getEnd()->format('Y-m-d H:i:s');
        
        $result = $this->db->fetchAll(
            "SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN is_enabled = 1 THEN 1 ELSE 0 END) as active_users,
                SUM(CASE WHEN is_enabled = 0 THEN 1 ELSE 0 END) as suspended_users,
                SUM(CASE WHEN expiry_date < NOW() THEN 1 ELSE 0 END) as expired_users,
                SUM(data_limit) as total_data_limit,
                SUM(data_used) as total_data_used
            FROM panel_users 
            WHERE panel_id = :panel_id 
            AND created_at BETWEEN :start_date AND :end_date",
            [
                'panel_id' => $panelId->getValue(),
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        );
        
        return $result[0] ?? [];
    }
    
    /**
     * Get panel usage summary
     */
    public function getPanelUsageSummary(PanelId $panelId, DateTimeRange $dateRange): array
    {
        $startDate = $dateRange->getStart()->format('Y-m-d H:i:s');
        $endDate = $dateRange->getEnd()->format('Y-m-d H:i:s');
        
        // Get daily usage data
        $dailyData = $this->db->fetchAll(
            "SELECT 
                DATE(created_at) as date,
                COUNT(*) as new_users,
                SUM(data_used) as data_used
            FROM panel_users 
            WHERE panel_id = :panel_id 
            AND created_at BETWEEN :start_date AND :end_date
            GROUP BY DATE(created_at)
            ORDER BY date ASC",
            [
                'panel_id' => $panelId->getValue(),
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        );
        
        // Get overall summary
        $summary = $this->getPanelUserStatistics($panelId, $dateRange);
        
        return [
            'summary' => $summary,
            'daily_data' => $dailyData
        ];
    }
    
    /**
     * Find panels for health check
     */
    public function findPanelsForHealthCheck(): array
    {
        $fiveMinutesAgo = (new \DateTime('-5 minutes'))->format('Y-m-d H:i:s');
        
        $data = $this->db->fetchAll(
            "SELECT * FROM {$this->table} 
            WHERE is_active = 1 
            AND (last_health_check IS NULL OR last_health_check < :five_minutes_ago)
            LIMIT 10",
            ['five_minutes_ago' => $fiveMinutesAgo]
        );

        return array_map([$this, 'mapToEntity'], $data);
    }
    
    /**
     * Update panel last health check
     */
    public function updateLastHealthCheck(PanelId $panelId, \DateTimeImmutable $lastCheck, bool $isHealthy): void
    {
        $this->db->update(
            $this->table,
            [
                'last_health_check' => $lastCheck->format('Y-m-d H:i:s'),
                'is_healthy' => $isHealthy ? 1 : 0,
                'last_health_check_error' => $isHealthy ? null : 'Health check failed',
                'updated_at' => date('Y-m-d H:i:s')
            ],
            ['id' => $panelId->getValue()]
        );
    }
    
    /**
     * Find panels with failed health checks
     */
    public function findPanelsWithFailedHealthChecks(\DateTimeImmutable $since): array
    {
        $data = $this->db->fetchAll(
            "SELECT * FROM {$this->table} 
            WHERE is_active = 1 
            AND is_healthy = 0
            AND last_health_check >= :since
            LIMIT 100",
            ['since' => $since->format('Y-m-d H:i:s')]
        );

        return array_map([$this, 'mapToEntity'], $data);
    }
    
    /**
     * Search panels by criteria
     */
    public function searchPanels(array $criteria, int $limit = 100, int $offset = 0): array
    {
        $whereClauses = [];
        $params = [];
        
        if (isset($criteria['name'])) {
            $whereClauses[] = "name LIKE :name";
            $params['name'] = '%' . $criteria['name'] . '%';
        }
        
        if (isset($criteria['type'])) {
            $whereClauses[] = "type = :type";
            $params['type'] = $criteria['type'];
        }
        
        if (isset($criteria['is_active'])) {
            $whereClauses[] = "is_active = :is_active";
            $params['is_active'] = $criteria['is_active'] ? 1 : 0;
        }
        
        if (isset($criteria['is_healthy'])) {
            $whereClauses[] = "is_healthy = :is_healthy";
            $params['is_healthy'] = $criteria['is_healthy'] ? 1 : 0;
        }
        
        $whereClause = empty($whereClauses) ? '1=1' : implode(' AND ', $whereClauses);
        $params['limit'] = $limit;
        $params['offset'] = $offset;
        
        $data = $this->db->fetchAll(
            "SELECT * FROM {$this->table} WHERE {$whereClause} LIMIT :limit OFFSET :offset",
            $params
        );

        return array_map([$this, 'mapToEntity'], $data);
    }
    
    /**
     * Search panel users by criteria
     */
    public function searchPanelUsers(array $criteria, int $limit = 100, int $offset = 0): array
    {
        $whereClauses = [];
        $params = [];
        
        if (isset($criteria['panel_id'])) {
            $whereClauses[] = "panel_id = :panel_id";
            $params['panel_id'] = $criteria['panel_id'];
        }
        
        if (isset($criteria['user_id'])) {
            $whereClauses[] = "user_id = :user_id";
            $params['user_id'] = $criteria['user_id'];
        }
        
        if (isset($criteria['username'])) {
            $whereClauses[] = "panel_username LIKE :username";
            $params['username'] = '%' . $criteria['username'] . '%';
        }
        
        if (isset($criteria['is_enabled'])) {
            $whereClauses[] = "is_enabled = :is_enabled";
            $params['is_enabled'] = $criteria['is_enabled'] ? 1 : 0;
        }
        
        if (isset($criteria['expired'])) {
            if ($criteria['expired']) {
                $whereClauses[] = "expiry_date < NOW()";
            } else {
                $whereClauses[] = "expiry_date > NOW()";
            }
        }
        
        $whereClause = empty($whereClauses) ? '1=1' : implode(' AND ', $whereClauses);
        $params['limit'] = $limit;
        $params['offset'] = $offset;
        
        $data = $this->db->fetchAll(
            "SELECT * FROM panel_users WHERE {$whereClause} LIMIT :limit OFFSET :offset",
            $params
        );

        return array_map([$this, 'mapToPanelUserEntity'], $data);
    }
    
    /**
     * Find panels for synchronization
     */
    public function findPanelsForSync(): array
    {
        $data = $this->db->fetchAll(
            "SELECT p.* FROM {$this->table} p
            JOIN panel_configurations pc ON p.id = pc.panel_id
            WHERE p.is_active = 1 AND p.is_healthy = 1 AND pc.auto_sync = 1
            AND (pc.last_sync IS NULL OR pc.last_sync < DATE_SUB(NOW(), INTERVAL pc.sync_interval MINUTE))
            LIMIT 5"
        );

        return array_map([$this, 'mapToEntity'], $data);
    }
    
    /**
     * Update panel last sync
     */
    public function updateLastSync(PanelId $panelId, \DateTimeImmutable $lastSync): void
    {
        $this->db->update(
            'panel_configurations',
            [
                'last_sync' => $lastSync->format('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            ['panel_id' => $panelId->getValue()]
        );
    }
    
    /**
     * Find panel users for bulk operations
     */
    public function findPanelUsersForBulkOperation(array $panelUserIds): array
    {
        if (empty($panelUserIds)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($panelUserIds), '?'));
        
        $data = $this->db->fetchAll(
            "SELECT * FROM panel_users WHERE id IN ({$placeholders})",
            $panelUserIds
        );

        return array_map([$this, 'mapToPanelUserEntity'], $data);
    }
    
    /**
     * Update panel user data usage
     */
    public function updatePanelUserDataUsage(string $panelUserId, int $usedBytes): void
    {
        $this->db->update(
            'panel_users',
            [
                'data_used' => $usedBytes,
                'updated_at' => date('Y-m-d H:i:s')
            ],
            ['id' => $panelUserId]
        );
    }
    
    /**
     * Get panel users with high data usage
     */
    public function findPanelUsersWithHighDataUsage(PanelId $panelId, int $thresholdBytes, int $limit = 100): array
    {
        $data = $this->db->fetchAll(
            "SELECT * FROM panel_users 
            WHERE panel_id = :panel_id 
            AND data_limit > 0
            AND data_used >= :threshold
            LIMIT :limit",
            [
                'panel_id' => $panelId->getValue(),
                'threshold' => $thresholdBytes,
                'limit' => $limit
            ]
        );

        return array_map([$this, 'mapToPanelUserEntity'], $data);
    }
    
    /**
     * Save panel configuration
     */
    private function savePanelConfiguration(PanelConfiguration $config): void
    {
        $data = [
            'panel_id' => $config->getPanelId()->getValue(),
            'settings' => json_encode($config->getSettings()),
            'default_user_settings' => json_encode($config->getDefaultUserSettings()),
            'inbound_settings' => json_encode($config->getInboundSettings()),
            'protocol_settings' => json_encode($config->getProtocolSettings()),
            'auto_sync' => $config->isAutoSyncEnabled() ? 1 : 0,
            'sync_interval' => $config->getSyncInterval(),
            'enforce_data_limits' => $config->isEnforceDataLimitsEnabled() ? 1 : 0,
            'enforce_expiry' => $config->isEnforceExpiryEnabled() ? 1 : 0,
            'allow_user_creation' => $config->isUserCreationAllowed() ? 1 : 0,
            'allow_user_modification' => $config->isUserModificationAllowed() ? 1 : 0,
            'allow_user_deletion' => $config->isUserDeletionAllowed() ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $lastSync = $config->getLastSync();
        if ($lastSync) {
            $data['last_sync'] = $lastSync->format('Y-m-d H:i:s');
        }

        $exists = $this->db->exists('panel_configurations', ['panel_id' => $config->getPanelId()->getValue()]);

        if ($exists) {
            $this->db->update('panel_configurations', $data, ['panel_id' => $config->getPanelId()->getValue()]);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('panel_configurations', $data);
        }
    }

    /**
     * Map database row to Panel entity
     */
    private function mapToEntity(array $data): Panel
    {
        $panelId = new PanelId($data['id']);
        $panelType = PanelType::fromString($data['type']);
        
        // Create connection config
        $url = new Url($data['url']);
        $connectionConfig = new ConnectionConfig(
            $url,
            $data['api_key'] ?? null,
            $data['username'] ?? null,
            $data['password'] ?? null
        );
        
        // Create panel entity
        $panel = new Panel(
            $panelId,
            $data['name'],
            $panelType,
            $connectionConfig,
            json_decode($data['capabilities'] ?? '[]', true)
        );
        
        // Set additional properties
        if ($data['is_active']) {
            $panel->activate();
        }
        
        if ($data['last_health_check']) {
            $panel->updateHealthStatus(
                (bool) $data['is_healthy'],
                $data['last_health_check_error'] ?? null
            );
        }
        
        if (!empty($data['statistics'])) {
            $panel->updateStatistics(json_decode($data['statistics'], true));
        }
        
        // Load panel configuration
        $configData = $this->db->fetchOne(
            "SELECT * FROM panel_configurations WHERE panel_id = :panel_id",
            ['panel_id' => $panelId->getValue()]
        );
        
        if ($configData) {
            $panel->setConfiguration($this->mapToPanelConfigurationEntity($configData));
        }
        
        return $panel;
    }

    /**
     * Map database row to PanelUser entity
     */
    private function mapToPanelUserEntity(array $data): PanelUser
    {
        $panelId = new PanelId($data['panel_id']);
        $userId = new UserId($data['user_id']);
        
        $panelUser = PanelUser::create(
            $panelId,
            $userId,
            $data['panel_username'],
            $data['panel_user_id'] ?? null
        );
        
        // Set ID
        $reflectionClass = new \ReflectionClass($panelUser);
        $idProperty = $reflectionClass->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($panelUser, $data['id']);
        
        // Set additional properties
        if ($data['is_enabled']) {
            $panelUser->enable();
        } else {
            $panelUser->disable();
        }
        
        if ($data['data_limit'] > 0) {
            $panelUser->updateDataLimit((int) $data['data_limit']);
        }
        
        if ($data['data_used'] > 0) {
            $panelUser->updateDataUsed((int) $data['data_used']);
        }
        
        if ($data['expiry_date']) {
            $panelUser->updateExpiryDate(new \DateTimeImmutable($data['expiry_date']));
        }
        
        if ($data['config_url']) {
            $panelUser->updateConfigUrl($data['config_url']);
        }
        
        if ($data['subscription_url']) {
            $panelUser->updateSubscriptionUrl($data['subscription_url']);
        }
        
        if (!empty($data['inbounds'])) {
            $panelUser->updateInbounds(json_decode($data['inbounds'], true));
        }
        
        if (!empty($data['statistics'])) {
            $panelUser->updateStatistics(json_decode($data['statistics'], true));
        }
        
        if ($data['last_sync_at']) {
            $reflectionClass = new \ReflectionClass($panelUser);
            $lastSyncProperty = $reflectionClass->getProperty('lastSyncAt');
            $lastSyncProperty->setAccessible(true);
            $lastSyncProperty->setValue($panelUser, new \DateTimeImmutable($data['last_sync_at']));
        }
        
        return $panelUser;
    }

    /**
     * Map database row to PanelConfiguration entity
     */
    private function mapToPanelConfigurationEntity(array $data): PanelConfiguration
    {
        $panelId = new PanelId($data['panel_id']);
        
        $config = PanelConfiguration::create(
            $panelId,
            json_decode($data['settings'] ?? '{}', true),
            json_decode($data['default_user_settings'] ?? '{}', true)
        );
        
        // Set additional properties
        if (!empty($data['inbound_settings'])) {
            $config->updateInboundSettings(json_decode($data['inbound_settings'], true));
        }
        
        if (!empty($data['protocol_settings'])) {
            $config->updateProtocolSettings(json_decode($data['protocol_settings'], true));
        }
        
        if ($data['auto_sync']) {
            $config->enableAutoSync((int) $data['sync_interval']);
        } else {
            $config->disableAutoSync();
        }
        
        if ($data['enforce_data_limits']) {
            $config->enableEnforceDataLimits();
        } else {
            $config->disableEnforceDataLimits();
        }
        
        if ($data['enforce_expiry']) {
            $config->enableEnforceExpiry();
        } else {
            $config->disableEnforceExpiry();
        }
        
        if ($data['allow_user_creation']) {
            $config->allowUserCreation();
        } else {
            $config->disallowUserCreation();
        }
        
        if ($data['allow_user_modification']) {
            $config->allowUserModification();
        } else {
            $config->disallowUserModification();
        }
        
        if ($data['allow_user_deletion']) {
            $config->allowUserDeletion();
        } else {
            $config->disallowUserDeletion();
        }
        
        if ($data['last_sync']) {
            $reflectionClass = new \ReflectionClass($config);
            $lastSyncProperty = $reflectionClass->getProperty('lastSync');
            $lastSyncProperty->setAccessible(true);
            $lastSyncProperty->setValue($config, new \DateTimeImmutable($data['last_sync']));
        }
        
        return $config;
    }
}