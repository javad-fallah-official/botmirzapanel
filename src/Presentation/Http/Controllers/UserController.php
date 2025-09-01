<?php

declare(strict_types=1);

namespace BotMirzaPanel\Presentation\Http\Controllers;

use BotMirzaPanel\Application\Commands\User\CreateUserCommand;
use BotMirzaPanel\Application\Commands\User\CreateUserCommandHandler;
use BotMirzaPanel\Application\Commands\User\UpdateUserCommand;
use BotMirzaPanel\Application\Commands\User\UpdateUserCommandHandler;
use BotMirzaPanel\Application\Commands\User\DeleteUserCommand;
use BotMirzaPanel\Application\Commands\User\DeleteUserCommandHandler;
use BotMirzaPanel\Application\Queries\User\GetUserByIdQuery;
use BotMirzaPanel\Application\Queries\User\GetUserByIdQueryHandler;
use BotMirzaPanel\Application\Queries\User\GetUsersQuery;
use BotMirzaPanel\Application\Queries\User\GetUsersQueryHandler;
use BotMirzaPanel\Domain\ValueObjects\User\UserId;
use BotMirzaPanel\Domain\ValueObjects\User\Username;
use BotMirzaPanel\Domain\ValueObjects\Common\Email;
use BotMirzaPanel\Domain\ValueObjects\User\TelegramId;
use BotMirzaPanel\Domain\ValueObjects\Common\Money;
use BotMirzaPanel\Domain\ValueObjects\Common\Currency;

/**
 * User Controller
 * 
 * Handles HTTP requests for user management
 */
class UserController extends BaseController
{
    /**
     * Get all users
     */
    public function index(): array
    {
        try {
            $this->requireAuth();
            $this->requirePermission('users.view');
            
            $data = $this->getRequestData();
            
            $query = new GetUsersQuery(
                limit: (int)($data['limit'] ?? 20),
                offset: (int)($data['offset'] ?? 0),
                search: $data['search'] ?? null,
                status: $data['status'] ?? null
            );
            
            $handler = $this->container->get(GetUsersQueryHandler::class);
            $result = $handler->handle($query);
            
            return $this->success([
                'users' => array_map(fn($user) => $this->formatUser($user), $result->getUsers()),
                'total' => $result->getTotal(),
                'limit' => $query->getLimit(),
                'offset' => $query->getOffset()
            ]);
            
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get user by ID
     */
    public function show(int $id): array
    {
        try {
            $this->requireAuth();
            $this->requirePermission('users.view');
            
            $query = new GetUserByIdQuery(new UserId($id));
            $handler = $this->container->get(GetUserByIdQueryHandler::class);
            $user = $handler->handle($query);
            
            if (!$user) {
                return $this->error('User not found', 404);
            }
            
            return $this->success(['user' => $this->formatUser($user)]);
            
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Create new user
     */
    public function store(): array
    {
        try {
            $this->requireAuth();
            $this->requirePermission('users.create');
            
            $data = $this->getRequestData();
            
            $validatedData = $this->validate($data, [
                'username' => ['required' => true, 'type' => 'string', 'min_length' => 3, 'max_length' => 50],
                'email' => ['required' => true, 'type' => 'email'],
                'telegram_id' => ['required' => true, 'type' => 'integer'],
                'balance_amount' => ['type' => 'integer'],
                'balance_currency' => ['type' => 'string']
            ]);
            
            $command = new CreateUserCommand(
                username: new Username($validatedData['username']),
                email: new Email($validatedData['email']),
                telegramId: new TelegramId($validatedData['telegram_id']),
                balance: isset($validatedData['balance_amount']) ? 
                    new Money(
                        $validatedData['balance_amount'],
                        new Currency($validatedData['balance_currency'] ?? 'USD')
                    ) : null
            );
            
            $handler = $this->container->get(CreateUserCommandHandler::class);
            $userId = $handler->handle($command);
            
            return $this->success(['user_id' => $userId->getValue()], 'User created successfully');
            
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update user
     */
    public function update(int $id): array
    {
        try {
            $this->requireAuth();
            $this->requirePermission('users.update');
            
            $data = $this->getRequestData();
            
            $validatedData = $this->validate($data, [
                'username' => ['type' => 'string', 'min_length' => 3, 'max_length' => 50],
                'email' => ['type' => 'email'],
                'telegram_id' => ['type' => 'integer'],
                'balance_amount' => ['type' => 'integer'],
                'balance_currency' => ['type' => 'string']
            ]);
            
            $command = new UpdateUserCommand(
                userId: new UserId($id),
                username: isset($validatedData['username']) ? new Username($validatedData['username']) : null,
                email: isset($validatedData['email']) ? new Email($validatedData['email']) : null,
                telegramId: isset($validatedData['telegram_id']) ? new TelegramId($validatedData['telegram_id']) : null,
                balance: isset($validatedData['balance_amount']) ? 
                    new Money(
                        $validatedData['balance_amount'],
                        new Currency($validatedData['balance_currency'] ?? 'USD')
                    ) : null
            );
            
            $handler = $this->container->get(UpdateUserCommandHandler::class);
            $handler->handle($command);
            
            return $this->success([], 'User updated successfully');
            
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Delete user
     */
    public function destroy(int $id): array
    {
        try {
            $this->requireAuth();
            $this->requirePermission('users.delete');
            
            $command = new DeleteUserCommand(new UserId($id));
            $handler = $this->container->get(DeleteUserCommandHandler::class);
            $handler->handle($command);
            
            return $this->success([], 'User deleted successfully');
            
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get user statistics
     */
    public function statistics(): array
    {
        try {
            $this->requireAuth();
            $this->requirePermission('users.view');
            
            // This would typically use a dedicated query/handler
            // For now, return placeholder data
            return $this->success([
                'total_users' => 0,
                'active_users' => 0,
                'banned_users' => 0,
                'users_today' => 0,
                'users_this_month' => 0
            ]);
            
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Format user data for API response
     */
    private function formatUser(mixed $user): array
    {
        return [
            'id' => $user->getId()->getValue(),
            'username' => $user->getUsername()->getValue(),
            'email' => $user->getEmail()?->getValue(),
            'telegram_id' => $user->getTelegramId()->getValue(),
            'status' => $user->getStatus()->getValue(),
            'balance' => [
                'amount' => $user->getBalance()->getAmount(),
                'currency' => $user->getBalance()->getCurrency()->getCode()
            ],
            'created_at' => $user->getCreatedAt()->format('c'),
            'updated_at' => $user->getUpdatedAt()->format('c')
        ];
    }
}