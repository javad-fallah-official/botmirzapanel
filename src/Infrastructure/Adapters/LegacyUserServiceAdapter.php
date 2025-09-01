<?php

declare(strict_types=1);

namespace BotMirzaPanel\Infrastructure\Adapters;

use BotMirzaPanel\Domain\Entities\User\User;
use BotMirzaPanel\Domain\ValueObjects\User\UserId;
use BotMirzaPanel\Domain\ValueObjects\User\Username;
use BotMirzaPanel\Domain\ValueObjects\Common\Email;
use BotMirzaPanel\Domain\ValueObjects\User\UserStatus;
use BotMirzaPanel\Domain\ValueObjects\Common\PhoneNumber;
use BotMirzaPanel\Domain\ValueObjects\Common\Money;
use BotMirzaPanel\Domain\Services\User\UserService as DomainUserService;
use BotMirzaPanel\User\UserService as LegacyUserService;
use BotMirzaPanel\Application\Commands\User\CreateUserCommand;
use BotMirzaPanel\Application\Commands\User\CreateUserCommandHandler;
use BotMirzaPanel\Application\Queries\User\GetUserByIdQuery;
use BotMirzaPanel\Application\Queries\User\GetUserByIdQueryHandler;

/**
 * Adapter to bridge legacy UserService with new domain-driven architecture
 * This allows gradual migration while maintaining backward compatibility
 */
class LegacyUserServiceAdapter
{
    private LegacyUserService $legacyService;
    private DomainUserService $domainService;
    private CreateUserCommandHandler $createUserHandler;
    private GetUserByIdQueryHandler $getUserHandler;
    
    public function __construct(
        LegacyUserService $legacyService,
        DomainUserService $domainService,
        CreateUserCommandHandler $createUserHandler,
        GetUserByIdQueryHandler $getUserHandler
    ) {
        $this->legacyService = $legacyService;
        $this->domainService = $domainService;
        $this->createUserHandler = $createUserHandler;
        $this->getUserHandler = $getUserHandler;
    }
    
    /**
     * Get or create user using new domain architecture
     */
    public function getOrCreateUser(int $userId, array $userInfo, string $referralCode = null): array
    {
        try {
            // Try to get user using new architecture
            $query = new GetUserByIdQuery(new UserId($userId));
            $user = $this->getUserHandler->handle($query);
            
            if ($user) {
                return $this->convertDomainUserToArray($user);
            }
        } catch (\Exception $e) {
            // User not found, create new one
        }
        
        // Create user using new architecture
        try {
            $command = new CreateUserCommand(
                new UserId($userId),
                isset($userInfo['username']) ? new Username($userInfo['username']) : null,
                $userInfo['first_name'] ?? '',
                $userInfo['last_name'] ?? '',
                isset($userInfo['email']) ? new Email($userInfo['email']) : null,
                isset($userInfo['phone']) ? new PhoneNumber($userInfo['phone']) : null,
                $referralCode
            );
            
            $user = $this->createUserHandler->handle($command);
            return $this->convertDomainUserToArray($user);
            
        } catch (\Exception $e) {
            // Fallback to legacy service
            return $this->legacyService->getOrCreateUser($userId, $userInfo, $referralCode);
        }
    }
    
    /**
     * Get user by ID using new architecture with legacy fallback
     */
    public function getUserById(int $userId): ?array
    {
        try {
            $query = new GetUserByIdQuery(new UserId($userId));
            $user = $this->getUserHandler->handle($query);
            
            if ($user) {
                return $this->convertDomainUserToArray($user);
            }
            
            return null;
        } catch (\Exception $e) {
            // Fallback to legacy service
            return $this->legacyService->getUserById($userId);
        }
    }
    
    /**
     * Block user - delegate to legacy service for now
     */
    public function blockUser(int $userId, string $reason = ''): bool
    {
        return $this->legacyService->blockUser($userId, $reason);
    }
    
    /**
     * Unblock user - delegate to legacy service for now
     */
    public function unblockUser(int $userId): bool
    {
        return $this->legacyService->unblockUser($userId);
    }
    
    /**
     * Check if user is blocked - delegate to legacy service for now
     */
    public function isUserBlocked(int $userId): bool
    {
        return $this->legacyService->isUserBlocked($userId);
    }
    
    /**
     * Check rate limiting - delegate to legacy service for now
     */
    public function isRateLimited(int $userId): bool
    {
        return $this->legacyService->isRateLimited($userId);
    }
    
    /**
     * Convert domain User entity to legacy array format
     */
    private function convertDomainUserToArray(User $user): array
    {
        return [
            'id' => $user->getId()->getValue(),
            'username' => $user->getUsername()?->getValue(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'email' => $user->getEmail()?->getValue(),
            'phone' => $user->getPhone()?->getValue(),
            'balance' => $user->getBalance()->getAmount(),
            'status' => $user->getStatus()->getValue(),
            'telegram_chat_id' => $user->getTelegramChatId(),
            'is_premium' => $user->isPremium(),
            'referral_code' => $user->getReferralCode(),
            'referred_by' => $user->getReferredBy()?->getValue(),
            'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $user->getUpdatedAt()->format('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Delegate all other method calls to legacy service
     */
    public function __call(string $method, array $arguments)
    {
        if (method_exists($this->legacyService, $method)) {
            return $this->legacyService->$method(...$arguments);
        }
        
        throw new \BadMethodCallException("Method {$method} does not exist");
    }
}