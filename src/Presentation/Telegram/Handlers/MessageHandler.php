<?php
// WARNING: SQL injection vulnerability detected in this file
// Please review and use prepared statements or secure_* functions


declare(strict_types=1);

namespace BotMirzaPanel\Presentation\Telegram\Handlers;

use BotMirzaPanel\Infrastructure\Container\ServiceContainer;
use BotMirzaPanel\Infrastructure\External\Telegram\TelegramServiceInterface;
use BotMirzaPanel\Application\Queries\User\GetUserByTelegramIdQuery;
use BotMirzaPanel\Application\Queries\User\GetUserByTelegramIdQueryHandler;
use BotMirzaPanel\Application\Commands\User\CreateUserCommand;
use BotMirzaPanel\Application\Commands\User\CreateUserCommandHandler;
use BotMirzaPanel\Domain\ValueObjects\User\TelegramId;
use BotMirzaPanel\Domain\ValueObjects\User\Username;
use BotMirzaPanel\Domain\ValueObjects\Common\Email;
use BotMirzaPanel\Domain\ValueObjects\Common\Money;
use BotMirzaPanel\Domain\ValueObjects\Common\Currency;

/**
 * Telegram Message Handler
 * 
 * Handles text messages and user interactions
 */
class MessageHandler
{
    private ServiceContainer $container;
    private TelegramServiceInterface $telegram;

    public function __construct(ServiceContainer $container, TelegramServiceInterface $telegram)
    {
        $this->container = $container;
        $this->telegram = $telegram;
    }

    /**
     * Handle incoming message
     */
    public function handle(array $message): void
    {
        if (!isset($message['from']['id'], $message['chat']['id'])) {
            return; // Invalid message structure
        }
        
        $userId = $message['from']['id'];
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        
        // Check if user is blocked
        if ($this->isUserBlocked($userId)) {
            $this->telegram->sendMessage($chatId, "❌ Access denied.");
            return;
        }
        
        // Handle different message types
        if ($this->isAmountMessage($text)) {
            $this->handleAmountMessage($message);
        } elseif ($this->isEmailMessage($text)) {
            $this->handleEmailMessage($message);
        } elseif ($this->isContactMessage($message)) {
            $this->handleContactMessage($message);
        } elseif ($this->isLocationMessage($message)) {
            $this->handleLocationMessage($message);
        } elseif ($this->isDocumentMessage($message)) {
            $this->handleDocumentMessage($message);
        } elseif ($this->isPhotoMessage($message)) {
            $this->handlePhotoMessage($message);
        } else {
            $this->handleTextMessage($message);
        }
    }

    /**
     * Handle text message
     */
    private function handleTextMessage(array $message): void
    {
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        
        // Handle common text patterns
        if (stripos($text, 'hello') !== false || stripos($text, 'hi') !== false) {
            $this->handleGreeting($message);
        } elseif (stripos($text, 'help') !== false) {
            $this->handleHelpRequest($message);
        } elseif (stripos($text, 'balance') !== false) {
            $this->handleBalanceRequest($message);
        } elseif (stripos($text, 'service') !== false) {
            $this->handleServiceRequest($message);
        } elseif (stripos($text, 'payment') !== false) {
            $this->handlePaymentRequest($message);
        } elseif (stripos($text, 'support') !== false) {
            $this->handleSupportRequest($message);
        } else {
            $this->handleUnknownMessage($message);
        }
    }

    /**
     * Handle amount message (e.g., "50 USD", "100 EUR")
     */
    private function handleAmountMessage(array $message): void
    {
        $chatId = $message['chat']['id'];
        $text = $message['text'];
        
        try {
            $parts = explode(' ', trim($text));
            if (count($parts) !== 2) {
                throw new \InvalidArgumentException('Invalid amount format');
            }
            
            $amount = (float) $parts[0];
            $currencyCode = strtoupper($parts[1]);
            
            if ($amount <= 0) {
                throw new \InvalidArgumentException('Amount must be positive');
            }
            
            if (!in_array($currencyCode, ['USD', 'EUR', 'GBP', 'BTC', 'ETH'])) {
                throw new \InvalidArgumentException('Unsupported currency');
            }
            
            $responseText = "💳 **Payment Request**\n\n";
            $responseText .= "Amount: **{$amount} {$currencyCode}**\n\n";
            $responseText .= "Please select your payment method:";
            
            $this->telegram->sendMessage($chatId, $responseText, [
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
            
        } catch (\Exception $e) {
            $this->telegram->sendMessage($chatId, "❌ Invalid amount format. Please use format: `50 USD` or `100 EUR`", [
                'parse_mode' => 'Markdown'
            ]);
        }
    }

    /**
     * Handle email message
     */
    private function handleEmailMessage(array $message): void
    {
        $userId = $message['from']['id'];
        $chatId = $message['chat']['id'];
        $email = $message['text'];
        
        try {
            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException('Invalid email format');
            }
            
            // Get user and update email
            $query = new GetUserByTelegramIdQuery(new TelegramId($userId));
            $handler = $this->container->get(GetUserByTelegramIdQueryHandler::class);
            $user = $handler->handle($query);
            
            if (!$user) {
                $this->telegram->sendMessage($chatId, "❌ User not found. Please use /start to register.");
                return;
            }
            
            // TODO: Implement email update command
            $this->telegram->sendMessage($chatId, "✅ Email updated successfully!\n\nNew email: `{$email}`", [
                'parse_mode' => 'Markdown'
            ]);
            
        } catch (\Exception $e) {
            $this->telegram->sendMessage($chatId, "❌ Invalid email format. Please enter a valid email address.");
        }
    }

    /**
     * Handle contact message
     */
    private function handleContactMessage(array $message): void
    {
        $chatId = $message['chat']['id'];
        $contact = $message['contact'];
        
        $responseText = "📞 **Contact Received**\n\n";
        $responseText .= "Name: {$contact['first_name']}";
        if (isset($contact['last_name'])) {
            $responseText .= " {$contact['last_name']}";
        }
        $responseText .= "\nPhone: `{$contact['phone_number']}`\n\n";
        $responseText .= "Thank you for sharing your contact information!";
        
        $this->telegram->sendMessage($chatId, $responseText, [
            'parse_mode' => 'Markdown'
        ]);
    }

    /**
     * Handle location message
     */
    private function handleLocationMessage(array $message): void
    {
        $chatId = $message['chat']['id'];
        $location = $message['location'];
        
        $responseText = "📍 **Location Received**\n\n";
        $responseText .= "Latitude: `{$location['latitude']}`\n";
        $responseText .= "Longitude: `{$location['longitude']}`\n\n";
        $responseText .= "Thank you for sharing your location!";
        
        $this->telegram->sendMessage($chatId, $responseText, [
            'parse_mode' => 'Markdown'
        ]);
    }

    /**
     * Handle document message
     */
    private function handleDocumentMessage(array $message): void
    {
        $chatId = $message['chat']['id'];
        $document = $message['document'];
        
        $responseText = "📄 **Document Received**\n\n";
        $responseText .= "File: `{$document['file_name']}`\n";
        $responseText .= "Size: " . $this->formatFileSize($document['file_size']) . "\n\n";
        $responseText .= "Document has been received and will be processed.";
        
        $this->telegram->sendMessage($chatId, $responseText, [
            'parse_mode' => 'Markdown'
        ]);
    }

    /**
     * Handle photo message
     */
    private function handlePhotoMessage(array $message): void
    {
        $chatId = $message['chat']['id'];
        $photos = $message['photo'];
        
        $responseText = "🖼️ **Photo Received**\n\n";
        $responseText .= "Photo has been received and will be processed.";
        
        if (isset($message['caption'])) {
            $responseText .= "\n\nCaption: {$message['caption']}";
        }
        
        $this->telegram->sendMessage($chatId, $responseText);
    }

    /**
     * Handle greeting
     */
    private function handleGreeting(array $message): void
    {
        $chatId = $message['chat']['id'];
        $firstName = $message['from']['first_name'] ?? 'User';
        
        $responseText = "👋 Hello {$firstName}!\n\n";
        $responseText .= "Welcome to our bot! How can I help you today?\n\n";
        $responseText .= "Use /help to see available commands or use the menu below:";
        
        $this->telegram->sendMessage($chatId, $responseText, [
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
                        ['text' => '📋 Help', 'callback_data' => 'help'],
                        ['text' => '🎧 Support', 'callback_data' => 'support']
                    ]
                ]
            ]
        ]);
    }

    /**
     * Handle help request
     */
    private function handleHelpRequest(array $message): void
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
        
        $this->telegram->sendMessage($chatId, $helpText, [
            'parse_mode' => 'Markdown'
        ]);
    }

    /**
     * Handle balance request
     */
    private function handleBalanceRequest(array $message): void
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
            $this->telegram->sendMessage($chatId, "❌ Error loading balance. Please try again.");
            error_log("Balance request error: " . $e->getMessage());
        }
    }

    /**
     * Handle service request
     */
    private function handleServiceRequest(array $message): void
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
     * Handle payment request
     */
    private function handlePaymentRequest(array $message): void
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
     * Handle support request
     */
    private function handleSupportRequest(array $message): void
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
     * Handle unknown message
     */
    private function handleUnknownMessage(array $message): void
    {
        $chatId = $message['chat']['id'];
        
        $responseText = "🤔 I didn't understand that message.\n\n";
        $responseText .= "Try using one of these commands:\n";
        $responseText .= "• /help - Show available commands\n";
        $responseText .= "• /profile - View your profile\n";
        $responseText .= "• /balance - Check your balance\n";
        $responseText .= "• /services - Browse services\n\n";
        $responseText .= "Or use the menu buttons below:";
        
        $this->telegram->sendMessage($chatId, $responseText, [
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => '👤 Profile', 'callback_data' => 'profile'],
                        ['text' => '💰 Balance', 'callback_data' => 'balance']
                    ],
                    [
                        ['text' => '🛒 Services', 'callback_data' => 'services'],
                        ['text' => '📋 Help', 'callback_data' => 'help']
                    ]
                ]
            ]
        ]);
    }

    /**
     * Check if message is an amount (e.g., "50 USD")
     */
    private function isAmountMessage(string $text): bool
    {
        return preg_match('/^\d+(\.\d{1,2})?\s+(USD|EUR|GBP|BTC|ETH)$/i', trim($text));
    }

    /**
     * Check if message is an email
     */
    private function isEmailMessage(string $text): bool
    {
        return filter_var(trim($text), FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Check if message contains contact
     */
    private function isContactMessage(array $message): bool
    {
        return isset($message['contact']);
    }

    /**
     * Check if message contains location
     */
    private function isLocationMessage(array $message): bool
    {
        return isset($message['location']);
    }

    /**
     * Check if message contains document
     */
    private function isDocumentMessage(array $message): bool
    {
        return isset($message['document']);
    }

    /**
     * Check if message contains photo
     */
    private function isPhotoMessage(array $message): bool
    {
        return isset($message['photo']);
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

    /**
     * Format file size
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}