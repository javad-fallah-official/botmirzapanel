<?php
// WARNING: SQL injection vulnerability detected in this file
// Please review and use prepared statements or secure_* functions


namespace BotMirzaPanel\Cron;

use BotMirzaPanel\Config\ConfigManager;
use BotMirzaPanel\Database\DatabaseManager;
use BotMirzaPanel\User\UserService;
use BotMirzaPanel\Panel\PanelService;
use BotMirzaPanel\Telegram\TelegramBot;

/**
 * Cron service for handling scheduled tasks
 */
class CronService
{
    private ConfigManager $config;
    private DatabaseManager $db;
    private UserService $userService;
    private PanelService $panelService;
    private TelegramBot $telegram;
    private array $jobs = [];

    public function __construct() {
        $this->config = $config;
        $this->db = $db;
        $this->userService = $userService;
        $this->panelService = $panelService;
        $this->telegram = $telegram;
        $this->registerJobs();
    }

    /**
     * Register all cron jobs
     */
    private function registerJobs(): void
    {
        $this->jobs = [
            'daily_tasks' => [
                'schedule' => '0 0 * * *', // Daily at midnight
                'callback' => [$this, 'runDailyTasks'],
                'description' => 'Daily maintenance tasks'
            ],
            'hourly_checks' => [
                'schedule' => '0 * * * *', // Every hour
                'callback' => [$this, 'runHourlyChecks'],
                'description' => 'Hourly system checks'
            ],
            'service_expiry_check' => [
                'schedule' => '*/30 * * * *', // Every 30 minutes
                'callback' => [$this, 'checkServiceExpiry'],
                'description' => 'Check for expiring services'
            ],
            'panel_sync' => [
                'schedule' => '*/15 * * * *', // Every 15 minutes
                'callback' => [$this, 'syncPanelUsers'],
                'description' => 'Sync users with panels'
            ],
            'send_notifications' => [
                'schedule' => '*/5 * * * *', // Every 5 minutes
                'callback' => [$this, 'sendPendingNotifications'],
                'description' => 'Send pending notifications'
            ],
            'cleanup_logs' => [
                'schedule' => '0 2 * * 0', // Weekly on Sunday at 2 AM
                'callback' => [$this, 'cleanupLogs'],
                'description' => 'Cleanup old logs and data'
            ]
        ];
    }

    /**
     * Run a specific cron job
     */
    public function runJob(string $jobName): array
    {
        if (!isset($this->jobs[$jobName])) {
            return ['success' => false, 'error' => 'Job not found'];
        }

        $job = $this->jobs[$jobName];
        $startTime = microtime(true);
        
        try {
            $this->logJobStart($jobName);
            
            $result = call_user_func($job['callback']);
            
            $duration = microtime(true) - $startTime;
            $this->logJobComplete($jobName, $duration, $result);
            
            return [
                'success' => true,
                'job' => $jobName,
                'duration' => $duration,
                'result' => $result
            ];
        } catch (\Exception $e) {
            $duration = microtime(true) - $startTime;
            $this->logJobError($jobName, $duration, $e->getMessage());
            
            return [
                'success' => false,
                'job' => $jobName,
                'duration' => $duration,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Run all jobs that are due
     */
    public function runDueJobs(): array
    {
        $results = [];
        
        foreach ($this->jobs as $jobName => $job) {
            if ($this->isJobDue($jobName)) {
                $results[$jobName] = $this->runJob($jobName);
            }
        }
        
        return $results;
    }

    /**
     * Check if a job is due to run
     */
    public function isJobDue(string $jobName): bool
    {
        if (!isset($this->jobs[$jobName])) {
            return false;
        }

        $schedule = $this->jobs[$jobName]['schedule'];
        $lastRun = $this->getLastJobRun($jobName);
        
        return $this->shouldRunBasedOnSchedule($schedule, $lastRun);
    }

    /**
     * Get list of all registered jobs
     */
    public function getJobs(): array
    {
        $jobList = [];
        
        foreach ($this->jobs as $name => $job) {
            $jobList[$name] = [
                'name' => $name,
                'schedule' => $job['schedule'],
                'description' => $job['description'],
                'last_run' => $this->getLastJobRun($name),
                'next_run' => $this->getNextJobRun($name),
                'is_due' => $this->isJobDue($name)
            ];
        }
        
        return $jobList;
    }

    /**
     * Daily maintenance tasks
     */
    public function runDailyTasks(): array
    {
        $results = [];
        
        // Clean expired sessions
        $results['clean_sessions'] = $this->cleanExpiredSessions();
        
        // Update user statistics
        $results['update_stats'] = $this->updateUserStatistics();
        
        // Check for expired services
        $results['expire_services'] = $this->expireServices();
        
        // Send daily reports to admins
        $results['daily_reports'] = $this->sendDailyReports();
        
        // Backup critical data
        $results['backup_data'] = $this->backupCriticalData();
        
        return $results;
    }

    /**
     * Hourly system checks
     */
    public function runHourlyChecks(): array
    {
        $results = [];
        
        // Check panel connectivity
        $results['panel_health'] = $this->checkPanelHealth();
        
        // Monitor system resources
        $results['system_health'] = $this->checkSystemHealth();
        
        // Update exchange rates
        $results['exchange_rates'] = $this->updateExchangeRates();
        
        return $results;
    }

    /**
     * Check for expiring services
     */
    public function checkServiceExpiry(): array
    {
        $results = [];
        
        // Get users with services expiring soon
        $expiringUsers = $this->getExpiringUsers();
        
        foreach ($expiringUsers as $user) {
            $daysLeft = $this->getDaysUntilExpiry($user['expire_date']);
            
            if ($daysLeft <= 3 && $daysLeft > 0) {
                // Send expiry warning
                $this->sendExpiryWarning($user, $daysLeft);
                $results['warnings_sent'][] = $user['user_id'];
            } elseif ($daysLeft <= 0) {
                // Expire the service
                $this->expireUserService($user);
                $results['services_expired'][] = $user['user_id'];
            }
        }
        
        return $results;
    }

    /**
     * Sync users with panels
     */
    public function syncPanelUsers(): array
    {
        $results = [];
        $panels = $this->panelService->getConfiguredPanels();
        
        foreach ($panels as $panel) {
            try {
                $syncResult = $this->panelService->syncPanelUsers($panel['id']);
                $results[$panel['name']] = $syncResult;
            } catch (\Exception $e) {
                $results[$panel['name']] = ['error' => $e->getMessage()];
            }
        }
        
        return $results;
    }

    /**
     * Send pending notifications
     */
    public function sendPendingNotifications(): array
    {
        $notifications = $this->getPendingNotifications();
        $sent = 0;
        $failed = 0;
        
        foreach ($notifications as $notification) {
            try {
                $this->sendNotification($notification);
                $this->markNotificationSent($notification['id']);
                $sent++;
            } catch (\Exception $e) {
                $this->markNotificationFailed($notification['id'], $e->getMessage());
                $failed++;
            }
        }
        
        return [
            'total' => count($notifications),
            'sent' => $sent,
            'failed' => $failed
        ];
    }

    /**
     * Cleanup old logs and data
     */
    public function cleanupLogs(): array
    {
        $results = [];
        
        // Clean old cron logs
        $results['cron_logs'] = $this->cleanOldCronLogs();
        
        // Clean old payment logs
        $results['payment_logs'] = $this->cleanOldPaymentLogs();
        
        // Clean old user activity logs
        $results['activity_logs'] = $this->cleanOldActivityLogs();
        
        // Clean temporary files
        $results['temp_files'] = $this->cleanTempFiles();
        
        return $results;
    }

    /**
     * Helper methods for cron tasks
     */
    
    private function cleanExpiredSessions(): int
    {
        $query = "DELETE FROM user_sessions WHERE expires_at < NOW()";
        $stmt = $this->db->execute($query);
        return method_exists($stmt, 'rowCount') ? $stmt->rowCount() : 0;
    }
    
    private function updateUserStatistics(): array
    {
        // Collect panel services for active users
        $services = $this->db->fetchAll(
            "SELECT us.user_id, us.username, us.panel_id
             FROM user_services us
             INNER JOIN users u ON u.id = us.user_id
             WHERE u.status = 'active'"
        );
        $updated = 0;
        
        foreach ($services as $svc) {
            try {
                $stats = $this->panelService->getUserStats($svc['panel_id'], $svc['username']);
                if ($stats) {
                    $this->userService->updateUserStats((int)$svc['user_id'], $stats);
                    $updated++;
                }
            } catch (\Exception $e) {
                // Log error but continue
                error_log("Failed to update stats for user {$svc['user_id']} on panel {$svc['panel_id']}: " . $e->getMessage());
            }
        }
        
        return ['updated' => $updated, 'total' => count($services)];
    }
    
    private function expireServices(): int
    {
        $expiredUsers = $this->db->fetchAll(
            "SELECT * FROM users WHERE expire_date <= NOW() AND status = 'active'"
        );
        
        $expired = 0;
        foreach ($expiredUsers as $user) {
            $this->expireUserService($user);
            $expired++;
        }
        
        return $expired;
    }
    
    private function sendDailyReports(): bool
    {
        $admins = $this->userService->getAdminUsers();
        $report = $this->generateDailyReport();
        
        foreach ($admins as $admin) {
            $this->telegram->sendMessage($admin['user_id'], $report);
        }
        
        return true;
    }
    
    private function backupCriticalData(): bool
    {
        // Implement backup logic
        return true;
    }
    
    private function checkPanelHealth(): array
    {
        $panels = $this->panelService->getConfiguredPanels();
        $health = [];
        
        foreach ($panels as $panel) {
            $health[$panel['name']] = $this->panelService->testPanelConnection($panel['id']);
        }
        
        return $health;
    }
    
    private function checkSystemHealth(): array
    {
        return [
            'disk_usage' => disk_free_space('/') / disk_total_space('/'),
            'memory_usage' => memory_get_usage(true),
            'load_average' => sys_getloadavg()
        ];
    }
    
    private function updateExchangeRates(): bool
    {
        // Implement exchange rate update logic
        return true;
    }
    
    private function getExpiringUsers(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM users WHERE expire_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY) AND status = 'active'"
        );
    }
    
    private function getDaysUntilExpiry(string $expireDate): int
    {
        $expire = new \DateTime($expireDate);
        $now = new \DateTime();
        return $expire->diff($now)->days;
    }
    
    private function sendExpiryWarning(array $user, int $daysLeft): void
    {
        $message = "⚠️ Your service will expire in {$daysLeft} day(s). Please renew to continue using the service.";
        $this->telegram->sendMessage($user['user_id'], $message);
    }
    
    private function expireUserService(array $user): void
    {
        // Disable user services in all configured panels for this user
        $services = $this->userService->getUserServices((int)$user['user_id']);
        foreach ($services as $service) {
            if (isset($service['panel_id'], $service['username'])) {
                $this->panelService->disableUser($service['panel_id'], $service['username']);
            }
        }
        
        // Update user status
        $this->userService->updateUserInfo((int)$user['user_id'], ['status' => 'expired']);
        
        // Send expiry notification
        $message = "❌ Your service has expired. Please contact support to renew.";
        $this->telegram->sendMessage($user['user_id'], $message);
    }
    
    private function getPendingNotifications(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM notifications WHERE status = 'pending' AND scheduled_at <= NOW() ORDER BY created_at ASC LIMIT 50"
        );
    }
    
    private function sendNotification(array $notification): void
    {
        $this->telegram->sendMessage($notification['user_id'], $notification['message']);
    }
    
    private function markNotificationSent(int $notificationId): void
    {
        $this->db->update('notifications', ['status' => 'sent', 'sent_at' => date('Y-m-d H:i:s')], ['id' => $notificationId]);
    }
    
    private function markNotificationFailed(int $notificationId, string $error): void
    {
        $this->db->update('notifications', [
            'status' => 'failed',
            'error_message' => $error,
            'failed_at' => date('Y-m-d H:i:s')
        ], ['id' => $notificationId]);
    }
    
    private function cleanOldCronLogs(): int
    {
        $stmt = $this->db->execute("DELETE FROM cron_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        return method_exists($stmt, 'rowCount') ? $stmt->rowCount() : 0;
    }
    
    private function cleanOldPaymentLogs(): int
    {
        $stmt = $this->db->execute("DELETE FROM payment_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
        return method_exists($stmt, 'rowCount') ? $stmt->rowCount() : 0;
    }
    
    private function cleanOldActivityLogs(): int
    {
        $stmt = $this->db->execute("DELETE FROM user_activity WHERE created_at < DATE_SUB(NOW(), INTERVAL 60 DAY)");
        return method_exists($stmt, 'rowCount') ? $stmt->rowCount() : 0;
    }
    
    private function cleanTempFiles(): int
    {
        $tempDir = sys_get_temp_dir();
        $files = glob($tempDir . '/botmirzapanel_*');
        $cleaned = 0;
        
        foreach ($files as $file) {
            if (filemtime($file) < time() - 86400) { // 24 hours old
                unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
    
    private function generateDailyReport(): string
    {
        $stats = $this->userService->getSystemStats();
        
        return "📊 Daily Report\n\n" .
               "👥 Total Users: {$stats['total_users']}\n" .
               "✅ Active Users: {$stats['active_users']}\n" .
               "💰 Today's Revenue: {$stats['daily_revenue']}\n" .
               "📈 New Registrations: {$stats['new_users_today']}\n" .
               "⚠️ Expiring Soon: {$stats['expiring_soon']}";
    }
    
    /**
     * Cron job management methods
     */
    
    private function logJobStart(string $jobName): void
    {
        $this->db->insert('cron_logs', [
            'job_name' => $jobName,
            'status' => 'running',
            'started_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    private function logJobComplete(string $jobName, float $duration, array $result): void
    {
        $this->db->update('cron_logs', [
            'status' => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
            'duration' => $duration,
            'result' => json_encode($result)
        ], [
            'job_name' => $jobName,
            'status' => 'running'
        ]);
    }
    
    private function logJobError(string $jobName, float $duration, string $error): void
    {
        $this->db->update('cron_logs', [
            'status' => 'failed',
            'completed_at' => date('Y-m-d H:i:s'),
            'duration' => $duration,
            'error_message' => $error
        ], [
            'job_name' => $jobName,
            'status' => 'running'
        ]);
    }
    
    private function getLastJobRun(string $jobName): ?string
    {
        $result = $this->db->fetchOne(
            "SELECT completed_at FROM cron_logs WHERE job_name = ? AND status IN ('completed', 'failed') ORDER BY completed_at DESC LIMIT 1",
            [$jobName]
        );
        
        return $result['completed_at'] ?? null;
    }
    
    private function getNextJobRun(string $jobName): ?string
    {
        if (!isset($this->jobs[$jobName])) {
            return null;
        }
        
        $schedule = $this->jobs[$jobName]['schedule'];
        $lastRun = $this->getLastJobRun($jobName);
        
        // Simple implementation - in production, use a proper cron parser
        return date('Y-m-d H:i:s', strtotime('+1 hour'));
    }
    
    private function shouldRunBasedOnSchedule(string $schedule, ?string $lastRun): bool
    {
        if (!$lastRun) {
            return true; // Never run before
        }
        
        // Simple implementation - in production, use a proper cron parser
        $lastRunTime = strtotime($lastRun);
        $now = time();
        
        // Parse basic schedule patterns
        if ($schedule === '*/5 * * * *') {
            return ($now - $lastRunTime) >= 300; // 5 minutes
        } elseif ($schedule === '*/15 * * * *') {
            return ($now - $lastRunTime) >= 900; // 15 minutes
        } elseif ($schedule === '*/30 * * * *') {
            return ($now - $lastRunTime) >= 1800; // 30 minutes
        } elseif ($schedule === '0 * * * *') {
            return ($now - $lastRunTime) >= 3600; // 1 hour
        } elseif ($schedule === '0 0 * * *') {
            return ($now - $lastRunTime) >= 86400; // 1 day
        } elseif ($schedule === '0 2 * * 0') {
            return ($now - $lastRunTime) >= 604800; // 1 week
        }
        
        return false;
    }

    /**
     * Run cron jobs. If a job name is provided, runs that job; otherwise runs all due jobs.
     */
    public function run(?string $jobName = null): array
    {
        return $jobName ? $this->runJob($jobName) : $this->runDueJobs();
    }
}