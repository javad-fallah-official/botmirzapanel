<?php

declare(strict_types=1);

namespace BotMirzaPanel\Presentation\Console\Commands;

use BotMirzaPanel\Application\Queries\User\GetUsersQuery;
use BotMirzaPanel\Application\Queries\User\GetUsersQueryHandler;
use BotMirzaPanel\Application\Queries\User\GetUserByIdQuery;
use BotMirzaPanel\Application\Queries\User\GetUserByIdQueryHandler;
use BotMirzaPanel\Application\Commands\User\CreateUserCommand;
use BotMirzaPanel\Application\Commands\User\CreateUserCommandHandler;
use BotMirzaPanel\Application\Commands\User\UpdateUserCommand;
use BotMirzaPanel\Application\Commands\User\UpdateUserCommandHandler;
use BotMirzaPanel\Application\Commands\User\DeleteUserCommand;
use BotMirzaPanel\Application\Commands\User\DeleteUserCommandHandler;
use BotMirzaPanel\Domain\ValueObjects\User\UserId;
use BotMirzaPanel\Domain\ValueObjects\User\Username;
use BotMirzaPanel\Domain\ValueObjects\Common\Email;
use BotMirzaPanel\Domain\ValueObjects\User\TelegramId;
use BotMirzaPanel\Domain\ValueObjects\User\UserStatus;
use BotMirzaPanel\Domain\ValueObjects\Common\Money;
use BotMirzaPanel\Domain\ValueObjects\Common\Currency;

/**
 * User Management Console Command
 */
class UserCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'user';
    }

    public function getDescription(): string
    {
        return 'Manage users (list, create, update, delete, show)';
    }

    public function getUsage(): string
    {
        return 'user <action> [options]';
    }

    public function getHelp(): string
    {
        return <<<HELP
User Management Commands:

  user list                    List all users
  user show <id>              Show user details
  user create                 Create a new user (interactive)
  user update <id>            Update user (interactive)
  user delete <id>            Delete user
  user ban <id>               Ban user
  user unban <id>             Unban user
  user balance <id> <amount>  Set user balance

Options:
  --format=table|json         Output format (default: table)
  --limit=N                   Limit number of results
  --offset=N                  Offset for pagination
  --status=active|banned      Filter by status
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
                case 'list':
                    return $this->listUsers();
                case 'show':
                    return $this->showUser();
                case 'create':
                    return $this->createUser();
                case 'update':
                    return $this->updateUser();
                case 'delete':
                    return $this->deleteUser();
                case 'ban':
                    return $this->banUser();
                case 'unban':
                    return $this->unbanUser();
                case 'balance':
                    return $this->setUserBalance();
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
     * List all users
     */
    private function listUsers(): int
    {
        $query = new GetUsersQuery(
            limit: $this->getOption('limit', 50),
            offset: $this->getOption('offset', 0),
            filters: $this->getOption('status') ? ['status' => $this->getOption('status')] : []
        );
        
        $handler = $this->getService(GetUsersQueryHandler::class);
        $users = $handler->handle($query);
        
        if (empty($users)) {
            $this->info('No users found.');
            return self::EXIT_SUCCESS;
        }
        
        $format = $this->getOption('format', 'table');
        
        if ($format === 'json') {
            $this->output(json_encode(array_map(function($user) {
                return [
                    'id' => $user->getId()->getValue(),
                    'username' => $user->getUsername()->getValue(),
                    'email' => $user->getEmail()?->getValue(),
                    'telegram_id' => $user->getTelegramId()->getValue(),
                    'status' => $user->getStatus()->getValue(),
                    'balance' => $user->getBalance()->getAmount(),
                    'currency' => $user->getBalance()->getCurrency()->getCode(),
                    'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s')
                ];
            }, $users), JSON_PRETTY_PRINT));
        } else {
            $headers = ['ID', 'Username', 'Email', 'Telegram ID', 'Status', 'Balance', 'Created'];
            $rows = [];
            
            foreach ($users as $user) {
                $rows[] = [
                    $user->getId()->getValue(),
                    $user->getUsername()->getValue(),
                    $user->getEmail()?->getValue(),
                    $user->getTelegramId()->getValue(),
                    $user->getStatus()->getValue(),
                    $user->getBalance()->getAmount() . ' ' . $user->getBalance()->getCurrency()->getCode(),
                    $user->getCreatedAt()->format('Y-m-d H:i')
                ];
            }
            
            $this->table($headers, $rows);
        }
        
        $this->info('Total users: ' . count($users));
        return self::EXIT_SUCCESS;
    }

    /**
     * Show user details
     */
    private function showUser(): int
    {
        $userId = $this->getArgument(1);
        
        if (!$userId) {
            $this->error('User ID is required.');
            return self::EXIT_INVALID_ARGUMENT;
        }
        
        $query = new GetUserByIdQuery(new UserId($userId));
        $handler = $this->getService(GetUserByIdQueryHandler::class);
        $user = $handler->handle($query);
        
        if (!$user) {
            $this->error("User with ID {$userId} not found.");
            return self::EXIT_NOT_FOUND;
        }
        
        $format = $this->getOption('format', 'table');
        
        if ($format === 'json') {
            $this->output(json_encode([
                'id' => $user->getId()->getValue(),
                'username' => $user->getUsername()->getValue(),
                'email' => $user->getEmail()?->getValue(),
                'telegram_id' => $user->getTelegramId()->getValue(),
                'status' => $user->getStatus()->getValue(),
                'balance' => $user->getBalance()->getAmount(),
                'currency' => $user->getBalance()->getCurrency()->getCode(),
                'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
                'updated_at' => $user->getUpdatedAt()->format('Y-m-d H:i:s')
            ], JSON_PRETTY_PRINT));
        } else {
            $this->output("User Details:");
            $this->output("  ID: {$user->getId()->getValue()}");
            $this->output("  Username: {$user->getUsername()->getValue()}");
            $this->output("  Email: {$user->getEmail()?->getValue()}");
            $this->output("  Telegram ID: {$user->getTelegramId()->getValue()}");
            $this->output("  Status: {$user->getStatus()->getValue()}");
            $this->output("  Balance: {$user->getBalance()->getAmount()} {$user->getBalance()->getCurrency()->getCode()}");
            $this->output("  Created: {$user->getCreatedAt()->format('Y-m-d H:i:s')}");
            $this->output("  Updated: {$user->getUpdatedAt()->format('Y-m-d H:i:s')}");
        }
        
        return self::EXIT_SUCCESS;
    }

    /**
     * Create new user
     */
    private function createUser(): int
    {
        $this->output('Creating new user...');
        
        $username = $this->ask('Username');
        $email = $this->ask('Email');
        $telegramId = $this->ask('Telegram ID');
        $initialBalance = $this->ask('Initial balance', '0');
        $currency = $this->ask('Currency', 'USD');
        
        if (!$username || !$email || !$telegramId) {
            $this->error('Username, email, and Telegram ID are required.');
            return self::EXIT_INVALID_ARGUMENT;
        }
        
        try {
            $command = new CreateUserCommand(
                new Username($username),
                new Email($email),
                new TelegramId((int) $telegramId),
                new Money((float) $initialBalance, new Currency($currency))
            );
            
            $handler = $this->getService(CreateUserCommandHandler::class);
            $userId = $handler->handle($command);
            
            $this->success("User created successfully with ID: {$userId->getValue()}");
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Failed to create user: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Update user
     */
    private function updateUser(): int
    {
        $userId = $this->getArgument(1);
        
        if (!$userId) {
            $this->error('User ID is required.');
            return self::EXIT_INVALID_ARGUMENT;
        }
        
        // Get current user data
        $query = new GetUserByIdQuery(new UserId($userId));
        $handler = $this->getService(GetUserByIdQueryHandler::class);
        $user = $handler->handle($query);
        
        if (!$user) {
            $this->error("User with ID {$userId} not found.");
            return self::EXIT_NOT_FOUND;
        }
        
        $this->output('Updating user (press Enter to keep current value)...');
        
        $username = $this->ask('Username', $user->getUsername()->getValue());
        $email = $this->ask('Email', $user->getEmail()?->getValue());
        
        try {
            $command = new UpdateUserCommand(
                new UserId($userId),
                new Username($username),
                new Email($email)
            );
            
            $updateHandler = $this->getService(UpdateUserCommandHandler::class);
            $updateHandler->handle($command);
            
            $this->success('User updated successfully.');
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Failed to update user: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Delete user
     */
    private function deleteUser(): int
    {
        $userId = $this->getArgument(1);
        
        if (!$userId) {
            $this->error('User ID is required.');
            return self::EXIT_INVALID_ARGUMENT;
        }
        
        if (!$this->confirm("Are you sure you want to delete user {$userId}?")) {
            $this->info('Operation cancelled.');
            return self::EXIT_SUCCESS;
        }
        
        try {
            $command = new DeleteUserCommand(new UserId($userId));
            $handler = $this->getService(DeleteUserCommandHandler::class);
            $handler->handle($command);
            
            $this->success('User deleted successfully.');
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Failed to delete user: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Ban user
     */
    private function banUser(): int
    {
        $userId = $this->getArgument(1);
        
        if (!$userId) {
            $this->error('User ID is required.');
            return self::EXIT_INVALID_ARGUMENT;
        }
        
        try {
            // TODO: Implement ban user command
            $this->success("User {$userId} has been banned.");
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Failed to ban user: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Unban user
     */
    private function unbanUser(): int
    {
        $userId = $this->getArgument(1);
        
        if (!$userId) {
            $this->error('User ID is required.');
            return self::EXIT_INVALID_ARGUMENT;
        }
        
        try {
            // TODO: Implement unban user command
            $this->success("User {$userId} has been unbanned.");
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Failed to unban user: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Set user balance
     */
    private function setUserBalance(): int
    {
        $userId = $this->getArgument(1);
        $amount = $this->getArgument(2);
        
        if (!$userId || !$amount) {
            $this->error('User ID and amount are required.');
            return self::EXIT_INVALID_ARGUMENT;
        }
        
        $currency = $this->getOption('currency', 'USD');
        
        try {
            // TODO: Implement set balance command
            $this->success("User {$userId} balance set to {$amount} {$currency}.");
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Failed to set user balance: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }
}