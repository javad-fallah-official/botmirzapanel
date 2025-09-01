<?php

declare(strict_types=1);

namespace BotMirzaPanel\Domain\Services;

use BotMirzaPanel\Domain\Entities\User;
use BotMirzaPanel\Domain\ValueObjects\UserId;
use BotMirzaPanel\Domain\ValueObjects\Email;
use BotMirzaPanel\Domain\ValueObjects\Money;
use BotMirzaPanel\Domain\Repositories\UserRepositoryInterface;
use BotMirzaPanel\Shared\Utils\StringHelper;
use BotMirzaPanel\Shared\Constants\AppConstants;
use BotMirzaPanel\Shared\Exceptions\ValidationException;
use BotMirzaPanel\Shared\Exceptions\ServiceException;

/**
 * User domain service
 * Contains business logic that doesn't belong to a single entity
 */
class UserDomainService
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository): void
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Create a new user with validation
     */
    public function createUser(
        UserId $id,
        ?string $username,
        string $firstName,
        string $lastName,
        ?Email $email = null,
        ?string $phoneNumber = null,
        string $languageCode = 'en',
        bool $isBot = false,
        ?string $referralCode = null
    ): User {
        // Validate username uniqueness
        if ($username && $this->userRepository->usernameExists($username)) {
            throw new ValidationException('Username already exists', ['username' => ['Username is already taken']]);
        }

        // Validate email uniqueness
        if ($email && $this->userRepository->emailExists($email)) {
            throw new ValidationException('Email already exists', ['email' => ['Email is already registered']]);
        }

        // Validate referral code if provided
        $referredBy = null;
        if ($referralCode) {
            $referrer = $this->userRepository->findByReferralCode($referralCode);
            if (!$referrer) {
                throw new ValidationException('Invalid referral code', ['referral_code' => ['Referral code not found']]);
            }
            $referredBy = $referrer->getId()->getValue();
        }

        // Create user entity
        $user = new User(
            $id,
            $username,
            $firstName,
            $lastName,
            $email,
            $phoneNumber,
            $languageCode,
            $isBot,
            new Money(0, 'USD'),
            AppConstants::USER_ACTIVE,
            'home',
            false,
            false,
            $referredBy
        );

        // Generate unique referral code
        $user->setReferralCode($this->generateUniqueReferralCode());

        return $user;
    }

    /**
     * Process referral reward
     */
    public function processReferralReward(User $referrer, User $newUser, Money $rewardAmount): void
    {
        if (!$referrer->isActive()) {
            throw new ServiceException(
                'UserDomainService',
                'processReferralReward',
                'Referrer account is not active'
            );
        }

        if ($newUser->getReferredBy() !== $referrer->getId()->getValue()) {
            throw new ServiceException(
                'UserDomainService',
                'processReferralReward',
                'User was not referred by this referrer'
            );
        }

        // Add reward to referrer's balance
        $referrer->addBalance($rewardAmount);
    }

    /**
     * Transfer balance between users
     */
    public function transferBalance(User $fromUser, User $toUser, Money $amount): void
    {
        if (!$fromUser->isActive() || !$toUser->isActive()) {
            throw new ServiceException(
                'UserDomainService',
                'transferBalance',
                'Both users must be active for balance transfer'
            );
        }

        if (!$fromUser->hasEnoughBalance($amount)) {
            throw new ServiceException(
                'UserDomainService',
                'transferBalance',
                'Insufficient balance for transfer'
            );
        }

        if ($fromUser->getId()->equals($toUser->getId())) {
            throw new ServiceException(
                'UserDomainService',
                'transferBalance',
                'Cannot transfer balance to the same user'
            );
        }

        // Perform transfer
        $fromUser->deductBalance($amount);
        $toUser->addBalance($amount);
    }

    /**
     * Validate user profile update
     */
    public function validateProfileUpdate(
        User $user,
        ?string $username,
        string $firstName,
        string $lastName,
        ?Email $email = null,
        ?string $phoneNumber = null
    ): void {
        $errors = [];

        // Validate username
        if ($username) {
            if (strlen($username) < 3 || strlen($username) > 20) {
                $errors['username'][] = 'Username must be between 3 and 20 characters';
            }

            if (!preg_match(AppConstants::USERNAME_REGEX, $username)) {
                $errors['username'][] = 'Username can only contain letters, numbers, and underscores';
            }

            if ($this->userRepository->usernameExists($username, $user->getId())) {
                $errors['username'][] = 'Username is already taken';
            }
        }

        // Validate names
        if (empty(trim($firstName))) {
            $errors['first_name'][] = 'First name is required';
        }

        if (strlen($firstName) > 50) {
            $errors['first_name'][] = 'First name cannot exceed 50 characters';
        }

        if (strlen($lastName) > 50) {
            $errors['last_name'][] = 'Last name cannot exceed 50 characters';
        }

        // Validate email
        if ($email) {
            if ($email->isDisposable()) {
                $errors['email'][] = 'Disposable email addresses are not allowed';
            }

            if ($this->userRepository->emailExists($email, $user->getId())) {
                $errors['email'][] = 'Email is already registered';
            }
        }

        // Validate phone number
        if ($phoneNumber && !preg_match(AppConstants::PHONE_REGEX, $phoneNumber)) {
            $errors['phone_number'][] = 'Invalid phone number format';
        }

        if (!empty($errors)) {
            throw new ValidationException('Profile validation failed', $errors);
        }
    }

    /**
     * Check if user can be promoted to admin
     */
    public function canPromoteToAdmin(User $user): bool
    {
        return $user->isActive() && 
               !$user->isBot() && 
               !$user->hasRole(AppConstants::ROLE_ADMIN);
    }

    /**
     * Check if user can be banned
     */
    public function canBanUser(User $user, User $adminUser): bool
    {
        // Cannot ban yourself
        if ($user->getId()->equals($adminUser->getId())) {
            return false;
        }

        // Cannot ban other admins unless you're a super admin
        if ($user->hasRole(AppConstants::ROLE_ADMIN)) {
            return $adminUser->hasRole('super_admin');
        }

        return $adminUser->hasRole(AppConstants::ROLE_ADMIN);
    }

    /**
     * Generate unique referral code
     */
    private function generateUniqueReferralCode(): string
    {
        $maxAttempts = 10;
        $attempts = 0;

        do {
            $code = $this->generateReferralCode();
            $attempts++;
        } while ($this->userRepository->referralCodeExists($code) && $attempts < $maxAttempts);

        if ($attempts >= $maxAttempts) {
            throw new ServiceException(
                'UserDomainService',
                'generateUniqueReferralCode',
                'Failed to generate unique referral code after maximum attempts'
            );
        }

        return $code;
    }

    /**
     * Generate referral code
     */
    private function generateReferralCode(): string
    {
        return strtoupper(StringHelper::random(8, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'));
    }

    /**
     * Calculate user activity score
     */
    public function calculateActivityScore(User $user): int
    {
        $score = 0;

        // Base score for active account
        if ($user->isActive()) {
            $score += 10;
        }

        // Score for verified contact methods
        if ($user->isPhoneVerified()) {
            $score += 15;
        }

        if ($user->isEmailVerified()) {
            $score += 15;
        }

        // Score for having balance
        if ($user->getBalance()->isPositive()) {
            $score += 20;
        }

        // Score for account age (1 point per day, max 30)
        $daysSinceCreation = $user->getCreatedAt()->diff(new \DateTimeImmutable())->days;
        $score += min($daysSinceCreation, 30);

        // Score for referrals
        $referralCount = $this->userRepository->countReferrals($user->getId());
        $score += min($referralCount * 5, 50); // Max 50 points from referrals

        return min($score, 100); // Cap at 100
    }

    /**
     * Get user tier based on activity and balance
     */
    public function getUserTier(User $user): string
    {
        $balance = $user->getBalance()->getAmount();
        $activityScore = $this->calculateActivityScore($user);

        if ($balance >= 1000 && $activityScore >= 80) {
            return 'platinum';
        }

        if ($balance >= 500 && $activityScore >= 60) {
            return 'gold';
        }

        if ($balance >= 100 && $activityScore >= 40) {
            return 'silver';
        }

        return 'bronze';
    }
}