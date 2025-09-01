<?php

namespace BotMirzaPanel\Domain\Entities\Subscription;

use BotMirzaPanel\Domain\ValueObjects\Subscription\SubscriptionId;
use BotMirzaPanel\Domain\ValueObjects\User\UserId;
use BotMirzaPanel\Domain\ValueObjects\Panel\PanelId;
use BotMirzaPanel\Domain\ValueObjects\Subscription\SubscriptionStatus;
use BotMirzaPanel\Domain\ValueObjects\Subscription\SubscriptionType;
use BotMirzaPanel\Domain\Events\SubscriptionCreated;
use BotMirzaPanel\Domain\Events\SubscriptionActivated;
use BotMirzaPanel\Domain\Events\SubscriptionExpired;
use BotMirzaPanel\Domain\Events\SubscriptionSuspended;
use BotMirzaPanel\Domain\Events\SubscriptionCancelled;
use BotMirzaPanel\Domain\Events\SubscriptionRenewed;
use BotMirzaPanel\Domain\Shared\AggregateRoot;
use DateTime;
use DateInterval;

/**
 * Subscription Aggregate Root
 * 
 * Represents a user's subscription to VPN services.
 */
class Subscription extends AggregateRoot
{
    private SubscriptionId $id;
    private UserId $userId;
    private ?PanelId $panelId;
    private string $name;
    private SubscriptionType $type;
    private SubscriptionStatus $status;
    private ?int $dataLimit; // in bytes, null for unlimited
    private int $dataUsed; // in bytes
    private ?DateTime $expiryDate;
    private ?DateTime $activatedAt;
    private ?DateTime $suspendedAt;
    private ?DateTime $cancelledAt;
    private bool $autoRenew;
    private ?int $renewalPeriodDays;
    private array $metadata;
    private DateTime $createdAt;
    private DateTime $updatedAt;

    /** @var SubscriptionFeature[] */
    private array $features;

    /** @var SubscriptionUsage[] */
    private array $usageHistory;

    public function __construct(
        SubscriptionId $id,
        UserId $userId,
        string $name,
        SubscriptionType $type,
        ?int $dataLimit = null,
        ?int $expiryDays = null,
        ?PanelId $panelId = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->panelId = $panelId;
        $this->name = $name;
        $this->type = $type;
        $this->status = SubscriptionStatus::pending();
        $this->dataLimit = $dataLimit;
        $this->dataUsed = 0;
        $this->expiryDate = $expiryDays ? (new DateTime())->add(new DateInterval("P{$expiryDays}D")) : null;
        $this->activatedAt = null;
        $this->suspendedAt = null;
        $this->cancelledAt = null;
        $this->autoRenew = false;
        $this->renewalPeriodDays = $expiryDays;
        $this->metadata = [];
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
        $this->features = [];
        $this->usageHistory = [];
    }

    public static function create(
        SubscriptionId $id,
        UserId $userId,
        string $name,
        SubscriptionType $type,
        ?int $dataLimit = null,
        ?int $expiryDays = null,
        ?PanelId $panelId = null
    ): self {
        $subscription = new self($id, $userId, $name, $type, $dataLimit, $expiryDays, $panelId);
        
        $subscription->recordEvent(new SubscriptionCreated(
            $id,
            $userId,
            $name,
            $type,
            $dataLimit,
            $subscription->expiryDate,
            $panelId
        ));
        
        return $subscription;
    }

    public function activate(): void
    {
        if (!$this->status->isPending()) {
            throw new \DomainException('Only pending subscriptions can be activated.');
        }
        
        $this->status = SubscriptionStatus::active();
        $this->activatedAt = new DateTime();
        $this->updatedAt = new DateTime();
        
        $this->recordEvent(new SubscriptionActivated($this->id, $this->userId));
    }

    public function suspend(string $reason = ''): void
    {
        if (!$this->status->isActive()) {
            throw new \DomainException('Only active subscriptions can be suspended.');
        }
        
        $this->status = SubscriptionStatus::suspended();
        $this->suspendedAt = new DateTime();
        $this->updatedAt = new DateTime();
        
        if ($reason) {
            $this->metadata['suspension_reason'] = $reason;
        }
        
        $this->recordEvent(new SubscriptionSuspended($this->id, $this->userId, $reason));
    }

    public function resume(): void
    {
        if (!$this->status->isSuspended()) {
            throw new \DomainException('Only suspended subscriptions can be resumed.');
        }
        
        $this->status = SubscriptionStatus::active();
        $this->suspendedAt = null;
        $this->updatedAt = new DateTime();
        
        unset($this->metadata['suspension_reason']);
        
        $this->recordEvent(new SubscriptionActivated($this->id, $this->userId));
    }

    public function cancel(string $reason = ''): void
    {
        if ($this->status->isCancelled()) {
            throw new \DomainException('Subscription is already cancelled.');
        }
        
        $this->status = SubscriptionStatus::cancelled();
        $this->cancelledAt = new DateTime();
        $this->autoRenew = false;
        $this->updatedAt = new DateTime();
        
        if ($reason) {
            $this->metadata['cancellation_reason'] = $reason;
        }
        
        $this->recordEvent(new SubscriptionCancelled($this->id, $this->userId, $reason));
    }

    public function expire(): void
    {
        if (!$this->status->isActive()) {
            throw new \DomainException('Only active subscriptions can expire.');
        }
        
        $this->status = SubscriptionStatus::expired();
        $this->updatedAt = new DateTime();
        
        $this->recordEvent(new SubscriptionExpired($this->id, $this->userId));
    }

    public function renew(int $days, ?int $newDataLimit = null): void
    {
        if ($this->status->isCancelled()) {
            throw new \DomainException('Cancelled subscriptions cannot be renewed.');
        }
        
        $now = new DateTime();
        
        // Extend expiry date
        if ($this->expiryDate && $this->expiryDate > $now) {
            // Extend from current expiry date
            $this->expiryDate->add(new DateInterval("P{$days}D"));
        } else {
            // Set new expiry date from now
            $this->expiryDate = $now->add(new DateInterval("P{$days}D"));
        }
        
        // Reset data usage if new data limit is provided
        if ($newDataLimit !== null) {
            $this->dataLimit = $newDataLimit;
            $this->dataUsed = 0;
        }
        
        // Reactivate if expired or suspended
        if ($this->status->isExpired() || $this->status->isSuspended()) {
            $this->status = SubscriptionStatus::active();
            $this->activatedAt = new DateTime();
            $this->suspendedAt = null;
        }
        
        $this->updatedAt = new DateTime();
        
        $this->recordEvent(new SubscriptionRenewed(
            $this->id,
            $this->userId,
            $days,
            $this->expiryDate,
            $newDataLimit
        ));
    }

    public function updateDataUsage(int $bytesUsed): void
    {
        if ($bytesUsed < 0) {
            throw new \InvalidArgumentException('Data usage cannot be negative.');
        }
        
        $this->dataUsed = $bytesUsed;
        $this->updatedAt = new DateTime();
        
        // Check if data limit exceeded
        if ($this->dataLimit && $this->dataUsed >= $this->dataLimit && $this->status->isActive()) {
            $this->suspend('Data limit exceeded');
        }
    }

    public function addDataUsage(int $bytes): void
    {
        $this->updateDataUsage($this->dataUsed + $bytes);
    }

    public function resetDataUsage(): void
    {
        $this->dataUsed = 0;
        $this->updatedAt = new DateTime();
        
        // Resume if suspended due to data limit
        if ($this->status->isSuspended() && 
            isset($this->metadata['suspension_reason']) && 
            $this->metadata['suspension_reason'] === 'Data limit exceeded') {
            $this->resume();
        }
    }

    public function updateDataLimit(?int $dataLimit): void
    {
        if ($dataLimit !== null && $dataLimit < 0) {
            throw new \InvalidArgumentException('Data limit cannot be negative.');
        }
        
        $this->dataLimit = $dataLimit;
        $this->updatedAt = new DateTime();
        
        // Resume if suspended due to data limit and new limit allows it
        if ($this->status->isSuspended() && 
            isset($this->metadata['suspension_reason']) && 
            $this->metadata['suspension_reason'] === 'Data limit exceeded' &&
            ($dataLimit === null || $this->dataUsed < $dataLimit)) {
            $this->resume();
        }
    }

    public function updateExpiryDate(?DateTime $expiryDate): void
    {
        $this->expiryDate = $expiryDate;
        $this->updatedAt = new DateTime();
    }

    public function enableAutoRenew(int $renewalPeriodDays): void
    {
        if ($renewalPeriodDays < 1) {
            throw new \InvalidArgumentException('Renewal period must be at least 1 day.');
        }
        
        $this->autoRenew = true;
        $this->renewalPeriodDays = $renewalPeriodDays;
        $this->updatedAt = new DateTime();
    }

    public function disableAutoRenew(): void
    {
        $this->autoRenew = false;
        $this->updatedAt = new DateTime();
    }

    public function updateMetadata(array $metadata): void
    {
        $this->metadata = array_merge($this->metadata, $metadata);
        $this->updatedAt = new DateTime();
    }

    public function addFeature(SubscriptionFeature $feature): void
    {
        $this->features[] = $feature;
        $this->updatedAt = new DateTime();
    }

    public function removeFeature(string $featureName): void
    {
        $this->features = array_filter(
            $this->features,
            fn(SubscriptionFeature $feature) => $feature->getName() !== $featureName
        );
        $this->updatedAt = new DateTime();
    }

    public function addUsageRecord(SubscriptionUsage $usage): void
    {
        $this->usageHistory[] = $usage;
    }

    // Status checks
    public function isActive(): bool
    {
        return $this->status->isActive() && !$this->isExpired() && !$this->isDataLimitExceeded();
    }

    public function isExpired(): bool
    {
        return $this->expiryDate && $this->expiryDate <= new DateTime();
    }

    public function isDataLimitExceeded(): bool
    {
        return $this->dataLimit && $this->dataUsed >= $this->dataLimit;
    }

    public function canBeRenewed(): bool
    {
        return !$this->status->isCancelled();
    }

    public function shouldAutoRenew(): bool
    {
        return $this->autoRenew && $this->canBeRenewed() && $this->isExpired();
    }

    public function getRemainingData(): ?int
    {
        return $this->dataLimit ? max(0, $this->dataLimit - $this->dataUsed) : null;
    }

    public function getDataUsagePercentage(): ?float
    {
        return $this->dataLimit ? ($this->dataUsed / $this->dataLimit) * 100 : null;
    }

    public function getDaysUntilExpiry(): ?int
    {
        if (!$this->expiryDate) {
            return null;
        }
        
        $now = new DateTime();
        $diff = $now->diff($this->expiryDate);
        
        return $diff->invert ? 0 : $diff->days;
    }

    // Getters
    public function getId(): SubscriptionId
    {
        return $this->id;
    }

    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function getPanelId(): ?PanelId
    {
        return $this->panelId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): SubscriptionType
    {
        return $this->type;
    }

    public function getStatus(): SubscriptionStatus
    {
        return $this->status;
    }

    public function getDataLimit(): ?int
    {
        return $this->dataLimit;
    }

    public function getDataUsed(): int
    {
        return $this->dataUsed;
    }

    public function getExpiryDate(): ?DateTime
    {
        return $this->expiryDate;
    }

    public function getActivatedAt(): ?DateTime
    {
        return $this->activatedAt;
    }

    public function getSuspendedAt(): ?DateTime
    {
        return $this->suspendedAt;
    }

    public function getCancelledAt(): ?DateTime
    {
        return $this->cancelledAt;
    }

    public function isAutoRenewEnabled(): bool
    {
        return $this->autoRenew;
    }

    public function getRenewalPeriodDays(): ?int
    {
        return $this->renewalPeriodDays;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function getFeatures(): array
    {
        return $this->features;
    }

    public function getUsageHistory(): array
    {
        return $this->usageHistory;
    }
}