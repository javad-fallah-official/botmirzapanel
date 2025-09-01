<?php

declare(strict_types=1);

namespace BotMirzaPanel\Infrastructure\External\Telegram;

use BotMirzaPanel\Infrastructure\External\Telegram\TelegramServiceInterface;

/**
 * Telegram Service Implementation
 * 
 * Handles all Telegram Bot API operations
 */
class TelegramService implements TelegramServiceInterface
{
    private string $botToken;
    private string $apiUrl;
    private array $config;

    public function __construct(string $botToken, array $config = []): void
    {
        $this->botToken = $botToken;
        $this->apiUrl = 'https://api.telegram.org/bot' . $botToken;
        $this->config = $config;
    }

    public function sendMessage(int $chatId, string $text, array $options = []): array
    {
        $params = array_merge([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ], $options);

        return $this->makeRequest('sendMessage', $params);
    }

    public function sendPhoto(int $chatId, string $photo, string $caption = '', array $options = []): array
    {
        $params = array_merge([
            'chat_id' => $chatId,
            'photo' => $photo,
            'caption' => $caption,
            'parse_mode' => 'HTML'
        ], $options);

        return $this->makeRequest('sendPhoto', $params);
    }

    public function sendDocument(int $chatId, string $document, string $caption = '', array $options = []): array
    {
        $params = array_merge([
            'chat_id' => $chatId,
            'document' => $document,
            'caption' => $caption,
            'parse_mode' => 'HTML'
        ], $options);

        return $this->makeRequest('sendDocument', $params);
    }

    public function editMessage(int $chatId, int $messageId, string $text, array $options = []): array
    {
        $params = array_merge([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ], $options);

        return $this->makeRequest('editMessageText', $params);
    }

    public function deleteMessage(int $chatId, int $messageId): array
    {
        $params = [
            'chat_id' => $chatId,
            'message_id' => $messageId
        ];

        return $this->makeRequest('deleteMessage', $params);
    }

    public function sendInlineKeyboard(int $chatId, string $text, array $keyboard): array
    {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ];

        return $this->makeRequest('sendMessage', $params);
    }

    public function sendReplyKeyboard(int $chatId, string $text, array $keyboard): array
    {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode([
                'keyboard' => $keyboard,
                'resize_keyboard' => true,
                'one_time_keyboard' => false
            ])
        ];

        return $this->makeRequest('sendMessage', $params);
    }

    public function answerCallbackQuery(string $callbackQueryId, string $text = '', bool $showAlert = false): array
    {
        $params = [
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
            'show_alert' => $showAlert
        ];

        return $this->makeRequest('answerCallbackQuery', $params);
    }

    public function getMe(): array
    {
        return $this->makeRequest('getMe');
    }

    public function getUpdates(int $offset = 0, int $limit = 100): array
    {
        $params = [
            'offset' => $offset,
            'limit' => $limit,
            'timeout' => 30
        ];

        return $this->makeRequest('getUpdates', $params);
    }

    public function setWebhook(string $url, array $options = []): array
    {
        $params = array_merge([
            'url' => $url
        ], $options);

        return $this->makeRequest('setWebhook', $params);
    }

    public function deleteWebhook(): array
    {
        return $this->makeRequest('deleteWebhook');
    }

    public function getWebhookInfo(): array
    {
        return $this->makeRequest('getWebhookInfo');
    }

    public function getChat(int $chatId): array
    {
        $params = [
            'chat_id' => $chatId
        ];

        return $this->makeRequest('getChat', $params);
    }

    public function getChatMember(int $chatId, int $userId): array
    {
        $params = [
            'chat_id' => $chatId,
            'user_id' => $userId
        ];

        return $this->makeRequest('getChatMember', $params);
    }

    public function banChatMember(int $chatId, int $userId, int $untilDate = 0): array
    {
        $params = [
            'chat_id' => $chatId,
            'user_id' => $userId
        ];

        if ($untilDate > 0) {
            $params['until_date'] = $untilDate;
        }

        return $this->makeRequest('banChatMember', $params);
    }

    public function unbanChatMember(int $chatId, int $userId): array
    {
        $params = [
            'chat_id' => $chatId,
            'user_id' => $userId
        ];

        return $this->makeRequest('unbanChatMember', $params);
    }

    public function restrictChatMember(int $chatId, int $userId, array $permissions, int $untilDate = 0): array
    {
        $params = [
            'chat_id' => $chatId,
            'user_id' => $userId,
            'permissions' => json_encode($permissions)
        ];

        if ($untilDate > 0) {
            $params['until_date'] = $untilDate;
        }

        return $this->makeRequest('restrictChatMember', $params);
    }

    public function promoteChatMember(int $chatId, int $userId, array $permissions): array
    {
        $params = array_merge([
            'chat_id' => $chatId,
            'user_id' => $userId
        ], $permissions);

        return $this->makeRequest('promoteChatMember', $params);
    }

    public function setChatAdministratorCustomTitle(int $chatId, int $userId, string $customTitle): array
    {
        $params = [
            'chat_id' => $chatId,
            'user_id' => $userId,
            'custom_title' => $customTitle
        ];

        return $this->makeRequest('setChatAdministratorCustomTitle', $params);
    }

    public function exportChatInviteLink(int $chatId): array
    {
        $params = [
            'chat_id' => $chatId
        ];

        return $this->makeRequest('exportChatInviteLink', $params);
    }

    public function createChatInviteLink(int $chatId, array $options = []): array
    {
        $params = array_merge([
            'chat_id' => $chatId
        ], $options);

        return $this->makeRequest('createChatInviteLink', $params);
    }

    public function revokeChatInviteLink(int $chatId, string $inviteLink): array
    {
        $params = [
            'chat_id' => $chatId,
            'invite_link' => $inviteLink
        ];

        return $this->makeRequest('revokeChatInviteLink', $params);
    }

    public function sendChatAction(int $chatId, string $action): array
    {
        $params = [
            'chat_id' => $chatId,
            'action' => $action
        ];

        return $this->makeRequest('sendChatAction', $params);
    }

    public function getUserProfilePhotos(int $userId, int $offset = 0, int $limit = 100): array
    {
        $params = [
            'user_id' => $userId,
            'offset' => $offset,
            'limit' => $limit
        ];

        return $this->makeRequest('getUserProfilePhotos', $params);
    }

    public function getFile(string $fileId): array
    {
        $params = [
            'file_id' => $fileId
        ];

        return $this->makeRequest('getFile', $params);
    }

    public function downloadFile(string $filePath): string
    {
        $url = 'https://api.telegram.org/file/bot' . $this->botToken . '/' . $filePath;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new \Exception('Failed to download file');
        }
        
        return $response;
    }

    public function sendInvoice(int $chatId, array $invoiceData): array
    {
        $params = array_merge([
            'chat_id' => $chatId
        ], $invoiceData);

        return $this->makeRequest('sendInvoice', $params);
    }

    public function answerShippingQuery(string $shippingQueryId, bool $ok, array $options = []): array
    {
        $params = array_merge([
            'shipping_query_id' => $shippingQueryId,
            'ok' => $ok
        ], $options);

        return $this->makeRequest('answerShippingQuery', $params);
    }

    public function answerPreCheckoutQuery(string $preCheckoutQueryId, bool $ok, string $errorMessage = ''): array
    {
        $params = [
            'pre_checkout_query_id' => $preCheckoutQueryId,
            'ok' => $ok
        ];

        if (!$ok && !empty($errorMessage)) {
            $params['error_message'] = $errorMessage;
        }

        return $this->makeRequest('answerPreCheckoutQuery', $params);
    }

    public function setMyCommands(array $commands, array $scope = []): array
    {
        $params = [
            'commands' => json_encode($commands)
        ];

        if (!empty($scope)) {
            $params['scope'] = json_encode($scope);
        }

        return $this->makeRequest('setMyCommands', $params);
    }

    public function getMyCommands(array $scope = []): array
    {
        $params = [];

        if (!empty($scope)) {
            $params['scope'] = json_encode($scope);
        }

        return $this->makeRequest('getMyCommands', $params);
    }

    public function deleteMyCommands(array $scope = []): array
    {
        $params = [];

        if (!empty($scope)) {
            $params['scope'] = json_encode($scope);
        }

        return $this->makeRequest('deleteMyCommands', $params);
    }

    public function setChatMenuButton(int $chatId = 0, array $menuButton = []): array
    {
        $params = [];

        if ($chatId > 0) {
            $params['chat_id'] = $chatId;
        }

        if (!empty($menuButton)) {
            $params['menu_button'] = json_encode($menuButton);
        }

        return $this->makeRequest('setChatMenuButton', $params);
    }

    public function getChatMenuButton(int $chatId = 0): array
    {
        $params = [];

        if ($chatId > 0) {
            $params['chat_id'] = $chatId;
        }

        return $this->makeRequest('getChatMenuButton', $params);
    }

    public function setMyDefaultAdministratorRights(array $rights = [], bool $forChannels = false): array
    {
        $params = [];

        if (!empty($rights)) {
            $params['rights'] = json_encode($rights);
        }

        if ($forChannels) {
            $params['for_channels'] = true;
        }

        return $this->makeRequest('setMyDefaultAdministratorRights', $params);
    }

    public function getMyDefaultAdministratorRights(bool $forChannels = false): array
    {
        $params = [];

        if ($forChannels) {
            $params['for_channels'] = true;
        }

        return $this->makeRequest('getMyDefaultAdministratorRights', $params);
    }

    public function validateToken(): bool
    {
        try {
            $response = $this->getMe();
            return $response['ok'] ?? false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getConfiguration(): array
    {
        return $this->config;
    }

    public function setConfiguration(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Make HTTP request to Telegram Bot API
     */
    private function makeRequest(string $method, array $params = []): array
    {
        $url = $this->apiUrl . '/' . $method;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception('cURL error: ' . $error);
        }
        
        $decodedResponse = json_decode($response, true);
        
        if ($httpCode !== 200 || !($decodedResponse['ok'] ?? false)) {
            throw new \Exception(
                'Telegram API error: ' . ($decodedResponse['description'] ?? 'Unknown error')
            );
        }
        
        return $decodedResponse;
    }
}