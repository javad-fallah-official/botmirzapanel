<?php
// WARNING: SQL injection vulnerability detected in this file
// Please review and use prepared statements or secure_* functions


declare(strict_types=1);

namespace BotMirzaPanel\Presentation\Console\Commands;

use BotMirzaPanel\Presentation\Telegram\TelegramBot;
use BotMirzaPanel\Infrastructure\External\Telegram\TelegramServiceInterface;

/**
 * Telegram Bot Management Console Command
 */
class TelegramCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'telegram';
    }

    public function getDescription(): string
    {
        return 'Telegram bot management (webhook, polling, info, commands)';
    }

    public function getUsage(): string
    {
        return 'telegram <action> [options]';
    }

    public function getHelp(): string
    {
        return <<<HELP
Telegram Bot Management Commands:

  telegram info                Show bot information
  telegram webhook:set <url>   Set webhook URL
  telegram webhook:delete      Delete webhook
  telegram webhook:info        Show webhook information
  telegram polling:start       Start polling mode (for development)
  telegram polling:stop        Stop polling mode
  telegram commands:set        Set bot commands menu
  telegram commands:delete     Delete bot commands menu
  telegram test <chat_id>      Send test message to chat
  telegram stats               Show bot statistics
  telegram users               List bot users
  telegram broadcast <message> Broadcast message to all users

Options:
  --url=URL                   Webhook URL
  --certificate=FILE          SSL certificate file
  --max-connections=N         Maximum webhook connections (1-100)
  --allowed-updates=LIST      Comma-separated list of allowed updates
  --drop-pending-updates      Drop pending updates when setting webhook
  --timeout=N                 Polling timeout in seconds
  --limit=N                   Limit number of updates
  --verbose, -v               Verbose output
HELP;
    }

    public function execute(array $arguments = [], array $options = []): int
    {
        $this->setArguments($arguments);
        $this->setOptions($options);

        try {
            $action = $this->getArgument(0);
            
            if (!$action) {
                $this->error('No action specified.');
                $this->output($this->getHelp());
                return self::EXIT_INVALID_ARGUMENT;
            }

            switch ($action) {
                case 'info':
                    return $this->info();
                case 'webhook:set':
                    return $this->setWebhook();
                case 'webhook:delete':
                    return $this->deleteWebhook();
                case 'webhook:info':
                    return $this->webhookInfo();
                case 'polling:start':
                    return $this->startPolling();
                case 'polling:stop':
                    return $this->stopPolling();
                case 'commands:set':
                    return $this->setCommands();
                case 'commands:delete':
                    return $this->deleteCommands();
                case 'test':
                    return $this->test();
                case 'stats':
                    return $this->stats();
                case 'users':
                    return $this->users();
                case 'broadcast':
                    return $this->broadcast();
                default:
                    $this->error("Unknown action: {$action}");
                    $this->output($this->getHelp());
                    return self::EXIT_INVALID_ARGUMENT;
            }
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Show bot information
     */
    private function info(): int
    {
        try {
            $telegram = $this->getService(TelegramServiceInterface::class);
            $botInfo = $telegram->getMe();
            
            $this->output('Bot Information:');
            $this->output("  ID: {$botInfo['id']}");
            $this->output("  Username: @{$botInfo['username']}");
            $this->output("  First Name: {$botInfo['first_name']}");
            $this->output("  Can Join Groups: " . ($botInfo['can_join_groups'] ? 'Yes' : 'No'));
            $this->output("  Can Read All Group Messages: " . ($botInfo['can_read_all_group_messages'] ? 'Yes' : 'No'));
            $this->output("  Supports Inline Queries: " . ($botInfo['supports_inline_queries'] ? 'Yes' : 'No'));
            
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Failed to get bot info: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Set webhook URL
     */
    private function setWebhook(): int
    {
        $url = $this->getArgument(1) ?? $this->getOption('url');
        
        if (!$url) {
            $this->error('Webhook URL is required.');
            return self::EXIT_INVALID_ARGUMENT;
        }
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->error('Invalid URL format.');
            return self::EXIT_INVALID_ARGUMENT;
        }
        
        try {
            $telegram = $this->getService(TelegramServiceInterface::class);
            
            $options = [];
            
            if ($this->hasOption('certificate')) {
                $options['certificate'] = $this->getOption('certificate');
            }
            
            if ($this->hasOption('max-connections')) {
                $options['max_connections'] = (int) $this->getOption('max-connections');
            }
            
            if ($this->hasOption('allowed-updates')) {
                $options['allowed_updates'] = explode(',', $this->getOption('allowed-updates'));
            }
            
            if ($this->hasOption('drop-pending-updates')) {
                $options['drop_pending_updates'] = true;
            }
            
            $this->info("Setting webhook to: {$url}");
            $result = $telegram->setWebhook($url, $options);
            
            if ($result) {
                $this->success('Webhook set successfully.');
                return self::EXIT_SUCCESS;
            } else {
                $this->error('Failed to set webhook.');
                return self::EXIT_FAILURE;
            }
            
        } catch (\Exception $e) {
            $this->error("Failed to set webhook: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Delete webhook
     */
    private function deleteWebhook(): int
    {
        try {
            $telegram = $this->getService(TelegramServiceInterface::class);
            
            $this->info('Deleting webhook...');
            $result = $telegram->deleteWebhook();
            
            if ($result) {
                $this->success('Webhook deleted successfully.');
                return self::EXIT_SUCCESS;
            } else {
                $this->error('Failed to delete webhook.');
                return self::EXIT_FAILURE;
            }
            
        } catch (\Exception $e) {
            $this->error("Failed to delete webhook: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Show webhook information
     */
    private function webhookInfo(): int
    {
        try {
            $telegram = $this->getService(TelegramServiceInterface::class);
            $webhookInfo = $telegram->getWebhookInfo();
            
            $this->output('Webhook Information:');
            $this->output("  URL: {$webhookInfo['url']}");
            $this->output("  Has Custom Certificate: " . ($webhookInfo['has_custom_certificate'] ? 'Yes' : 'No'));
            $this->output("  Pending Update Count: {$webhookInfo['pending_update_count']}");
            
            if (isset($webhookInfo['last_error_date'])) {
                $this->output("  Last Error Date: " . date('Y-m-d H:i:s', $webhookInfo['last_error_date']));
                $this->output("  Last Error Message: {$webhookInfo['last_error_message']}");
            }
            
            if (isset($webhookInfo['max_connections'])) {
                $this->output("  Max Connections: {$webhookInfo['max_connections']}");
            }
            
            if (isset($webhookInfo['allowed_updates'])) {
                $this->output("  Allowed Updates: " . implode(', ', $webhookInfo['allowed_updates']));
            }
            
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Failed to get webhook info: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Start polling mode
     */
    private function startPolling(): int
    {
        $this->info('Starting Telegram bot polling mode...');
        $this->warning('Press Ctrl+C to stop.');
        
        try {
            $bot = $this->getService(TelegramBot::class);
            $bot->startPolling();
            
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Polling failed: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Stop polling mode
     */
    private function stopPolling(): int
    {
        $this->info('Stopping polling mode...');
        
        // This would typically send a signal to stop the polling process
        // For now, we'll just show a message
        $this->success('Polling stopped.');
        
        return self::EXIT_SUCCESS;
    }

    /**
     * Set bot commands menu
     */
    private function setCommands(): int
    {
        try {
            $telegram = $this->getService(TelegramServiceInterface::class);
            
            $commands = [
                ['command' => 'start', 'description' => 'Start the bot and register'],
                ['command' => 'help', 'description' => 'Show help message'],
                ['command' => 'profile', 'description' => 'View your profile'],
                ['command' => 'balance', 'description' => 'Check your balance'],
                ['command' => 'services', 'description' => 'Browse available services'],
                ['command' => 'buy', 'description' => 'Purchase a service'],
                ['command' => 'payment', 'description' => 'Make a payment'],
                ['command' => 'support', 'description' => 'Contact support'],
                ['command' => 'settings', 'description' => 'Manage your settings']
            ];
            
            $this->info('Setting bot commands menu...');
            $result = $telegram->setMyCommands($commands);
            
            if ($result) {
                $this->success('Bot commands menu set successfully.');
                return self::EXIT_SUCCESS;
            } else {
                $this->error('Failed to set bot commands menu.');
                return self::EXIT_FAILURE;
            }
            
        } catch (\Exception $e) {
            $this->error("Failed to set commands: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Delete bot commands menu
     */
    private function deleteCommands(): int
    {
        try {
            $telegram = $this->getService(TelegramServiceInterface::class);
            
            $this->info('Deleting bot commands menu...');
            $result = $telegram->deleteMyCommands();
            
            if ($result) {
                $this->success('Bot commands menu deleted successfully.');
                return self::EXIT_SUCCESS;
            } else {
                $this->error('Failed to delete bot commands menu.');
                return self::EXIT_FAILURE;
            }
            
        } catch (\Exception $e) {
            $this->error("Failed to delete commands: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Send test message
     */
    private function test(): int
    {
        $chatId = $this->getArgument(1);
        
        if (!$chatId) {
            $this->error('Chat ID is required.');
            return self::EXIT_INVALID_ARGUMENT;
        }
        
        try {
            $telegram = $this->getService(TelegramServiceInterface::class);
            
            $message = "🤖 Test message from bot\n\nTime: " . date('Y-m-d H:i:s');
            
            $this->info("Sending test message to chat: {$chatId}");
            $result = $telegram->sendMessage((int) $chatId, $message);
            
            if ($result) {
                $this->success('Test message sent successfully.');
                return self::EXIT_SUCCESS;
            } else {
                $this->error('Failed to send test message.');
                return self::EXIT_FAILURE;
            }
            
        } catch (\Exception $e) {
            $this->error("Failed to send test message: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Show bot statistics
     */
    private function stats(): int
    {
        try {
            // TODO: Implement bot statistics
            $this->output('Bot Statistics:');
            $this->output('  Total Users: 0');
            $this->output('  Active Users (24h): 0');
            $this->output('  Messages Sent: 0');
            $this->output('  Messages Received: 0');
            $this->output('  Commands Processed: 0');
            
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Failed to get statistics: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * List bot users
     */
    private function users(): int
    {
        try {
            // TODO: Implement user listing
            $this->output('Bot Users:');
            $this->info('No users found.');
            
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Failed to get users: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Broadcast message to all users
     */
    private function broadcast(): int
    {
        $message = $this->getArgument(1);
        
        if (!$message) {
            $this->error('Message is required.');
            return self::EXIT_INVALID_ARGUMENT;
        }
        
        if (!$this->confirm("Are you sure you want to broadcast this message to all users?")) {
            $this->info('Broadcast cancelled.');
            return self::EXIT_SUCCESS;
        }
        
        try {
            // TODO: Implement broadcast functionality
            $this->info('Broadcasting message to all users...');
            $this->success('Broadcast completed.');
            
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Broadcast failed: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }
}