<?php

namespace BotMirzaPanel\Domain\Entities\User;

use BotMirzaPanel\Domain\ValueObjects\User\UserId;
use BotMirzaPanel\Domain\ValueObjects\User\Username;
use BotMirzaPanel\Domain\ValueObjects\User\UserStatus;
use BotMirzaPanel\Domain\ValueObjects\Common\Email;
use BotMirzaPanel\Domain\ValueObjects\Common\PhoneNumber;
use BotMirzaPanel\Domain\Events\User\UserRegistered;
use BotMirzaPanel\Domain\Events\User\UserActivated;
use BotMirzaPanel\Domain\Events\User\UserDeactivated;
use DateTime;

/**
 * User Aggregate Root
 * 
 * Represents a user in the system with all business rules and invariants.
 * This is the main entry point for all user-related operations.
 */
class User
{
    private UserId $id;
    private Username $username;
    private ?Email $email;
    private ?PhoneNumber $phoneNumber;
    private UserStatus $status;
    private ?string $telegramId;
    private ?string $firstName;
    private ?string $lastName;
    private ?string $referralCode;
    private ?UserId $referredBy;
    private DateTime $createdAt;
    private DateTime $updatedAt;
    private array $domainEvents = [];
    
    private ?UserProfile $profile;
    private ?UserPreferences $preferences;

    public function __construct(
        UserId $id,
        Username $username,
        ?Email $email = null,
        ?PhoneNumber $phoneNumber = null,
        ?string $telegramId = null,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $referralCode = null,
        ?UserId $referredBy = null
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
        $this->phoneNumber = $phoneNumber;
        $this->status = UserStatus::pending();
        $this->telegramId = $telegramId;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->referralCode = $referralCode;
        $this->referredBy = $referredBy;
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
        
        $this->addDomainEvent(new UserRegistered($this->id, $this->username, $this->email));
    }

    public static function create(
        UserId $id,
        Username $username,
        ?Email $email = null,
        ?PhoneNumber $phoneNumber = null,
        ?string $telegramId = null,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $referralCode = null,
        ?UserId $referredBy = null
    ): self {
        return new self(
            $id,
            $username,
            $email,
            $phoneNumber,
            $telegramId,
            $firstName,
            $lastName,
            $referralCode,
            $referredBy
        );
    }

    public function activate(): void
    {
        if ($this->status->equals(UserStatus::active())) {
            return;
        }
        
        $this->status = UserStatus::active();
        $this->updatedAt = new DateTime();
        
        $this->addDomainEvent(new UserActivated($this->id));
    }

    public function deactivate(): void
    {
        if ($this->status->equals(UserStatus::inactive())) {
            return;
        }
        
        $this->status = UserStatus::inactive();
        $this->updatedAt = new DateTime();
        
        $this->addDomainEvent(new UserDeactivated($this->id));
    }

    public function updateProfile(UserProfile $profile): void
    {
        $this->profile = $profile;
        $this->updatedAt = new DateTime();
    }

    public function updatePreferences(UserPreferences $preferences): void
    {
        $this->preferences = $preferences;
        $this->updatedAt = new DateTime();
    }

    public function updateEmail(Email $email): void
    {
        $this->email = $email;
        $this->updatedAt = new DateTime();
    }

    public function updatePhoneNumber(PhoneNumber $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
        $this->updatedAt = new DateTime();
    }

    public function isActive(): bool
    {
        return $this->status->equals(UserStatus::active());
    }

    public function isBlocked(): bool
    {
        return $this->status->equals(UserStatus::blocked());
    }

    // Getters
    public function getId(): UserId
    {
        return $this->id;
    }

    public function getUsername(): Username
    {
        return $this->username;
    }

    public function getEmail(): ?Email
    {
        return $this->email;
    }

    public function getPhoneNumber(): ?PhoneNumber
    {
        return $this->phoneNumber;
    }

    public function getStatus(): UserStatus
    {
        return $this->status;
    }

    public function getTelegramId(): ?string
    {
        return $this->telegramId;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function getReferralCode(): ?string
    {
        return $this->referralCode;
    }

    public function getReferredBy(): ?UserId
    {
        return $this->referredBy;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function getProfile(): ?UserProfile
    {
        return $this->profile;
    }

    public function getPreferences(): ?UserPreferences
    {
        return $this->preferences;
    }

    // Domain Events
    public function getDomainEvents(): array
    {
        return $this->domainEvents;
    }

    public function clearDomainEvents(): void
    {
        $this->domainEvents = [];
    }

    private function addDomainEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }
}