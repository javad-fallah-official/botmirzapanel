<?php

namespace BotMirzaPanel\Telegram\Handlers;

use BotMirzaPanel\Telegram\TelegramBot;

/**
 * Handles bot commands (messages starting with /)
 * Processes system commands and user interactions
 */
class CommandHandler
{
    private TelegramBot $bot;

    public function __construct(TelegramBot $bot)
    {
        $this->bot = $bot;
    }

    /**
     * Handle incoming command
     */
    public function handle(array $message, array $services): void
    {
        $userId = $message['from']['id'];
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        
        // Extract command and parameters
        $commandData = $this->parseCommand($text);
        $command = $commandData['command'];
        $params = $commandData['params'];
        
        /** @var \BotMirzaPanel\User\UserService $userService */
        $userService = $services['user'];
        
        // Check if user is blocked
        if ($userService->isUserBlocked($userId)) {
            return;
        }
        
        // Route to appropriate command handler
        switch ($command) {
            case '/start':
                $this->handleStartCommand($message, $params, $services);
                break;
                
            case '/help':
                $this->handleHelpCommand($message, $services);
                break;
                
            case '/menu':
                $this->handleMenuCommand($message, $services);
                break;
                
            case '/balance':
                $this->handleBalanceCommand($message, $services);
                break;
                
            case '/services':
                $this->handleServicesCommand($message, $services);
                break;
                
            case '/support':
                $this->handleSupportCommand($message, $services);
                break;
                
            case '/admin':
                $this->handleAdminCommand($message, $services);
                break;
                
            case '/stats':
                $this->handleStatsCommand($message, $services);
                break;
                
            case '/broadcast':
                $this->handleBroadcastCommand($message, $params, $services);
                break;
                
            case '/user':
                $this->handleUserCommand($message, $params, $services);
                break;
                
            case '/panel':
                $this->handlePanelCommand($message, $params, $services);
                break;
                
            case '/settings':
                $this->handleSettingsCommand($message, $services);
                break;
                
            case '/test':
                $this->handleTestCommand($message, $params, $services);
                break;
                
            default:
                $this->handleUnknownCommand($message, $command, $services);
                break;
        }
    }

    /**
     * Handle /start command
     */
    private function handleStartCommand(array $message, array $params, array $services): void
    {
        $userId = $message['from']['id'];
        $userInfo = $message['from'];
        
        /** @var \BotMirzaPanel\User\UserService $userService */
        $userService = $services['user'];
        
        /** @var \BotMirzaPanel\Config\ConfigManager $config */
        $config = $services['config'];
        
        // Check for referral parameter
        $referralCode = $params[0] ?? null;
        
        // Get or create user
        $user = $userService->getOrCreateUser($userId, $userInfo, $referralCode);
        
        // Check if user needs to verify phone number
        if (!$user['phone_verified']) {
            $this->requestPhoneVerification($message, $services);
            return;
        }
        
        // Check channel membership if required
        if ($config->get('bot.require_channel_membership', false)) {
            if (!$this->checkChannelMembership($userId, $services)) {
                $this->requestChannelMembership($message, $services);
                return;
            }
        }
        
        // Show welcome message and main menu
        $this->showWelcomeMessage($message, $services);
    }

    /**
     * Handle /help command
     */
    private function handleHelpCommand(array $message, array $services): void
    {
        $userId = $message['from']['id'];
        
        $helpText = $this->getHelpText($services);
        $keyboard = $this->getHelpKeyboard($services);
        
        $this->bot->sendMessage($userId, $helpText, $keyboard);
    }

    /**
     * Handle /menu command
     */
    private function handleMenuCommand(array $message, array $services): void
    {
        $userId = $message['from']['id'];
        
        /** @var \BotMirzaPanel\User\UserService $userService */
        $userService = $services['user'];
        
        // Reset user step to home
        $userService->setUserStep($userId, 'home');
        
        // Show main menu
        $this->showMainMenu($message, $services);
    }

    /**
     * Handle /balance command
     */
    private function handleBalanceCommand(array $message, array $services): void
    {
        $userId = $message['from']['id'];
        
        /** @var \BotMirzaPanel\User\UserService $userService */
        $userService = $services['user'];
        
        $balance = $userService->getUserBalance($userId);
        $currency = $this->getCurrency($services);
        
        $balanceText = "💰 Your current balance: {$balance} {$currency}";
        $keyboard = $this->getBalanceKeyboard($services);
        
        $this->bot->sendMessage($userId, $balanceText, $keyboard);
    }

    /**
     * Handle /services command
     */
    private function handleServicesCommand(array $message, array $services): void
    {
        $userId = $message['from']['id'];
        
        /** @var \BotMirzaPanel\User\UserService $userService */
        $userService = $services['user'];
        
        $userServices = $userService->getUserServices($userId);
        
        if (empty($userServices)) {
            $text = "You don't have any active services.\n\nWould you like to purchase a service?";
            $keyboard = $this->getServicePurchaseKeyboard($services);
        } else {
            $text = $this->formatUserServices($userServices);
            $keyboard = $this->getUserServicesKeyboard($userServices, $services);
        }
        
        $this->bot->sendMessage($userId, $text, $keyboard);
    }

    /**
     * Handle /support command
     */
    private function handleSupportCommand(array $message, array $services): void
    {
        $userId = $message['from']['id'];
        
        /** @var \BotMirzaPanel\Config\ConfigManager $config */
        $config = $services['config'];
        
        $supportText = $this->getSupportText($services);
        $supportUsername = $config->get('bot.support_username');
        
        if ($supportUsername) {
            $supportText .= "\n\n📞 Contact support: @{$supportUsername}";
        }
        
        $keyboard = $this->getSupportKeyboard($services);
        
        $this->bot->sendMessage($userId, $supportText, $keyboard);
    }

    /**
     * Handle /admin command
     */
    private function handleAdminCommand(array $message, array $services): void
    {
        $userId = $message['from']['id'];
        
        /** @var \BotMirzaPanel\Config\ConfigManager $config */
        $config = $services['config'];
        
        // Check if user is admin
        if ($userId != $config->get('admin.id')) {
            $this->bot->sendMessage($userId, "❌ Access denied.");
            return;
        }
        
        $adminText = "🔧 Admin Panel\n\nSelect an option:";
        $keyboard = $this->getAdminKeyboard($services);
        
        $this->bot->sendMessage($userId, $adminText, $keyboard);
    }

    /**
     * Handle /stats command
     */
    private function handleStatsCommand(array $message, array $services): void
    {
        $userId = $message['from']['id'];
        
        /** @var \BotMirzaPanel\Config\ConfigManager $config */
        $config = $services['config'];
        
        // Check if user is admin
        if ($userId != $config->get('admin.id')) {
            $this->bot->sendMessage($userId, "❌ Access denied.");
            return;
        }
        
        /** @var \BotMirzaPanel\User\UserService $userService */
        $userService = $services['user'];
        
        $stats = $userService->getSystemStats();
        $statsText = $this->formatSystemStats($stats);
        
        $this->bot->sendMessage($userId, $statsText);
    }

    /**
     * Handle /broadcast command
     */
    private function handleBroadcastCommand(array $message, array $params, array $services): void
    {
        $userId = $message['from']['id'];
        
        /** @var \BotMirzaPanel\Config\ConfigManager $config */
        $config = $services['config'];
        
        // Check if user is admin
        if ($userId != $config->get('admin.id')) {
            $this->bot->sendMessage($userId, "❌ Access denied.");
            return;
        }
        
        if (empty($params)) {
            $this->bot->sendMessage($userId, "Usage: /broadcast <message>");
            return;
        }
        
        $broadcastMessage = implode(' ', $params);
        
        /** @var \BotMirzaPanel\User\UserService $userService */
        $userService = $services['user'];
        
        // Queue broadcast message
        $userService->queueBroadcastMessage($broadcastMessage);
        
        $this->bot->sendMessage($userId, "✅ Broadcast message queued for delivery.");
    }

    /**
     * Handle /user command
     */
    private function handleUserCommand(array $message, array $params, array $services): void
    {
        $userId = $message['from']['id'];
        
        /** @var \BotMirzaPanel\Config\ConfigManager $config */
        $config = $services['config'];
        
        // Check if user is admin
        if ($userId != $config->get('admin.id')) {
            $this->bot->sendMessage($userId, "❌ Access denied.");
            return;
        }
        
        if (empty($params)) {
            $this->bot->sendMessage($userId, "Usage: /user <user_id>");
            return;
        }
        
        $targetUserId = $params[0];
        
        /** @var \BotMirzaPanel\User\UserService $userService */
        $userService = $services['user'];
        
        $userInfo = $userService->getUserInfo($targetUserId);
        
        if (!$userInfo) {
            $this->bot->sendMessage($userId, "❌ User not found.");
            return;
        }
        
        $userText = $this->formatUserInfo($userInfo);
        $keyboard = $this->getUserManagementKeyboard($targetUserId, $services);
        
        $this->bot->sendMessage($userId, $userText, $keyboard);
    }

    /**
     * Handle /panel command
     */
    private function handlePanelCommand(array $message, array $params, array $services): void
    {
        $userId = $message['from']['id'];
        
        /** @var \BotMirzaPanel\Config\ConfigManager $config */
        $config = $services['config'];
        
        // Check if user is admin
        if ($userId != $config->get('admin.id')) {
            $this->bot->sendMessage($userId, "❌ Access denied.");
            return;
        }
        
        /** @var \BotMirzaPanel\Panel\PanelService $panelService */
        $panelService = $services['panel'];
        
        if (empty($params)) {
            // Show panel status
            $panelStatus = $panelService->getAllPanelStatus();
            $statusText = $this->formatPanelStatus($panelStatus);
            
            $this->bot->sendMessage($userId, $statusText);
        } else {
            $action = $params[0];
            $panelId = $params[1] ?? null;
            
            switch ($action) {
                case 'test':
                    if ($panelId) {
                        $result = $panelService->testPanelConnection($panelId);
                        $message = $result ? "✅ Panel connection successful" : "❌ Panel connection failed";
                        $this->bot->sendMessage($userId, $message);
                    }
                    break;
                    
                case 'sync':
                    if ($panelId) {
                        $panelService->syncPanelUsers($panelId);
                        $this->bot->sendMessage($userId, "✅ Panel sync initiated");
                    }
                    break;
            }
        }
    }

    /**
     * Handle /settings command
     */
    private function handleSettingsCommand(array $message, array $services): void
    {
        $userId = $message['from']['id'];
        
        /** @var \BotMirzaPanel\Config\ConfigManager $config */
        $config = $services['config'];
        
        // Check if user is admin
        if ($userId != $config->get('admin.id')) {
            $this->bot->sendMessage($userId, "❌ Access denied.");
            return;
        }
        
        $settingsText = "⚙️ Bot Settings\n\nSelect a category:";
        $keyboard = $this->getSettingsKeyboard($services);
        
        $this->bot->sendMessage($userId, $settingsText, $keyboard);
    }

    /**
     * Handle /test command
     */
    private function handleTestCommand(array $message, array $params, array $services): void
    {
        $userId = $message['from']['id'];
        
        /** @var \BotMirzaPanel\Config\ConfigManager $config */
        $config = $services['config'];
        
        // Check if user is admin
        if ($userId != $config->get('admin.id')) {
            $this->bot->sendMessage($userId, "❌ Access denied.");
            return;
        }
        
        if (empty($params)) {
            $this->bot->sendMessage($userId, "Usage: /test <component>");
            return;
        }
        
        $component = $params[0];
        
        switch ($component) {
            case 'db':
                $this->testDatabaseConnection($message, $services);
                break;
                
            case 'panels':
                $this->testAllPanels($message, $services);
                break;
                
            case 'payments':
                $this->testPaymentGateways($message, $services);
                break;
                
            default:
                $this->bot->sendMessage($userId, "Unknown test component: {$component}");
                break;
        }
    }

    /**
     * Handle unknown commands
     */
    private function handleUnknownCommand(array $message, string $command, array $services): void
    {
        $userId = $message['from']['id'];
        
        $text = "❓ Unknown command: {$command}\n\nUse /help to see available commands.";
        $this->bot->sendMessage($userId, $text);
    }

    /**
     * Parse command text into command and parameters
     */
    private function parseCommand(string $text): array
    {
        $parts = explode(' ', trim($text));
        $command = array_shift($parts);
        
        // Remove bot username from command if present
        if (strpos($command, '@') !== false) {
            $command = explode('@', $command)[0];
        }
        
        return [
            'command' => $command,
            'params' => $parts
        ];
    }

    // Placeholder methods for various functionalities
    private function requestPhoneVerification(array $message, array $services): void {}
    private function checkChannelMembership(int $userId, array $services): bool { return true; }
    private function requestChannelMembership(array $message, array $services): void {}
    private function showWelcomeMessage(array $message, array $services): void {}
    private function showMainMenu(array $message, array $services): void {}
    private function getHelpText(array $services): string { return "Help text"; }
    private function getHelpKeyboard(array $services): array { return []; }
    private function getCurrency(array $services): string { return "USD"; }
    private function getBalanceKeyboard(array $services): array { return []; }
    private function formatUserServices(array $services): string { return "Services list"; }
    private function getServicePurchaseKeyboard(array $services): array { return []; }
    private function getUserServicesKeyboard(array $userServices, array $services): array { return []; }
    private function getSupportText(array $services): string { return "Support information"; }
    private function getSupportKeyboard(array $services): array { return []; }
    private function getAdminKeyboard(array $services): array { return []; }
    private function formatSystemStats(array $stats): string { return "System statistics"; }
    private function formatUserInfo(array $userInfo): string { return "User information"; }
    private function getUserManagementKeyboard(string $userId, array $services): array { return []; }
    private function formatPanelStatus(array $status): string { return "Panel status"; }
    private function getSettingsKeyboard(array $services): array { return []; }
    private function testDatabaseConnection(array $message, array $services): void {}
    private function testAllPanels(array $message, array $services): void {}
    private function testPaymentGateways(array $message, array $services): void {}
}