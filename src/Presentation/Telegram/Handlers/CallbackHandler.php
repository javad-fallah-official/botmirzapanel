<?php

declare(strict_types=1);

namespace BotMirzaPanel\Presentation\Telegram\Handlers;

use BotMirzaPanel\Infrastructure\Container\ServiceContainer;
use BotMirzaPanel\Infrastructure\External\Telegram\TelegramServiceInterface;
use BotMirzaPanel\Application\Queries\User\GetUserByTelegramIdQuery;
use BotMirzaPanel\Application\Queries\User\GetUserByTelegramIdQueryHandler;
use BotMirzaPanel\Domain\ValueObjects\User\TelegramId;

/**
 * Telegram Callback Handler
 * 
 * Handles inline keyboard button callbacks
 */
class CallbackHandler
{
    private ServiceContainer $container;
    private TelegramServiceInterface $telegram;

    public function __construct(ServiceContainer $container, TelegramServiceInterface $telegram)
    {
        $this->container = $container;
        $this->telegram = $telegram;
    }

    /**
     * Handle incoming callback query
     */
    public function handle(array $callbackQuery): void
    {
        if (!isset($callbackQuery['from']['id'], $callbackQuery['message']['chat']['id'], 
                   $callbackQuery['message']['message_id'], $callbackQuery['data'], $callbackQuery['id'])) {
            return; // Invalid callback query structure
        }
        
        $userId = $callbackQuery['from']['id'];
        $chatId = $callbackQuery['message']['chat']['id'];
        $messageId = $callbackQuery['message']['message_id'];
        $data = $callbackQuery['data'];
        
        // Check if user is blocked
        if ($this->isUserBlocked($userId)) {
            $this->telegram->answerCallbackQuery($callbackQuery['id'], [
                'text' => 'Access denied.',
                'show_alert' => true
            ]);
            return;
        }
        
        // Route to appropriate callback handler
        switch ($data) {
            case 'profile':
                $this->handleProfileCallback($callbackQuery);
                break;
                
            case 'balance':
                $this->handleBalanceCallback($callbackQuery);
                break;
                
            case 'services':
                $this->handleServicesCallback($callbackQuery);
                break;
                
            case 'payment':
                $this->handlePaymentCallback($callbackQuery);
                break;
                
            case 'help':
                $this->handleHelpCallback($callbackQuery);
                break;
                
            case 'support':
                $this->handleSupportCallback($callbackQuery);
                break;
                
            case 'add_balance':
                $this->handleAddBalanceCallback($callbackQuery);
                break;
                
            case 'transaction_history':
                $this->handleTransactionHistoryCallback($callbackQuery);
                break;
                
            case 'settings':
                $this->handleSettingsCallback($callbackQuery);
                break;
                
            case 'settings_notifications':
                $this->handleNotificationSettingsCallback($callbackQuery);
                break;
                
            case 'settings_language':
                $this->handleLanguageSettingsCallback($callbackQuery);
                break;
                
            case 'settings_security':
                $this->handleSecuritySettingsCallback($callbackQuery);
                break;
                
            case 'services_vpn':
                $this->handleVpnServicesCallback($callbackQuery);
                break;
                
            case 'services_proxy':
                $this->handleProxyServicesCallback($callbackQuery);
                break;
                
            case 'services_panel':
                $this->handlePanelServicesCallback($callbackQuery);
                break;
                
            case 'payment_card':
                $this->handleCardPaymentCallback($callbackQuery);
                break;
                
            case 'payment_crypto':
                $this->handleCryptoPaymentCallback($callbackQuery);
                break;
                
            case 'payment_bank':
                $this->handleBankPaymentCallback($callbackQuery);
                break;
                
            case 'support_chat':
                $this->handleSupportChatCallback($callbackQuery);
                break;
                
            case 'support_faq':
                $this->handleSupportFaqCallback($callbackQuery);
                break;
                
            default:
                $this->handleUnknownCallback($callbackQuery);
                break;
        }
    }

    /**
     * Handle profile callback
     */
    private function handleProfileCallback(array $callbackQuery): void
    {
        $userId = $callbackQuery['from']['id'];
        $chatId = $callbackQuery['message']['chat']['id'];
        $messageId = $callbackQuery['message']['message_id'];
        
        try {
            $query = new GetUserByTelegramIdQuery(new TelegramId($userId));
            $handler = $this->container->get(GetUserByTelegramIdQueryHandler::class);
            $user = $handler->handle($query);
            
            if (!$user) {
                $this->telegram->answerCallbackQuery($callbackQuery['id'], [
                    'text' => 'User not found. Please use /start to register.',
                    'show_alert' => true
                ]);
                return;
            }
            
            $profileText = "👤 **Your Profile**\n\n";
            $profileText .= "🆔 User ID: `{$user->getId()->getValue()}`\n";
            $profileText .= "👤 Username: {$user->getUsername()->getValue()}\n";
            $profileText .= "📧 Email: {$user->getEmail()?->getValue()}\n";
            $profileText .= "📱 Telegram ID: `{$user->getTelegramId()->getValue()}`\n";
            $profileText .= "📊 Status: {$user->getStatus()->getValue()}\n";
            $profileText .= "💰 Balance: {$user->getBalance()->getAmount()} {$user->getBalance()->getCurrency()->getCode()}\n";
            $profileText .= "📅 Joined: {$user->getCreatedAt()->format('Y-m-d H:i')}";
            
            $this->telegram->editMessageText($chatId, $messageId, $profileText, [
                'parse_mode' => 'Markdown',
                'reply_markup' => [
                    'inline_keyboard' => [
                        [
                            ['text' => '💰 Add Balance', 'callback_data' => 'add_balance'],
                            ['text' => '⚙️ Settings', 'callback_data' => 'settings']
                        ],
                        [
                            ['text' => '🔙 Back to Menu', 'callback_data' => 'main_menu']
                        ]
                    ]
                ]
            ]);
            
            $this->telegram->answerCallbackQuery($callbackQuery['id']);
            
        } catch (\Exception $e) {
            $this->telegram->answerCallbackQuery($callbackQuery['id'], [
                'text' => 'Error loading profile. Please try again.',
                'show_alert' => true
            ]);
            error_log("Profile callback error: " . $e->getMessage());
        }
    }

    /**
     * Handle balance callback
     */
    private function handleBalanceCallback(array $callbackQuery): void
    {
        $userId = $callbackQuery['from']['id'];
        $chatId = $callbackQuery['message']['chat']['id'];
        $messageId = $callbackQuery['message']['message_id'];
        
        try {
            $query = new GetUserByTelegramIdQuery(new TelegramId($userId));
            $handler = $this->container->get(GetUserByTelegramIdQueryHandler::class);
            $user = $handler->handle($query);
            
            if (!$user) {
                $this->telegram->answerCallbackQuery($callbackQuery['id'], [
                    'text' => 'User not found.',
                    'show_alert' => true
                ]);
                return;
            }
            
            $balanceText = "💰 **Your Balance**\n\n";
            $balanceText .= "Current Balance: **{$user->getBalance()->getAmount()} {$user->getBalance()->getCurrency()->getCode()}**\n\n";
            $balanceText .= "💳 Ready to add more funds?";
            
            $this->telegram->editMessageText($chatId, $messageId, $balanceText, [
                'parse_mode' => 'Markdown',
                'reply_markup' => [
                    'inline_keyboard' => [
                        [
                            ['text' => '💳 Add Balance', 'callback_data' => 'add_balance']
                        ],
                        [
                            ['text' => '📊 Transaction History', 'callback_data' => 'transaction_history']
                        ],
                        [
                            ['text' => '🔙 Back to Menu', 'callback_data' => 'main_menu']
                        ]
                    ]
                ]
            ]);
            
            $this->telegram->answerCallbackQuery($callbackQuery['id']);
            
        } catch (\Exception $e) {
            $this->telegram->answerCallbackQuery($callbackQuery['id'], [
                'text' => 'Error loading balance. Please try again.',
                'show_alert' => true
            ]);
            error_log("Balance callback error: " . $e->getMessage());
        }
    }

    /**
     * Handle services callback
     */
    private function handleServicesCallback(array $callbackQuery): void
    {
        $chatId = $callbackQuery['message']['chat']['id'];
        $messageId = $callbackQuery['message']['message_id'];
        
        $servicesText = "🛒 **Available Services**\n\n";
        $servicesText .= "📱 VPN Services\n";
        $servicesText .= "🔒 Proxy Services\n";
        $servicesText .= "🌐 Panel Access\n\n";
        $servicesText .= "Select a category to browse:";
        
        $this->telegram->editMessageText($chatId, $messageId, $servicesText, [
            'parse_mode' => 'Markdown',
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => '📱 VPN Services', 'callback_data' => 'services_vpn']
                    ],
                    [
                        ['text' => '🔒 Proxy Services', 'callback_data' => 'services_proxy']
                    ],
                    [
                        ['text' => '🌐 Panel Access', 'callback_data' => 'services_panel']
                    ],
                    [
                        ['text' => '🔙 Back to Menu', 'callback_data' => 'main_menu']
                    ]
                ]
            ]
        ]);
        
        $this->telegram->answerCallbackQuery($callbackQuery['id']);
    }

    /**
     * Handle payment callback
     */
    private function handlePaymentCallback(array $callbackQuery): void
    {
        $chatId = $callbackQuery['message']['chat']['id'];
        $messageId = $callbackQuery['message']['message_id'];
        
        $paymentText = "💳 **Payment Options**\n\n";
        $paymentText .= "Choose your preferred payment method:";
        
        $this->telegram->editMessageText($chatId, $messageId, $paymentText, [
            'parse_mode' => 'Markdown',
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => '💳 Credit Card', 'callback_data' => 'payment_card']
                    ],
                    [
                        ['text' => '₿ Cryptocurrency', 'callback_data' => 'payment_crypto']
                    ],
                    [
                        ['text' => '🏦 Bank Transfer', 'callback_data' => 'payment_bank']
                    ],
                    [
                        ['text' => '🔙 Back to Menu', 'callback_data' => 'main_menu']
                    ]
                ]
            ]
        ]);
        
        $this->telegram->answerCallbackQuery($callbackQuery['id']);
    }

    /**
     * Handle help callback
     */
    private function handleHelpCallback(array $callbackQuery): void
    {
        $chatId = $callbackQuery['message']['chat']['id'];
        $messageId = $callbackQuery['message']['message_id'];
        
        $helpText = "📋 **Available Commands:**\n\n";
        $helpText .= "/start - Start the bot and register\n";
        $helpText .= "/help - Show this help message\n";
        $helpText .= "/profile - View your profile\n";
        $helpText .= "/balance - Check your balance\n";
        $helpText .= "/services - Browse available services\n";
        $helpText .= "/buy - Purchase a service\n";
        $helpText .= "/payment - Make a payment\n";
        $helpText .= "/support - Contact support\n";
        $helpText .= "/settings - Manage your settings\n\n";
        $helpText .= "💡 You can also use the inline buttons for easier navigation.";
        
        $this->telegram->editMessageText($chatId, $messageId, $helpText, [
            'parse_mode' => 'Markdown',
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => '🔙 Back to Menu', 'callback_data' => 'main_menu']
                    ]
                ]
            ]
        ]);
        
        $this->telegram->answerCallbackQuery($callbackQuery['id']);
    }

    /**
     * Handle support callback
     */
    private function handleSupportCallback(array $callbackQuery): void
    {
        $chatId = $callbackQuery['message']['chat']['id'];
        $messageId = $callbackQuery['message']['message_id'];
        
        $supportText = "🎧 **Support Center**\n\n";
        $supportText .= "Need help? We're here for you!\n\n";
        $supportText .= "📞 Contact our support team:\n";
        $supportText .= "📧 Email: support@example.com\n";
        $supportText .= "💬 Live Chat: Available 24/7\n\n";
        $supportText .= "🔍 Or browse our FAQ section.";
        
        $this->telegram->editMessageText($chatId, $messageId, $supportText, [
            'parse_mode' => 'Markdown',
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => '💬 Live Chat', 'callback_data' => 'support_chat']
                    ],
                    [
                        ['text' => '❓ FAQ', 'callback_data' => 'support_faq']
                    ],
                    [
                        ['text' => '📧 Email Support', 'url' => 'mailto:support@example.com']
                    ],
                    [
                        ['text' => '🔙 Back to Menu', 'callback_data' => 'main_menu']
                    ]
                ]
            ]
        ]);
        
        $this->telegram->answerCallbackQuery($callbackQuery['id']);
    }

    /**
     * Handle add balance callback
     */
    private function handleAddBalanceCallback(array $callbackQuery): void
    {
        $chatId = $callbackQuery['message']['chat']['id'];
        
        $this->telegram->sendMessage($chatId, "💳 **Add Balance**\n\nPlease specify the amount you want to add:\n\nExample: `50 USD` or `100 EUR`", [
            'parse_mode' => 'Markdown'
        ]);
        
        $this->telegram->answerCallbackQuery($callbackQuery['id'], [
            'text' => 'Please send the amount you want to add.'
        ]);
    }

    /**
     * Handle transaction history callback
     */
    private function handleTransactionHistoryCallback(array $callbackQuery): void
    {
        $this->telegram->answerCallbackQuery($callbackQuery['id'], [
            'text' => 'Transaction history feature coming soon!',
            'show_alert' => true
        ]);
    }

    /**
     * Handle settings callback
     */
    private function handleSettingsCallback(array $callbackQuery): void
    {
        $chatId = $callbackQuery['message']['chat']['id'];
        $messageId = $callbackQuery['message']['message_id'];
        
        $settingsText = "⚙️ **Settings**\n\n";
        $settingsText .= "Manage your account settings:";
        
        $this->telegram->editMessageText($chatId, $messageId, $settingsText, [
            'parse_mode' => 'Markdown',
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => '🔔 Notifications', 'callback_data' => 'settings_notifications']
                    ],
                    [
                        ['text' => '🌐 Language', 'callback_data' => 'settings_language']
                    ],
                    [
                        ['text' => '🔐 Security', 'callback_data' => 'settings_security']
                    ],
                    [
                        ['text' => '🔙 Back to Menu', 'callback_data' => 'main_menu']
                    ]
                ]
            ]
        ]);
        
        $this->telegram->answerCallbackQuery($callbackQuery['id']);
    }

    /**
     * Handle notification settings callback
     */
    private function handleNotificationSettingsCallback(array $callbackQuery): void
    {
        $this->telegram->answerCallbackQuery($callbackQuery['id'], [
            'text' => 'Notification settings coming soon!',
            'show_alert' => true
        ]);
    }

    /**
     * Handle language settings callback
     */
    private function handleLanguageSettingsCallback(array $callbackQuery): void
    {
        $this->telegram->answerCallbackQuery($callbackQuery['id'], [
            'text' => 'Language settings coming soon!',
            'show_alert' => true
        ]);
    }

    /**
     * Handle security settings callback
     */
    private function handleSecuritySettingsCallback(array $callbackQuery): void
    {
        $this->telegram->answerCallbackQuery($callbackQuery['id'], [
            'text' => 'Security settings coming soon!',
            'show_alert' => true
        ]);
    }

    /**
     * Handle VPN services callback
     */
    private function handleVpnServicesCallback(array $callbackQuery): void
    {
        $this->telegram->answerCallbackQuery($callbackQuery['id'], [
            'text' => 'VPN services coming soon!',
            'show_alert' => true
        ]);
    }

    /**
     * Handle proxy services callback
     */
    private function handleProxyServicesCallback(array $callbackQuery): void
    {
        $this->telegram->answerCallbackQuery($callbackQuery['id'], [
            'text' => 'Proxy services coming soon!',
            'show_alert' => true
        ]);
    }

    /**
     * Handle panel services callback
     */
    private function handlePanelServicesCallback(array $callbackQuery): void
    {
        $this->telegram->answerCallbackQuery($callbackQuery['id'], [
            'text' => 'Panel services coming soon!',
            'show_alert' => true
        ]);
    }

    /**
     * Handle card payment callback
     */
    private function handleCardPaymentCallback(array $callbackQuery): void
    {
        $this->telegram->answerCallbackQuery($callbackQuery['id'], [
            'text' => 'Card payment coming soon!',
            'show_alert' => true
        ]);
    }

    /**
     * Handle crypto payment callback
     */
    private function handleCryptoPaymentCallback(array $callbackQuery): void
    {
        $this->telegram->answerCallbackQuery($callbackQuery['id'], [
            'text' => 'Crypto payment coming soon!',
            'show_alert' => true
        ]);
    }

    /**
     * Handle bank payment callback
     */
    private function handleBankPaymentCallback(array $callbackQuery): void
    {
        $this->telegram->answerCallbackQuery($callbackQuery['id'], [
            'text' => 'Bank payment coming soon!',
            'show_alert' => true
        ]);
    }

    /**
     * Handle support chat callback
     */
    private function handleSupportChatCallback(array $callbackQuery): void
    {
        $this->telegram->answerCallbackQuery($callbackQuery['id'], [
            'text' => 'Live chat coming soon!',
            'show_alert' => true
        ]);
    }

    /**
     * Handle support FAQ callback
     */
    private function handleSupportFaqCallback(array $callbackQuery): void
    {
        $this->telegram->answerCallbackQuery($callbackQuery['id'], [
            'text' => 'FAQ section coming soon!',
            'show_alert' => true
        ]);
    }

    /**
     * Handle unknown callback
     */
    private function handleUnknownCallback(array $callbackQuery): void
    {
        $this->telegram->answerCallbackQuery($callbackQuery['id'], [
            'text' => 'Unknown action.',
            'show_alert' => true
        ]);
    }

    /**
     * Check if user is blocked
     */
    private function isUserBlocked(int $userId): bool
    {
        try {
            $query = new GetUserByTelegramIdQuery(new TelegramId($userId));
            $handler = $this->container->get(GetUserByTelegramIdQueryHandler::class);
            $user = $handler->handle($query);
            
            return $user && $user->getStatus()->getValue() === 'banned';
        } catch (\Exception $e) {
            return false;
        }
    }
}