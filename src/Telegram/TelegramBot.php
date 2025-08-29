<?php

namespace BotMirzaPanel\Telegram;

use BotMirzaPanel\Config\ConfigManager;
use BotMirzaPanel\Telegram\Handlers\MessageHandler;
use BotMirzaPanel\Telegram\Handlers\CallbackHandler;
use BotMirzaPanel\Telegram\Handlers\CommandHandler;

/**
 * Telegram Bot API wrapper with clean interfaces
 * Handles all Telegram operations and webhook processing
 */
class TelegramBot
{
    private ConfigManager $config;
    private string $apiKey;
    private string $apiUrl;
    private MessageHandler $messageHandler;
    private CallbackHandler $callbackHandler;
    private CommandHandler $commandHandler;

    public function __construct(ConfigManager $config)
    {
        $this->config = $config;
        $this->apiKey = $config->get('telegram.api_key');
        $this->apiUrl = "https://api.telegram.org/bot{$this->apiKey}/";
        
        $this->initializeHandlers();
    }

    /**
     * Initialize message handlers
     */
    private function initializeHandlers(): void
    {
        $this->messageHandler = new MessageHandler($this);
        $this->callbackHandler = new CallbackHandler($this);
        $this->commandHandler = new CommandHandler($this);
    }

    /**
     * Process incoming webhook update
     */
    public function processUpdate(array $update, array $services): void
    {
        if (isset($update['message'])) {
            $this->handleMessage($update['message'], $services);
        } elseif (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query'], $services);
        }
    }

    /**
     * Handle incoming message
     */
    private function handleMessage(array $message, array $services): void
    {
        // Check for commands
        if (isset($message['text']) && strpos($message['text'], '/') === 0) {
            $this->commandHandler->handle($message, $services);
        } else {
            $this->messageHandler->handle($message, $services);
        }
    }

    /**
     * Handle callback query
     */
    private function handleCallbackQuery(array $callbackQuery, array $services): void
    {
        $this->callbackHandler->handle($callbackQuery, $services);
    }

    /**
     * Send message to user
     */
    public function sendMessage(
        int $chatId,
        string $text,
        array $keyboard = null,
        string $parseMode = 'HTML',
        bool $disablePreview = true
    ): array {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
            'disable_web_page_preview' => $disablePreview
        ];

        if ($keyboard) {
            $params['reply_markup'] = is_string($keyboard) ? $keyboard : json_encode($keyboard);
        }

        return $this->makeRequest('sendMessage', $params);
    }

    /**
     * Edit message text
     */
    public function editMessageText(
        int $chatId,
        int $messageId,
        string $text,
        array $keyboard = null,
        string $parseMode = 'HTML'
    ): array {
        $params = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => $parseMode
        ];

        if ($keyboard) {
            $params['reply_markup'] = is_string($keyboard) ? $keyboard : json_encode($keyboard);
        }

        return $this->makeRequest('editMessageText', $params);
    }

    /**
     * Delete message
     */
    public function deleteMessage(int $chatId, int $messageId): array
    {
        return $this->makeRequest('deleteMessage', [
            'chat_id' => $chatId,
            'message_id' => $messageId
        ]);
    }

    /**
     * Send photo
     */
    public function sendPhoto(
        int $chatId,
        string $photo,
        string $caption = null,
        array $keyboard = null
    ): array {
        $params = [
            'chat_id' => $chatId,
            'photo' => $photo
        ];

        if ($caption) {
            $params['caption'] = $caption;
        }

        if ($keyboard) {
            $params['reply_markup'] = is_string($keyboard) ? $keyboard : json_encode($keyboard);
        }

        return $this->makeRequest('sendPhoto', $params);
    }

    /**
     * Send document
     */
    public function sendDocument(
        int $chatId,
        string $document,
        string $caption = null,
        array $keyboard = null
    ): array {
        $params = [
            'chat_id' => $chatId,
            'document' => $document
        ];

        if ($caption) {
            $params['caption'] = $caption;
        }

        if ($keyboard) {
            $params['reply_markup'] = is_string($keyboard) ? $keyboard : json_encode($keyboard);
        }

        return $this->makeRequest('sendDocument', $params);
    }

    /**
     * Send video
     */
    public function sendVideo(
        int $chatId,
        string $video,
        string $caption = null,
        array $keyboard = null
    ): array {
        $params = [
            'chat_id' => $chatId,
            'video' => $video
        ];

        if ($caption) {
            $params['caption'] = $caption;
        }

        if ($keyboard) {
            $params['reply_markup'] = is_string($keyboard) ? $keyboard : json_encode($keyboard);
        }

        return $this->makeRequest('sendVideo', $params);
    }

    /**
     * Forward message
     */
    public function forwardMessage(int $chatId, int $fromChatId, int $messageId): array
    {
        return $this->makeRequest('forwardMessage', [
            'chat_id' => $chatId,
            'from_chat_id' => $fromChatId,
            'message_id' => $messageId
        ]);
    }

    /**
     * Answer callback query
     */
    public function answerCallbackQuery(
        string $callbackQueryId,
        string $text = null,
        bool $showAlert = false,
        int $cacheTime = 0
    ): array {
        $params = [
            'callback_query_id' => $callbackQueryId,
            'show_alert' => $showAlert,
            'cache_time' => $cacheTime
        ];

        if ($text) {
            $params['text'] = $text;
        }

        return $this->makeRequest('answerCallbackQuery', $params);
    }

    /**
     * Get chat member
     */
    public function getChatMember(int $chatId, int $userId): array
    {
        return $this->makeRequest('getChatMember', [
            'chat_id' => $chatId,
            'user_id' => $userId
        ]);
    }

    /**
     * Set webhook
     */
    public function setWebhook(string $url, array $allowedUpdates = []): array
    {
        $params = ['url' => $url];
        
        if (!empty($allowedUpdates)) {
            $params['allowed_updates'] = $allowedUpdates;
        }

        return $this->makeRequest('setWebhook', $params);
    }

    /**
     * Delete webhook
     */
    public function deleteWebhook(): array
    {
        return $this->makeRequest('deleteWebhook');
    }

    /**
     * Get webhook info
     */
    public function getWebhookInfo(): array
    {
        return $this->makeRequest('getWebhookInfo');
    }

    /**
     * Make HTTP request to Telegram API
     */
    private function makeRequest(string $method, array $params = []): array
    {
        $url = $this->apiUrl . $method;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception("cURL error: {$error}");
        }
        
        if ($httpCode !== 200) {
            throw new \Exception("HTTP error: {$httpCode}");
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("JSON decode error: " . json_last_error_msg());
        }
        
        if (!$decoded['ok']) {
            throw new \Exception("Telegram API error: " . ($decoded['description'] ?? 'Unknown error'));
        }
        
        return $decoded;
    }

    /**
     * Get configuration manager
     */
    public function getConfig(): ConfigManager
    {
        return $this->config;
    }
}