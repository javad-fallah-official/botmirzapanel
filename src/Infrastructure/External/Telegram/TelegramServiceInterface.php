<?php
// WARNING: SQL injection vulnerability detected in this file
// Please review and use prepared statements or secure_* functions


declare(strict_types=1);

namespace BotMirzaPanel\Infrastructure\External\Telegram;

/**
 * Telegram Service Interface
 * 
 * Defines the contract for Telegram bot operations
 */
interface TelegramServiceInterface
{
    /**
     * Send a text message
     */
    public function sendMessage(int $chatId, string $text, array $options = []): array;
    
    /**
     * Send a photo
     */
    public function sendPhoto(int $chatId, string $photo, string $caption = '', array $options = []): array;
    
    /**
     * Send a document
     */
    public function sendDocument(int $chatId, string $document, string $caption = '', array $options = []): array;
    
    /**
     * Edit a message
     */
    public function editMessage(int $chatId, int $messageId, string $text, array $options = []): array;
    
    /**
     * Delete a message
     */
    public function deleteMessage(int $chatId, int $messageId): array;
    
    /**
     * Send an inline keyboard
     */
    public function sendInlineKeyboard(int $chatId, string $text, array $keyboard): array;
    
    /**
     * Send a reply keyboard
     */
    public function sendReplyKeyboard(int $chatId, string $text, array $keyboard): array;
    
    /**
     * Answer callback query
     */
    public function answerCallbackQuery(string $callbackQueryId, string $text = '', bool $showAlert = false): array;
    
    /**
     * Get bot information
     */
    public function getMe(): array;
    
    /**
     * Get updates
     */
    public function getUpdates(int $offset = 0, int $limit = 100): array;
    
    /**
     * Set webhook
     */
    public function setWebhook(string $url, array $options = []): array;
    
    /**
     * Delete webhook
     */
    public function deleteWebhook(): array;
    
    /**
     * Get webhook info
     */
    public function getWebhookInfo(): array;
    
    /**
     * Get chat information
     */
    public function getChat(int $chatId): array;
    
    /**
     * Get chat member information
     */
    public function getChatMember(int $chatId, int $userId): array;
    
    /**
     * Ban chat member
     */
    public function banChatMember(int $chatId, int $userId, int $untilDate = 0): array;
    
    /**
     * Unban chat member
     */
    public function unbanChatMember(int $chatId, int $userId): array;
    
    /**
     * Restrict chat member
     */
    public function restrictChatMember(int $chatId, int $userId, array $permissions, int $untilDate = 0): array;
    
    /**
     * Promote chat member
     */
    public function promoteChatMember(int $chatId, int $userId, array $permissions): array;
    
    /**
     * Set chat administrator custom title
     */
    public function setChatAdministratorCustomTitle(int $chatId, int $userId, string $customTitle): array;
    
    /**
     * Export chat invite link
     */
    public function exportChatInviteLink(int $chatId): array;
    
    /**
     * Create chat invite link
     */
    public function createChatInviteLink(int $chatId, array $options = []): array;
    
    /**
     * Revoke chat invite link
     */
    public function revokeChatInviteLink(int $chatId, string $inviteLink): array;
    
    /**
     * Send chat action (typing, upload_photo, etc.)
     */
    public function sendChatAction(int $chatId, string $action): array;
    
    /**
     * Get user profile photos
     */
    public function getUserProfilePhotos(int $userId, int $offset = 0, int $limit = 100): array;
    
    /**
     * Get file information
     */
    public function getFile(string $fileId): array;
    
    /**
     * Download file
     */
    public function downloadFile(string $filePath): string;
    
    /**
     * Send invoice
     */
    public function sendInvoice(int $chatId, array $invoiceData): array;
    
    /**
     * Answer shipping query
     */
    public function answerShippingQuery(string $shippingQueryId, bool $ok, array $options = []): array;
    
    /**
     * Answer pre-checkout query
     */
    public function answerPreCheckoutQuery(string $preCheckoutQueryId, bool $ok, string $errorMessage = ''): array;
    
    /**
     * Set bot commands
     */
    public function setMyCommands(array $commands, array $scope = []): array;
    
    /**
     * Get bot commands
     */
    public function getMyCommands(array $scope = []): array;
    
    /**
     * Delete bot commands
     */
    public function deleteMyCommands(array $scope = []): array;
    
    /**
     * Set chat menu button
     */
    public function setChatMenuButton(int $chatId = 0, array $menuButton = []): array;
    
    /**
     * Get chat menu button
     */
    public function getChatMenuButton(int $chatId = 0): array;
    
    /**
     * Set default administrator rights
     */
    public function setMyDefaultAdministratorRights(array $rights = [], bool $forChannels = false): array;
    
    /**
     * Get default administrator rights
     */
    public function getMyDefaultAdministratorRights(bool $forChannels = false): array;
    
    /**
     * Check if the bot token is valid
     */
    public function validateToken(): bool;
    
    /**
     * Get bot configuration
     */
    public function getConfiguration(): array;
    
    /**
     * Set bot configuration
     */
    public function setConfiguration(array $config): void;
}