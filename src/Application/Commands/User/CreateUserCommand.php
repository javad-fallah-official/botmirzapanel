<?php

declare(strict_types=1);

namespace Application\Commands\User;

use Application\Commands\CommandInterface;

/**
 * Command to create a new user
 */
final readonly class CreateUserCommand implements CommandInterface
{
    public function __construct(
        public string $email,
        public string $password,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $phoneNumber = null,
        public ?string $telegramChatId = null,
        public ?string $referralCode = null,
        public bool $isActive = true,
        public bool $isPremium = false,
        public ?array $metadata = null
    ) {}

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'phoneNumber' => $this->phoneNumber,
            'telegramChatId' => $this->telegramChatId,
            'referralCode' => $this->referralCode,
            'isActive' => $this->isActive,
            'isPremium' => $this->isPremium,
            'metadata' => $this->metadata,
        ];
    }
}