<?php

declare(strict_types=1);

namespace Application\Commands\User;

use Application\Commands\CommandInterface;

/**
 * Command to update an existing user
 */
final readonly class UpdateUserCommand implements CommandInterface
{
    public function __construct(
        public string $userId,
        public ?string $email = null,
        public ?string $password = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $phoneNumber = null,
        public ?string $telegramChatId = null,
        public ?bool $isActive = null,
        public ?bool $isPremium = null,
        public ?string $premiumExpiresAt = null,
        public ?array $metadata = null
    ) {}

    public function toArray(): array
    {
        return [
            'userId' => $this->userId,
            'email' => $this->email,
            'password' => $this->password,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'phoneNumber' => $this->phoneNumber,
            'telegramChatId' => $this->telegramChatId,
            'isActive' => $this->isActive,
            'isPremium' => $this->isPremium,
            'premiumExpiresAt' => $this->premiumExpiresAt,
            'metadata' => $this->metadata,
        ];
    }
}