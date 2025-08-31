<?php

declare(strict_types=1);

namespace BotMirzaPanel\Infrastructure\External\Notifications;

/**
 * Notification Service Interface
 * 
 * Defines the contract for sending notifications through various channels
 */
interface NotificationServiceInterface
{
    /**
     * Send a notification
     */
    public function send(string $channel, string $recipient, string $subject, string $message, array $options = []): array;
    
    /**
     * Send an email notification
     */
    public function sendEmail(string $to, string $subject, string $message, array $options = []): array;
    
    /**
     * Send an SMS notification
     */
    public function sendSms(string $phoneNumber, string $message, array $options = []): array;
    
    /**
     * Send a Telegram notification
     */
    public function sendTelegram(int $chatId, string $message, array $options = []): array;
    
    /**
     * Send a push notification
     */
    public function sendPush(string $deviceToken, string $title, string $message, array $options = []): array;
    
    /**
     * Send a webhook notification
     */
    public function sendWebhook(string $url, array $data, array $options = []): array;
    
    /**
     * Send a Discord notification
     */
    public function sendDiscord(string $webhookUrl, string $message, array $options = []): array;
    
    /**
     * Send a Slack notification
     */
    public function sendSlack(string $webhookUrl, string $message, array $options = []): array;
    
    /**
     * Get supported notification channels
     */
    public function getSupportedChannels(): array;
    
    /**
     * Check if a channel is supported
     */
    public function supportsChannel(string $channel): bool;
    
    /**
     * Get channel configuration requirements
     */
    public function getChannelConfigurationFields(string $channel): array;
    
    /**
     * Validate channel configuration
     */
    public function validateChannelConfiguration(string $channel, array $config): bool;
    
    /**
     * Test notification channel
     */
    public function testChannel(string $channel, array $config): array;
    
    /**
     * Get notification history
     */
    public function getHistory(int $limit = 100, array $filters = []): array;
    
    /**
     * Get notification statistics
     */
    public function getStatistics(array $filters = []): array;
    
    /**
     * Schedule a notification
     */
    public function schedule(string $channel, string $recipient, string $subject, string $message, \DateTime $scheduledAt, array $options = []): array;
    
    /**
     * Cancel a scheduled notification
     */
    public function cancelScheduled(string $notificationId): array;
    
    /**
     * Get scheduled notifications
     */
    public function getScheduled(array $filters = []): array;
    
    /**
     * Send bulk notifications
     */
    public function sendBulk(string $channel, array $recipients, string $subject, string $message, array $options = []): array;
    
    /**
     * Create notification template
     */
    public function createTemplate(string $name, string $subject, string $content, array $variables = []): array;
    
    /**
     * Get notification template
     */
    public function getTemplate(string $name): array;
    
    /**
     * Update notification template
     */
    public function updateTemplate(string $name, array $data): array;
    
    /**
     * Delete notification template
     */
    public function deleteTemplate(string $name): array;
    
    /**
     * Send notification using template
     */
    public function sendFromTemplate(string $channel, string $recipient, string $templateName, array $variables = [], array $options = []): array;
    
    /**
     * Set notification preferences for a user
     */
    public function setUserPreferences(int $userId, array $preferences): array;
    
    /**
     * Get notification preferences for a user
     */
    public function getUserPreferences(int $userId): array;
    
    /**
     * Check if user allows notifications for a specific channel
     */
    public function userAllowsChannel(int $userId, string $channel): bool;
}