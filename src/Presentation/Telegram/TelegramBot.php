<?php

declare(strict_types=1);

namespace BotMirzaPanel\Presentation\Telegram;

use BotMirzaPanel\Infrastructure\Container\ServiceContainer;
use BotMirzaPanel\Infrastructure\External\Telegram\TelegramServiceInterface;
use BotMirzaPanel\Presentation\Telegram\Handlers\CommandHandler;
use BotMirzaPanel\Presentation\Telegram\Handlers\CallbackHandler;
use BotMirzaPanel\Presentation\Telegram\Handlers\MessageHandler;

/**
 * Telegram Bot Main Class
 * 
 * Coordinates all Telegram bot operations and webhook processing
 */
class TelegramBot
{
    private ServiceContainer $container;
    private TelegramServiceInterface $telegram;
    private CommandHandler $commandHandler;
    private CallbackHandler $callbackHandler;
    private MessageHandler $messageHandler;

    public function __construct(ServiceContainer $container): void
    {
        $this->container = $container;
        $this->telegram = $container->get(TelegramServiceInterface::class);
        $this->commandHandler = new CommandHandler($container, $this->telegram);
        $this->callbackHandler = new CallbackHandler($container, $this->telegram);
        $this->messageHandler = new MessageHandler($container, $this->telegram);
    }

    /**
     * Process incoming webhook update
     */
    public function processUpdate(array $update): void
    {
        try {
            // Log incoming update for debugging
            error_log("Telegram update received: " . json_encode($update));
            
            // Handle different update types
            if (isset($update['message'])) {
                $this->handleMessage($update['message']);
            } elseif (isset($update['callback_query'])) {
                $this->handleCallbackQuery($update['callback_query']);
            } elseif (isset($update['inline_query'])) {
                $this->handleInlineQuery($update['inline_query']);
            } elseif (isset($update['chosen_inline_result'])) {
                $this->handleChosenInlineResult($update['chosen_inline_result']);
            } elseif (isset($update['channel_post'])) {
                $this->handleChannelPost($update['channel_post']);
            } elseif (isset($update['edited_message'])) {
                $this->handleEditedMessage($update['edited_message']);
            } elseif (isset($update['edited_channel_post'])) {
                $this->handleEditedChannelPost($update['edited_channel_post']);
            } elseif (isset($update['shipping_query'])) {
                $this->handleShippingQuery($update['shipping_query']);
            } elseif (isset($update['pre_checkout_query'])) {
                $this->handlePreCheckoutQuery($update['pre_checkout_query']);
            } elseif (isset($update['poll'])) {
                $this->handlePoll($update['poll']);
            } elseif (isset($update['poll_answer'])) {
                $this->handlePollAnswer($update['poll_answer']);
            } elseif (isset($update['my_chat_member'])) {
                $this->handleMyChatMember($update['my_chat_member']);
            } elseif (isset($update['chat_member'])) {
                $this->handleChatMember($update['chat_member']);
            } elseif (isset($update['chat_join_request'])) {
                $this->handleChatJoinRequest($update['chat_join_request']);
            } else {
                error_log("Unknown update type: " . json_encode($update));
            }
            
        } catch (\Exception $e) {
            error_log("Error processing Telegram update: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
        }
    }

    /**
     * Handle incoming message
     */
    private function handleMessage(array $message): void
    {
        // Check if message contains a command
        if (isset($message['text']) && strpos($message['text'], '/') === 0) {
            $this->commandHandler->handle($message);
        } else {
            $this->messageHandler->handle($message);
        }
    }

    /**
     * Handle callback query (inline keyboard button press)
     */
    private function handleCallbackQuery(array $callbackQuery): void
    {
        $this->callbackHandler->handle($callbackQuery);
    }

    /**
     * Handle inline query
     */
    private function handleInlineQuery(array $inlineQuery): void
    {
        $queryId = $inlineQuery['id'];
        $query = $inlineQuery['query'];
        
        // Prepare inline results
        $results = [];
        
        if (empty($query)) {
            // Default results when query is empty
            $results = [
                [
                    'type' => 'article',
                    'id' => '1',
                    'title' => '👤 My Profile',
                    'description' => 'View your profile information',
                    'input_message_content' => [
                        'message_text' => '/profile'
                    ]
                ],
                [
                    'type' => 'article',
                    'id' => '2',
                    'title' => '💰 My Balance',
                    'description' => 'Check your current balance',
                    'input_message_content' => [
                        'message_text' => '/balance'
                    ]
                ],
                [
                    'type' => 'article',
                    'id' => '3',
                    'title' => '🛒 Services',
                    'description' => 'Browse available services',
                    'input_message_content' => [
                        'message_text' => '/services'
                    ]
                ]
            ];
        } else {
            // Search-based results
            if (stripos($query, 'profile') !== false) {
                $results[] = [
                    'type' => 'article',
                    'id' => '1',
                    'title' => '👤 My Profile',
                    'description' => 'View your profile information',
                    'input_message_content' => [
                        'message_text' => '/profile'
                    ]
                ];
            }
            
            if (stripos($query, 'balance') !== false) {
                $results[] = [
                    'type' => 'article',
                    'id' => '2',
                    'title' => '💰 My Balance',
                    'description' => 'Check your current balance',
                    'input_message_content' => [
                        'message_text' => '/balance'
                    ]
                ];
            }
            
            if (stripos($query, 'service') !== false) {
                $results[] = [
                    'type' => 'article',
                    'id' => '3',
                    'title' => '🛒 Services',
                    'description' => 'Browse available services',
                    'input_message_content' => [
                        'message_text' => '/services'
                    ]
                ];
            }
            
            if (stripos($query, 'help') !== false) {
                $results[] = [
                    'type' => 'article',
                    'id' => '4',
                    'title' => '📋 Help',
                    'description' => 'Get help and see available commands',
                    'input_message_content' => [
                        'message_text' => '/help'
                    ]
                ];
            }
        }
        
        $this->telegram->answerInlineQuery($queryId, $results);
    }

    /**
     * Handle chosen inline result
     */
    private function handleChosenInlineResult(array $chosenInlineResult): void
    {
        // Log the chosen result for analytics
        error_log("Chosen inline result: " . json_encode($chosenInlineResult));
    }

    /**
     * Handle channel post
     */
    private function handleChannelPost(array $channelPost): void
    {
        // Handle channel posts if needed
        error_log("Channel post received: " . json_encode($channelPost));
    }

    /**
     * Handle edited message
     */
    private function handleEditedMessage(array $editedMessage): void
    {
        // Handle message edits if needed
        error_log("Message edited: " . json_encode($editedMessage));
    }

    /**
     * Handle edited channel post
     */
    private function handleEditedChannelPost(array $editedChannelPost): void
    {
        // Handle channel post edits if needed
        error_log("Channel post edited: " . json_encode($editedChannelPost));
    }

    /**
     * Handle shipping query
     */
    private function handleShippingQuery(array $shippingQuery): void
    {
        $queryId = $shippingQuery['id'];
        
        // Answer shipping query
        $this->telegram->answerShippingQuery($queryId, true, [
            [
                'id' => 'standard',
                'title' => 'Standard Delivery',
                'prices' => [
                    ['label' => 'Standard Delivery', 'amount' => 500] // $5.00
                ]
            ]
        ]);
    }

    /**
     * Handle pre-checkout query
     */
    private function handlePreCheckoutQuery(array $preCheckoutQuery): void
    {
        $queryId = $preCheckoutQuery['id'];
        
        // Answer pre-checkout query
        $this->telegram->answerPreCheckoutQuery($queryId, true);
    }

    /**
     * Handle poll
     */
    private function handlePoll(array $poll): void
    {
        // Handle poll updates if needed
        error_log("Poll update: " . json_encode($poll));
    }

    /**
     * Handle poll answer
     */
    private function handlePollAnswer(array $pollAnswer): void
    {
        // Handle poll answers if needed
        error_log("Poll answer: " . json_encode($pollAnswer));
    }

    /**
     * Handle my chat member update
     */
    private function handleMyChatMember(array $myChatMember): void
    {
        $chatId = $myChatMember['chat']['id'];
        $newStatus = $myChatMember['new_chat_member']['status'];
        $oldStatus = $myChatMember['old_chat_member']['status'];
        
        if ($newStatus === 'kicked' || $newStatus === 'left') {
            error_log("Bot was removed from chat: {$chatId}");
        } elseif ($oldStatus === 'kicked' && $newStatus === 'member') {
            error_log("Bot was added back to chat: {$chatId}");
            
            // Send welcome message
            $this->telegram->sendMessage($chatId, "👋 Hello! I'm back and ready to help!");
        }
    }

    /**
     * Handle chat member update
     */
    private function handleChatMember(array $chatMember): void
    {
        // Handle chat member updates if needed
        error_log("Chat member update: " . json_encode($chatMember));
    }

    /**
     * Handle chat join request
     */
    private function handleChatJoinRequest(array $chatJoinRequest): void
    {
        $chatId = $chatJoinRequest['chat']['id'];
        $userId = $chatJoinRequest['from']['id'];
        
        // Auto-approve join requests (you can add custom logic here)
        $this->telegram->approveChatJoinRequest($chatId, $userId);
        
        error_log("Chat join request approved for user: {$userId} in chat: {$chatId}");
    }

    /**
     * Set webhook URL
     */
    public function setWebhook(string $url, array $options = []): bool
    {
        return $this->telegram->setWebhook($url, $options);
    }

    /**
     * Delete webhook
     */
    public function deleteWebhook(): bool
    {
        return $this->telegram->deleteWebhook();
    }

    /**
     * Get webhook info
     */
    public function getWebhookInfo(): array
    {
        return $this->telegram->getWebhookInfo();
    }

    /**
     * Get bot information
     */
    public function getMe(): array
    {
        return $this->telegram->getMe();
    }

    /**
     * Send message to chat
     */
    public function sendMessage(int $chatId, string $text, array $options = []): array
    {
        return $this->telegram->sendMessage($chatId, $text, $options);
    }

    /**
     * Send photo to chat
     */
    public function sendPhoto(int $chatId, string $photo, array $options = []): array
    {
        return $this->telegram->sendPhoto($chatId, $photo, $options);
    }

    /**
     * Send document to chat
     */
    public function sendDocument(int $chatId, string $document, array $options = []): array
    {
        return $this->telegram->sendDocument($chatId, $document, $options);
    }

    /**
     * Get updates (for polling mode)
     */
    public function getUpdates(int $offset = 0, int $limit = 100, int $timeout = 0): array
    {
        return $this->telegram->getUpdates($offset, $limit, $timeout);
    }

    /**
     * Start polling mode (for development/testing)
     */
    public function startPolling(): void
    {
        $offset = 0;
        
        echo "Starting Telegram bot polling...\n";
        
        while (true) {
            try {
                $updates = $this->getUpdates($offset, 100, 30);
                
                foreach ($updates as $update) {
                    $this->processUpdate($update);
                    $offset = $update['update_id'] + 1;
                }
                
            } catch (\Exception $e) {
                error_log("Polling error: " . $e->getMessage());
                sleep(5); // Wait before retrying
            }
        }
    }

    /**
     * Stop polling mode
     */
    public function stopPolling(): void
    {
        echo "Stopping Telegram bot polling...\n";
        exit(0);
    }

    /**
     * Get service container
     */
    public function getContainer(): ServiceContainer
    {
        return $this->container;
    }

    /**
     * Get Telegram service
     */
    public function getTelegramService(): TelegramServiceInterface
    {
        return $this->telegram;
    }
}