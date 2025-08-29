<?php

namespace BotMirzaPanel\Telegram\Handlers;

use BotMirzaPanel\Telegram\TelegramBot;

/**
 * Handles callback queries from inline keyboards
 * Processes button clicks and user interactions
 */
class CallbackHandler
{
    private TelegramBot $bot;

    public function __construct(TelegramBot $bot)
    {
        $this->bot = $bot;
    }

    /**
     * Handle incoming callback query
     */
    public function handle(array $callbackQuery, array $services): void
    {
        $userId = $callbackQuery['from']['id'];
        $chatId = $callbackQuery['message']['chat']['id'];
        $messageId = $callbackQuery['message']['message_id'];
        $data = $callbackQuery['data'] ?? '';
        $callbackId = $callbackQuery['id'];
        
        /** @var \BotMirzaPanel\User\UserService $userService */
        $userService = $services['user'];
        
        // Check if user is blocked
        if ($userService->isUserBlocked($userId)) {
            $this->bot->answerCallbackQuery($callbackId, "Access denied.");
            return;
        }
        
        // Parse callback data
        $callbackData = $this->parseCallbackData($data);
        
        // Route to appropriate handler
        switch ($callbackData['action']) {
            case 'payment':
                $this->handlePaymentCallback($callbackQuery, $callbackData, $services);
                break;
                
            case 'service':
                $this->handleServiceCallback($callbackQuery, $callbackData, $services);
                break;
                
            case 'admin':
                $this->handleAdminCallback($callbackQuery, $callbackData, $services);
                break;
                
            case 'panel':
                $this->handlePanelCallback($callbackQuery, $callbackData, $services);
                break;
                
            case 'user_management':
                $this->handleUserManagementCallback($callbackQuery, $callbackData, $services);
                break;
                
            case 'settings':
                $this->handleSettingsCallback($callbackQuery, $callbackData, $services);
                break;
                
            case 'navigation':
                $this->handleNavigationCallback($callbackQuery, $callbackData, $services);
                break;
                
            default:
                $this->handleUnknownCallback($callbackQuery, $callbackData, $services);
                break;
        }
    }

    /**
     * Handle payment-related callbacks
     */
    private function handlePaymentCallback(array $callbackQuery, array $data, array $services): void
    {
        $userId = $callbackQuery['from']['id'];
        $callbackId = $callbackQuery['id'];
        
        /** @var \BotMirzaPanel\Payment\PaymentService $paymentService */
        $paymentService = $services['payment'];
        
        switch ($data['subaction']) {
            case 'select_gateway':
                $gateway = $data['params']['gateway'] ?? '';
                $this->selectPaymentGateway($callbackQuery, $gateway, $services);
                break;
                
            case 'confirm_payment':
                $paymentId = $data['params']['payment_id'] ?? '';
                $this->confirmPayment($callbackQuery, $paymentId, $services);
                break;
                
            case 'cancel_payment':
                $paymentId = $data['params']['payment_id'] ?? '';
                $this->cancelPayment($callbackQuery, $paymentId, $services);
                break;
                
            case 'add_balance':
                $this->initiateBalanceAddition($callbackQuery, $services);
                break;
                
            case 'view_transactions':
                $this->showTransactionHistory($callbackQuery, $services);
                break;
                
            default:
                $this->bot->answerCallbackQuery($callbackId, "Unknown payment action.");
                break;
        }
    }

    /**
     * Handle service-related callbacks
     */
    private function handleServiceCallback(array $callbackQuery, array $data, array $services): void
    {
        $userId = $callbackQuery['from']['id'];
        $callbackId = $callbackQuery['id'];
        
        switch ($data['subaction']) {
            case 'buy':
                $serviceId = $data['params']['service_id'] ?? '';
                $this->initiateServicePurchase($callbackQuery, $serviceId, $services);
                break;
                
            case 'extend':
                $userServiceId = $data['params']['user_service_id'] ?? '';
                $this->initiateServiceExtension($callbackQuery, $userServiceId, $services);
                break;
                
            case 'view_config':
                $userServiceId = $data['params']['user_service_id'] ?? '';
                $this->showServiceConfig($callbackQuery, $userServiceId, $services);
                break;
                
            case 'test_service':
                $serviceId = $data['params']['service_id'] ?? '';
                $this->initiateTestService($callbackQuery, $serviceId, $services);
                break;
                
            case 'category':
                $categoryId = $data['params']['category_id'] ?? '';
                $this->showServiceCategory($callbackQuery, $categoryId, $services);
                break;
                
            default:
                $this->bot->answerCallbackQuery($callbackId, "Unknown service action.");
                break;
        }
    }

    /**
     * Handle admin-related callbacks
     */
    private function handleAdminCallback(array $callbackQuery, array $data, array $services): void
    {
        $userId = $callbackQuery['from']['id'];
        $callbackId = $callbackQuery['id'];
        
        /** @var \BotMirzaPanel\Config\ConfigManager $config */
        $config = $services['config'];
        
        // Check if user is admin
        if ($userId != $config->get('admin.id')) {
            $this->bot->answerCallbackQuery($callbackId, "Access denied.");
            return;
        }
        
        switch ($data['subaction']) {
            case 'user_stats':
                $this->showUserStatistics($callbackQuery, $services);
                break;
                
            case 'system_stats':
                $this->showSystemStatistics($callbackQuery, $services);
                break;
                
            case 'broadcast':
                $this->initiateBroadcastMessage($callbackQuery, $services);
                break;
                
            case 'manage_services':
                $this->showServiceManagement($callbackQuery, $services);
                break;
                
            case 'financial_report':
                $this->showFinancialReport($callbackQuery, $services);
                break;
                
            default:
                $this->bot->answerCallbackQuery($callbackId, "Unknown admin action.");
                break;
        }
    }

    /**
     * Handle panel-related callbacks
     */
    private function handlePanelCallback(array $callbackQuery, array $data, array $services): void
    {
        $userId = $callbackQuery['from']['id'];
        $callbackId = $callbackQuery['id'];
        
        /** @var \BotMirzaPanel\Panel\PanelService $panelService */
        $panelService = $services['panel'];
        
        switch ($data['subaction']) {
            case 'select_panel':
                $panelType = $data['params']['panel_type'] ?? '';
                $this->selectPanel($callbackQuery, $panelType, $services);
                break;
                
            case 'test_connection':
                $panelId = $data['params']['panel_id'] ?? '';
                $this->testPanelConnection($callbackQuery, $panelId, $services);
                break;
                
            case 'sync_users':
                $panelId = $data['params']['panel_id'] ?? '';
                $this->syncPanelUsers($callbackQuery, $panelId, $services);
                break;
                
            default:
                $this->bot->answerCallbackQuery($callbackId, "Unknown panel action.");
                break;
        }
    }

    /**
     * Handle user management callbacks
     */
    private function handleUserManagementCallback(array $callbackQuery, array $data, array $services): void
    {
        $userId = $callbackQuery['from']['id'];
        $callbackId = $callbackQuery['id'];
        
        switch ($data['subaction']) {
            case 'block_user':
                $targetUserId = $data['params']['user_id'] ?? '';
                $this->blockUser($callbackQuery, $targetUserId, $services);
                break;
                
            case 'unblock_user':
                $targetUserId = $data['params']['user_id'] ?? '';
                $this->unblockUser($callbackQuery, $targetUserId, $services);
                break;
                
            case 'view_user':
                $targetUserId = $data['params']['user_id'] ?? '';
                $this->showUserDetails($callbackQuery, $targetUserId, $services);
                break;
                
            case 'add_balance':
                $targetUserId = $data['params']['user_id'] ?? '';
                $this->addUserBalance($callbackQuery, $targetUserId, $services);
                break;
                
            default:
                $this->bot->answerCallbackQuery($callbackId, "Unknown user management action.");
                break;
        }
    }

    /**
     * Handle settings callbacks
     */
    private function handleSettingsCallback(array $callbackQuery, array $data, array $services): void
    {
        $userId = $callbackQuery['from']['id'];
        $callbackId = $callbackQuery['id'];
        
        switch ($data['subaction']) {
            case 'payment_settings':
                $this->showPaymentSettings($callbackQuery, $services);
                break;
                
            case 'panel_settings':
                $this->showPanelSettings($callbackQuery, $services);
                break;
                
            case 'bot_settings':
                $this->showBotSettings($callbackQuery, $services);
                break;
                
            case 'toggle_gateway':
                $gateway = $data['params']['gateway'] ?? '';
                $this->togglePaymentGateway($callbackQuery, $gateway, $services);
                break;
                
            default:
                $this->bot->answerCallbackQuery($callbackId, "Unknown settings action.");
                break;
        }
    }

    /**
     * Handle navigation callbacks
     */
    private function handleNavigationCallback(array $callbackQuery, array $data, array $services): void
    {
        $userId = $callbackQuery['from']['id'];
        $callbackId = $callbackQuery['id'];
        
        switch ($data['subaction']) {
            case 'back':
                $this->navigateBack($callbackQuery, $services);
                break;
                
            case 'home':
                $this->navigateHome($callbackQuery, $services);
                break;
                
            case 'page':
                $page = $data['params']['page'] ?? 1;
                $context = $data['params']['context'] ?? '';
                $this->navigateToPage($callbackQuery, $page, $context, $services);
                break;
                
            default:
                $this->bot->answerCallbackQuery($callbackId, "Unknown navigation action.");
                break;
        }
    }

    /**
     * Parse callback data string into structured array
     */
    private function parseCallbackData(string $data): array
    {
        // Parse callback data format: action:subaction:param1=value1,param2=value2
        $parts = explode(':', $data, 3);
        
        $result = [
            'action' => $parts[0] ?? '',
            'subaction' => $parts[1] ?? '',
            'params' => []
        ];
        
        if (isset($parts[2])) {
            $paramPairs = explode(',', $parts[2]);
            foreach ($paramPairs as $pair) {
                $keyValue = explode('=', $pair, 2);
                if (count($keyValue) === 2) {
                    $result['params'][$keyValue[0]] = $keyValue[1];
                }
            }
        }
        
        return $result;
    }

    /**
     * Build callback data string from structured array
     */
    public static function buildCallbackData(string $action, string $subaction = '', array $params = []): string
    {
        $data = $action;
        
        if ($subaction) {
            $data .= ':' . $subaction;
        }
        
        if (!empty($params)) {
            $paramPairs = [];
            foreach ($params as $key => $value) {
                $paramPairs[] = $key . '=' . $value;
            }
            $data .= ':' . implode(',', $paramPairs);
        }
        
        return $data;
    }

    // Placeholder methods for specific callback handlers
    private function selectPaymentGateway(array $callbackQuery, string $gateway, array $services): void {}
    private function confirmPayment(array $callbackQuery, string $paymentId, array $services): void {}
    private function cancelPayment(array $callbackQuery, string $paymentId, array $services): void {}
    private function initiateBalanceAddition(array $callbackQuery, array $services): void {}
    private function showTransactionHistory(array $callbackQuery, array $services): void {}
    private function initiateServicePurchase(array $callbackQuery, string $serviceId, array $services): void {}
    private function initiateServiceExtension(array $callbackQuery, string $userServiceId, array $services): void {}
    private function showServiceConfig(array $callbackQuery, string $userServiceId, array $services): void {}
    private function initiateTestService(array $callbackQuery, string $serviceId, array $services): void {}
    private function showServiceCategory(array $callbackQuery, string $categoryId, array $services): void {}
    private function showUserStatistics(array $callbackQuery, array $services): void {}
    private function showSystemStatistics(array $callbackQuery, array $services): void {}
    private function initiateBroadcastMessage(array $callbackQuery, array $services): void {}
    private function showServiceManagement(array $callbackQuery, array $services): void {}
    private function showFinancialReport(array $callbackQuery, array $services): void {}
    private function selectPanel(array $callbackQuery, string $panelType, array $services): void {}
    private function testPanelConnection(array $callbackQuery, string $panelId, array $services): void {}
    private function syncPanelUsers(array $callbackQuery, string $panelId, array $services): void {}
    private function blockUser(array $callbackQuery, string $targetUserId, array $services): void {}
    private function unblockUser(array $callbackQuery, string $targetUserId, array $services): void {}
    private function showUserDetails(array $callbackQuery, string $targetUserId, array $services): void {}
    private function addUserBalance(array $callbackQuery, string $targetUserId, array $services): void {}
    private function showPaymentSettings(array $callbackQuery, array $services): void {}
    private function showPanelSettings(array $callbackQuery, array $services): void {}
    private function showBotSettings(array $callbackQuery, array $services): void {}
    private function togglePaymentGateway(array $callbackQuery, string $gateway, array $services): void {}
    private function navigateBack(array $callbackQuery, array $services): void {}
    private function navigateHome(array $callbackQuery, array $services): void {}
    private function navigateToPage(array $callbackQuery, int $page, string $context, array $services): void {}
    private function handleUnknownCallback(array $callbackQuery, array $data, array $services): void {}
}