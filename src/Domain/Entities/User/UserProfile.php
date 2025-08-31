<?php

namespace BotMirzaPanel\Domain\Entities\User;

use BotMirzaPanel\Domain\ValueObjects\User\UserId;
use DateTime;

/**
 * UserProfile Entity
 * 
 * Represents additional profile information for a user.
 * This is a child entity of the User aggregate.
 */
class UserProfile
{
    private UserId $userId;
    private ?string $avatar;
    private ?string $bio;
    private ?string $website;
    private ?string $location;
    private ?DateTime $birthDate;
    private ?string $timezone;
    private ?string $language;
    private DateTime $createdAt;
    private DateTime $updatedAt;

    public function __construct(
        UserId $userId,
        ?string $avatar = null,
        ?string $bio = null,
        ?string $website = null,
        ?string $location = null,
        ?DateTime $birthDate = null,
        ?string $timezone = null,
        ?string $language = null
    ) {
        $this->userId = $userId;
        $this->avatar = $avatar;
        $this->bio = $bio;
        $this->website = $website;
        $this->location = $location;
        $this->birthDate = $birthDate;
        $this->timezone = $timezone;
        $this->language = $language;
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    public static function create(
        UserId $userId,
        ?string $avatar = null,
        ?string $bio = null,
        ?string $website = null,
        ?string $location = null,
        ?DateTime $birthDate = null,
        ?string $timezone = null,
        ?string $language = null
    ): self {
        return new self(
            $userId,
            $avatar,
            $bio,
            $website,
            $location,
            $birthDate,
            $timezone,
            $language
        );
    }

    public function updateAvatar(string $avatar): void
    {
        $this->avatar = $avatar;
        $this->updatedAt = new DateTime();
    }

    public function updateBio(string $bio): void
    {
        $this->bio = $bio;
        $this->updatedAt = new DateTime();
    }

    public function updateWebsite(string $website): void
    {
        $this->website = $website;
        $this->updatedAt = new DateTime();
    }

    public function updateLocation(string $location): void
    {
        $this->location = $location;
        $this->updatedAt = new DateTime();
    }

    public function updateBirthDate(DateTime $birthDate): void
    {
        $this->birthDate = $birthDate;
        $this->updatedAt = new DateTime();
    }

    public function updateTimezone(string $timezone): void
    {
        $this->timezone = $timezone;
        $this->updatedAt = new DateTime();
    }

    public function updateLanguage(string $language): void
    {
        $this->language = $language;
        $this->updatedAt = new DateTime();
    }

    // Getters
    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function getBirthDate(): ?DateTime
    {
        return $this->birthDate;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }
}