<?php

declare(strict_types=1);

namespace BotMirzaPanel\Presentation\Telegram\Handlers;

use BotMirzaPanel\Infrastructure\Container\ServiceContainer;
use BotMirzaPanel\Infrastructure\External\Telegram\TelegramServiceInterface;
use BotMirzaPanel\Application\Commands\User\CreateUserCommand;
use BotMirzaPanel\Application\Commands\User\CreateUserCommandHandler;
use BotMirzaPanel\Application\Queries\User\GetUserByTelegramIdQuery;
use BotMirzaPanel\Application\Queries\User\GetUserByTelegramIdQueryHandler;
use BotMirzaPanel\Domain\ValueObjects\User\TelegramId;
use BotMirzaPanel\Domain\ValueObjects\User\Username;
use BotMirzaPanel\Domain\ValueObjects\User\Email;
use BotMirzaPanel\Domain\ValueObjects\Common\Money;
use BotMirzaPanel\Domain\ValueObjects\Common\Currency;

/**
 * Telegram Command Handler
 * 
 * Handles bot commands (messages starting with /)
 */
class CommandHandler
{
    private ServiceContainer $container;
    private TelegramServiceInterface $telegram;

    public function __construct(ServiceContainer $container, TelegramServiceInterface $telegram)
    {
        $this->container = $container;
        $this->telegram = $telegram;
    }

    /**
     * Handle incoming command
     */
    public function handle(array $message): void
    {
        $userId = $message['from']['id'];
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        
        // Extract command and parameters
        $commandData = $this->parseCommand($text);
        $command = $commandData['command'];
        $params = $commandData['params'];
        
        // Check if user is blocked
        if ($this->isUserBlocked($userId)) {
            return;
        }
        
        // Route to appropriate command handler
        switch ($command) {
            case '/start':
                $this->handleStartCommand($message, $params);
                break;
                
            case '/help':
                $this->handleHelpCommand($message);
                break;
                
            case '/profile':
                $this->handleProfileCommand($message);
                break;
                
            case '/balance':
                $this->handleBalanceCommand($message);
                break;
                
            case '/services':
                $this->handleServicesCommand($message);
                break;
                
            case '/buy':
                $this->handleBuyCommand($message, $params);
                break;
                
            case '/payment':
                $this->handlePaymentCommand($message, $params);
                break;
                
            case '/support':
                $this->handleSupportCommand($message);
                break;
                
            case '/settings':
                $this->handleSettingsCommand($message);
                break;
                
            default:
                $this->handleUnknownCommand($message);
                break;
        }
    }

    /**
     * Handle /start command
     */
    private function handleStartCommand(array $message, array $params): void
    {
        $userId = $message['from']['id'];
        $chatId = $message['chat']['id'];
        $firstName = $message['from']['first_name'] ?? 'User';
        $username = $message['from']['username'] ?? null;
        
        try {
            // Check if user already exists
            $query = new GetUserByTelegramIdQuery(new TelegramId($userId));
            $handler = $this->container->get(GetUserByTelegramIdQueryHandler::class);
            $user = $handler->handle($query);
            
            if (!$user) {
                // Create new user
                $command = new CreateUserCommand(
                    username: new Username($username ?? 'user_' . $userId),
                    email: new Email($userId . '@telegram.local'), // Placeholder email
                    telegramId: new TelegramId($userId),
                    balance: new Money(0, new Currency('USD'))
                );
                
                $createHandler = $this->container->get(CreateUserCommandHandler::class);
                $createHandler->handle($command);
                
                $welcomeMessage = "🎉 Welcome to BotMirzaPanel, {$firstName}!\n\n";
                $welcomeMessage .= "Your account has been created successfully.\n";
                $welcomeMessage .= "Use /help to see available commands.";
            } else {
                $welcomeMessage = "👋 Welcome back, {$firstName}!\n\n";
                $welcomeMessage .= "Use /help to see available commands.";
            }
            
            $this->telegram->sendMessage($chatId, $welcomeMessage, [
                'reply_markup' => [
                    'inline_keyboard' => [
                        [
                            ['text' => '👤 Profile', 'callback_data' => 'profile'],
                            ['text' => '💰 Balance', 'callback_data' => 'balance']
                        ],
                        [
                            ['text' => '🛒 Services', 'callback_data' => 'services'],
                            ['text' => '💳 Payment', 'callback_data' => 'payment']
                        ],
                        [
                            ['text' => '❓ Help', 'callback_data' => 'help'],
                            ['text' => '🎧 Support', 'callback_data' => 'support']
                        ]
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->telegram->sendMessage($chatId, "❌ An error occurred. Please try again later.");
            error_log("Start command error: " . $e->getMessage());
        }
    }

    /**
     * Handle /help command
     */
    private function handleHelpCommand(array $message): void
    {
        $chatId = $message['chat']['id'];
        
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
        
        $this->telegram->sendMessage($chatId, $helpText, ['parse_mode' => 'Markdown']);
    }

    /**
     * Handle /profile command
     */
    private function handleProfileCommand(array $message): void
    {
        $userId = $message['from']['id'];
        $chatId = $message['chat']['id'];
        
        try {
            $query = new GetUserByTelegramIdQuery(new TelegramId($userId));
            $handler = $this->container->get(GetUserByTelegramIdQueryHandler::class);
            $user = $handler->handle($query);
            
            if (!$user) {
                $this->telegram->sendMessage($chatId, "❌ User not found. Please use /start to register.");
                return;
            }
            
            $profileText = "👤 **Your Profile**\n\n";
            $profileText .= "🆔 User ID: `{$user->getId()->getValue()}`\n";
            $profileText .= "👤 Username: {$user->getUsername()->getValue()}\n";
            $profileText .= "📧 Email: {$user->getEmail()->getValue()}\n";
            $profileText .= "📱 Telegram ID: `{$user->getTelegramId()->getValue()}`\n";
            $profileText .= "📊 Status: {$user->getStatus()->getValue()}\n";
            $profileText .= "💰 Balance: {$user->getBalance()->getAmount()} {$user->getBalance()->getCurrency()->getCode()}\n";
            $profileText .= "📅 Joined: {$user->getCreatedAt()->format('Y-m-d H:i')}";
            
            $this->telegram->sendMessage($chatId, $profileText, [
                'parse_mode' => 'Markdown',
                'reply_markup' => [
                    'inline_keyboard' => [
                        [
                            ['text' => '💰 Add Balance', 'callback_data' => 'add_balance'],
                            ['text' => '⚙️ Settings', 'callback_data' => 'settings']
                        ]
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->telegram->sendMessage($chatId, "❌ Error retrieving profile. Please try again later.");
            error_log("Profile command error: " . $e->getMessage());
        }
    }

    /**
     * Handle /balance command
     */
    private function handleBalanceCommand(array $message): void
    {
        $userId = $message['from']['id'];
        $chatId = $message['chat']['id'];
        
        try {
            $query = new GetUserByTelegramIdQuery(new TelegramId($userId));
            $handler = $this->container->get(GetUserByTelegramIdQueryHandler::class);
            $user = $handler->handle($query);
            
            if (!$user) {
                $this->telegram->sendMessage($chatId, "❌ User not found. Please use /start to register.");
                return;
            }
            
            $balanceText = "💰 **Your Balance**\n\n";
            $balanceText .= "Current Balance: **{$user->getBalance()->getAmount()} {$user->getBalance()->getCurrency()->getCode()}**\n\n";
            $balanceText .= "💳 Ready to add more funds?";
            
            $this->telegram->sendMessage($chatId, $balanceText, [
                'parse_mode' => 'Markdown',
                'reply_markup' => [
                    'inline_keyboard' => [
                        [
                            ['text' => '💳 Add Balance', 'callback_data' => 'add_balance']
                        ],
                        [
                            ['text' => '📊 Transaction History', 'callback_data' => 'transaction_history']
                        ]
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->telegram->sendMessage($chatId, "❌ Error retrieving balance. Please try again later.");
            error_log("Balance command error: " . $e->getMessage());
        }
    }

    /**
     * Handle /services command
     */
    private function handleServicesCommand(array $message): void
    {
        $chatId = $message['chat']['id'];
        
        $servicesText = "🛒 **Available Services**\n\n";
        $servicesText .= "📱 VPN Services\n";
        $servicesText .= "🔒 Proxy Services\n";
        $servicesText .= "🌐 Panel Access\n\n";
        $servicesText .= "Select a category to browse:";
        
        $this->telegram->sendMessage($chatId, $servicesText, [
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
                    ]
                ]
            ]
        ]);
    }

    /**
     * Handle /buy command
     */
    private function handleBuyCommand(array $message, array $params): void
    {
        $chatId = $message['chat']['id'];
        
        if (empty($params)) {
            $this->telegram->sendMessage($chatId, "🛒 Please specify what you want to buy.\n\nExample: `/buy vpn` or use /services to browse.", ['parse_mode' => 'Markdown']);
            return;
        }
        
        // Handle purchase logic here
        $this->telegram->sendMessage($chatId, "🛒 Purchase functionality coming soon!");
    }

    /**
     * Handle /payment command
     */
    private function handlePaymentCommand(array $message, array $params): void
    {
        $chatId = $message['chat']['id'];
        
        $paymentText = "💳 **Payment Options**\n\n";
        $paymentText .= "Choose your preferred payment method:";
        
        $this->telegram->sendMessage($chatId, $paymentText, [
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
                    ]
                ]
            ]
        ]);
    }

    /**
     * Handle /support command
     */
    private function handleSupportCommand(array $message): void
    {
        $chatId = $message['chat']['id'];
        
        $supportText = "🎧 **Support Center**\n\n";
        $supportText .= "Need help? We're here for you!\n\n";
        $supportText .= "📞 Contact our support team:\n";
        $supportText .= "📧 Email: support@example.com\n";
        $supportText .= "💬 Live Chat: Available 24/7\n\n";
        $supportText .= "🔍 Or browse our FAQ section.";
        
        $this->telegram->sendMessage($chatId, $supportText, [
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
                    ]
                ]
            ]
        ]);
    }

    /**
     * Handle /settings command
     */
    private function handleSettingsCommand(array $message): void
    {
        $chatId = $message['chat']['id'];
        
        $settingsText = "⚙️ **Settings**\n\n";
        $settingsText .= "Manage your account settings:";
        
        $this->telegram->sendMessage($chatId, $settingsText, [
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
                    ]
                ]
            ]
        ]);
    }

    /**
     * Handle unknown command
     */
    private function handleUnknownCommand(array $message): void
    {
        $chatId = $message['chat']['id'];
        
        $this->telegram->sendMessage($chatId, "❓ Unknown command. Use /help to see available commands.");
    }

    /**
     * Parse command from message text
     */
    private function parseCommand(string $text): array
    {
        $parts = explode(' ', trim($text));
        $command = array_shift($parts);
        
        return [
            'command' => $command,
            'params' => $parts
        ];
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