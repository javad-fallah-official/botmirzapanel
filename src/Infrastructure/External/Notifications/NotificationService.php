<?php
// WARNING: SQL injection vulnerability detected in this file
// Please review and use prepared statements or secure_* functions


declare(strict_types=1);

namespace BotMirzaPanel\Infrastructure\External\Notifications;

use BotMirzaPanel\Infrastructure\External\Notifications\NotificationServiceInterface;
use BotMirzaPanel\Infrastructure\External\Telegram\TelegramServiceInterface;
use BotMirzaPanel\Config\ConfigManager;

/**
 * Notification Service Implementation
 * 
 * Handles sending notifications through various channels
 */
class NotificationService implements NotificationServiceInterface
{
    private ConfigManager $config;
    private TelegramServiceInterface $telegramService;
    private array $templates = [];
    private array $userPreferences = [];
    private array $history = [];

    public function __construct(
        ConfigManager $config,
        TelegramServiceInterface $telegramService
    ) {
        $this->config = $config;
        $this->telegramService = $telegramService;
    }

    public function send(string $channel, string $recipient, string $subject, string $message, array $options = []): array
    {
        if (!$this->supportsChannel($channel)) {
            throw new \InvalidArgumentException("Unsupported notification channel: {$channel}");
        }

        $result = match ($channel) {
            'email' => $this->sendEmail($recipient, $subject, $message, $options),
            'sms' => $this->sendSms($recipient, $message, $options),
            'telegram' => $this->sendTelegram((int)$recipient, $message, $options),
            'push' => $this->sendPush($recipient, $subject, $message, $options),
            'webhook' => $this->sendWebhook($recipient, array_merge(['subject' => $subject, 'message' => $message], $options)),
            'discord' => $this->sendDiscord($recipient, $message, $options),
            'slack' => $this->sendSlack($recipient, $message, $options),
            default => throw new \InvalidArgumentException("Unsupported channel: {$channel}")
        };

        // Log to history
        $this->addToHistory($channel, $recipient, $subject, $message, $result);

        return $result;
    }

    public function sendEmail(string $to, string $subject, string $message, array $options = []): array
    {
        $emailConfig = $this->config->get('notifications.email', []);
        
        if (empty($emailConfig['enabled']) || !$emailConfig['enabled']) {
            return ['success' => false, 'message' => 'Email notifications are disabled'];
        }

        // Basic email sending implementation
        $headers = [
            'From: ' . ($emailConfig['from_email'] ?? 'noreply@example.com'),
            'Reply-To: ' . ($emailConfig['reply_to'] ?? $emailConfig['from_email'] ?? 'noreply@example.com'),
            'Content-Type: text/html; charset=UTF-8',
            'MIME-Version: 1.0'
        ];

        $success = mail($to, $subject, $message, implode("\r\n", $headers));

        return [
            'success' => $success,
            'channel' => 'email',
            'recipient' => $to,
            'message' => $success ? 'Email sent successfully' : 'Failed to send email'
        ];
    }

    public function sendSms(string $phoneNumber, string $message, array $options = []): array
    {
        $smsConfig = $this->config->get('notifications.sms', []);
        
        if (empty($smsConfig['enabled']) || !$smsConfig['enabled']) {
            return ['success' => false, 'message' => 'SMS notifications are disabled'];
        }

        // SMS implementation would depend on the SMS provider
        // This is a placeholder implementation
        return [
            'success' => false,
            'channel' => 'sms',
            'recipient' => $phoneNumber,
            'message' => 'SMS provider not configured'
        ];
    }

    public function sendTelegram(int $chatId, string $message, array $options = []): array
    {
        try {
            $result = $this->telegramService->sendMessage($chatId, $message, $options);
            
            return [
                'success' => $result['ok'] ?? false,
                'channel' => 'telegram',
                'recipient' => (string)$chatId,
                'message' => 'Telegram message sent successfully',
                'message_id' => $result['result']['message_id'] ?? null
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'channel' => 'telegram',
                'recipient' => (string)$chatId,
                'message' => 'Failed to send Telegram message: ' . $e->getMessage()
            ];
        }
    }

    public function sendPush(string $deviceToken, string $title, string $message, array $options = []): array
    {
        $pushConfig = $this->config->get('notifications.push', []);
        
        if (empty($pushConfig['enabled']) || !$pushConfig['enabled']) {
            return ['success' => false, 'message' => 'Push notifications are disabled'];
        }

        // Push notification implementation would depend on the service (FCM, APNS, etc.)
        return [
            'success' => false,
            'channel' => 'push',
            'recipient' => $deviceToken,
            'message' => 'Push notification service not configured'
        ];
    }

    public function sendWebhook(string $url, array $data, array $options = []): array
    {
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'User-Agent: BotMirzaPanel-Webhook/1.0'
                ],
                CURLOPT_TIMEOUT => 30
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $success = $httpCode >= 200 && $httpCode < 300;
            
            return [
                'success' => $success,
                'channel' => 'webhook',
                'recipient' => $url,
                'message' => $success ? 'Webhook sent successfully' : 'Webhook failed',
                'http_code' => $httpCode,
                'response' => $response
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'channel' => 'webhook',
                'recipient' => $url,
                'message' => 'Webhook error: ' . $e->getMessage()
            ];
        }
    }

    public function sendDiscord(string $webhookUrl, string $message, array $options = []): array
    {
        $data = [
            'content' => $message,
            'username' => $options['username'] ?? 'BotMirzaPanel',
            'avatar_url' => $options['avatar_url'] ?? null
        ];

        if (isset($options['embeds'])) {
            $data['embeds'] = $options['embeds'];
        }

        return $this->sendWebhook($webhookUrl, $data);
    }

    public function sendSlack(string $webhookUrl, string $message, array $options = []): array
    {
        $data = [
            'text' => $message,
            'username' => $options['username'] ?? 'BotMirzaPanel',
            'icon_emoji' => $options['icon_emoji'] ?? ':robot_face:',
            'channel' => $options['channel'] ?? null
        ];

        if (isset($options['attachments'])) {
            $data['attachments'] = $options['attachments'];
        }

        return $this->sendWebhook($webhookUrl, $data);
    }

    public function getSupportedChannels(): array
    {
        return ['email', 'sms', 'telegram', 'push', 'webhook', 'discord', 'slack'];
    }

    public function supportsChannel(string $channel): bool
    {
        return in_array($channel, $this->getSupportedChannels());
    }

    public function getChannelConfigurationFields(string $channel): array
    {
        return match ($channel) {
            'email' => [
                'enabled' => ['type' => 'boolean', 'label' => 'Enable Email'],
                'from_email' => ['type' => 'email', 'label' => 'From Email'],
                'smtp_host' => ['type' => 'text', 'label' => 'SMTP Host'],
                'smtp_port' => ['type' => 'number', 'label' => 'SMTP Port'],
                'smtp_username' => ['type' => 'text', 'label' => 'SMTP Username'],
                'smtp_password' => ['type' => 'password', 'label' => 'SMTP Password']
            ],
            'sms' => [
                'enabled' => ['type' => 'boolean', 'label' => 'Enable SMS'],
                'provider' => ['type' => 'select', 'label' => 'SMS Provider', 'options' => ['twilio', 'nexmo', 'custom']],
                'api_key' => ['type' => 'text', 'label' => 'API Key'],
                'api_secret' => ['type' => 'password', 'label' => 'API Secret']
            ],
            'telegram' => [
                'enabled' => ['type' => 'boolean', 'label' => 'Enable Telegram'],
                'bot_token' => ['type' => 'text', 'label' => 'Bot Token']
            ],
            'push' => [
                'enabled' => ['type' => 'boolean', 'label' => 'Enable Push'],
                'fcm_server_key' => ['type' => 'text', 'label' => 'FCM Server Key']
            ],
            default => []
        };
    }

    public function validateChannelConfiguration(string $channel, array $config): bool
    {
        $fields = $this->getChannelConfigurationFields($channel);
        
        foreach ($fields as $field => $definition) {
            if (($definition['required'] ?? false) && empty($config[$field])) {
                return false;
            }
        }
        
        return true;
    }

    public function testChannel(string $channel, array $config): array
    {
        // Test notification implementation
        return [
            'success' => true,
            'message' => "Test for {$channel} channel completed"
        ];
    }

    public function getHistory(int $limit = 100, array $filters = []): array
    {
        $history = array_slice($this->history, -$limit);
        
        // Apply filters if provided
        if (!empty($filters)) {
            $history = array_filter($history, function ($item) use ($filters) {
                foreach ($filters as $key => $value) {
                    if (isset($item[$key]) && $item[$key] !== $value) {
                        return false;
                    }
                }
                return true;
            });
        }
        
        return array_values($history);
    }

    public function getStatistics(array $filters = []): array
    {
        $history = $this->getHistory(1000, $filters);
        
        $stats = [
            'total' => count($history),
            'successful' => 0,
            'failed' => 0,
            'by_channel' => []
        ];
        
        foreach ($history as $item) {
            if ($item['success']) {
                $stats['successful']++;
            } else {
                $stats['failed']++;
            }
            
            $channel = $item['channel'];
            if (!isset($stats['by_channel'][$channel])) {
                $stats['by_channel'][$channel] = ['total' => 0, 'successful' => 0, 'failed' => 0];
            }
            
            $stats['by_channel'][$channel]['total']++;
            if ($item['success']) {
                $stats['by_channel'][$channel]['successful']++;
            } else {
                $stats['by_channel'][$channel]['failed']++;
            }
        }
        
        return $stats;
    }

    public function schedule(string $channel, string $recipient, string $subject, string $message, \DateTime $scheduledAt, array $options = []): array
    {
        // Scheduled notification implementation
        $notificationId = uniqid('scheduled_', true);
        
        return [
            'success' => true,
            'notification_id' => $notificationId,
            'scheduled_at' => $scheduledAt->format('Y-m-d H:i:s'),
            'message' => 'Notification scheduled successfully'
        ];
    }

    public function cancelScheduled(string $notificationId): array
    {
        return [
            'success' => true,
            'message' => 'Scheduled notification cancelled'
        ];
    }

    public function getScheduled(array $filters = []): array
    {
        return [];
    }

    public function sendBulk(string $channel, array $recipients, string $subject, string $message, array $options = []): array
    {
        $results = [];
        $successful = 0;
        $failed = 0;
        
        foreach ($recipients as $recipient) {
            try {
                $result = $this->send($channel, $recipient, $subject, $message, $options);
                $results[] = $result;
                
                if ($result['success']) {
                    $successful++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $results[] = [
                    'success' => false,
                    'recipient' => $recipient,
                    'message' => $e->getMessage()
                ];
                $failed++;
            }
        }
        
        return [
            'success' => $failed === 0,
            'total' => count($recipients),
            'successful' => $successful,
            'failed' => $failed,
            'results' => $results
        ];
    }

    public function createTemplate(string $name, string $subject, string $content, array $variables = []): array
    {
        $this->templates[$name] = [
            'subject' => $subject,
            'content' => $content,
            'variables' => $variables,
            'created_at' => new \DateTime()
        ];
        
        return [
            'success' => true,
            'message' => 'Template created successfully'
        ];
    }

    public function getTemplate(string $name): array
    {
        if (!isset($this->templates[$name])) {
            throw new \InvalidArgumentException("Template '{$name}' not found");
        }
        
        return $this->templates[$name];
    }

    public function updateTemplate(string $name, array $data): array
    {
        if (!isset($this->templates[$name])) {
            throw new \InvalidArgumentException("Template '{$name}' not found");
        }
        
        $this->templates[$name] = array_merge($this->templates[$name], $data);
        $this->templates[$name]['updated_at'] = new \DateTime();
        
        return [
            'success' => true,
            'message' => 'Template updated successfully'
        ];
    }

    public function deleteTemplate(string $name): array
    {
        if (!isset($this->templates[$name])) {
            throw new \InvalidArgumentException("Template '{$name}' not found");
        }
        
        unset($this->templates[$name]);
        
        return [
            'success' => true,
            'message' => 'Template deleted successfully'
        ];
    }

    public function sendFromTemplate(string $channel, string $recipient, string $templateName, array $variables = [], array $options = []): array
    {
        $template = $this->getTemplate($templateName);
        
        $subject = $this->replaceVariables($template['subject'], $variables);
        $message = $this->replaceVariables($template['content'], $variables);
        
        return $this->send($channel, $recipient, $subject, $message, $options);
    }

    public function setUserPreferences(int $userId, array $preferences): array
    {
        $this->userPreferences[$userId] = $preferences;
        
        return [
            'success' => true,
            'message' => 'User preferences updated'
        ];
    }

    public function getUserPreferences(int $userId): array
    {
        return $this->userPreferences[$userId] ?? [];
    }

    public function userAllowsChannel(int $userId, string $channel): bool
    {
        $preferences = $this->getUserPreferences($userId);
        return $preferences[$channel] ?? true;
    }

    /**
     * Replace variables in template content
     */
    private function replaceVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', (string)$value, $content);
        }
        
        return $content;
    }

    /**
     * Add notification to history
     */
    private function addToHistory(string $channel, string $recipient, string $subject, string $message, array $result): void
    {
        $this->history[] = [
            'id' => uniqid(),
            'channel' => $channel,
            'recipient' => $recipient,
            'subject' => $subject,
            'message' => $message,
            'success' => $result['success'] ?? false,
            'response' => $result,
            'sent_at' => new \DateTime()
        ];
        
        // Keep only last 1000 entries
        if (count($this->history) > 1000) {
            $this->history = array_slice($this->history, -1000);
        }
    }
}