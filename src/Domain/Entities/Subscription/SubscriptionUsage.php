<?php
// WARNING: SQL injection vulnerability detected in this file
// Please review and use prepared statements or secure_* functions


namespace BotMirzaPanel\Domain\Entities\Subscription;

use BotMirzaPanel\Domain\ValueObjects\Subscription\SubscriptionId;
use DateTime;

/**
 * SubscriptionUsage Entity
 * 
 * Represents usage tracking for a subscription.
 * This is a child entity of the Subscription aggregate.
 */
class SubscriptionUsage
{
    private SubscriptionId $subscriptionId;
    private string $type; // 'data', 'time', 'feature'
    private string $metric; // 'bytes', 'minutes', 'count', etc.
    private int $amount;
    private string $source; // 'panel', 'api', 'manual', etc.
    private ?string $sourceId; // panel user id, api key id, etc.
    private array $metadata;
    private DateTime $recordedAt;
    private DateTime $createdAt;

    public function __construct(
        SubscriptionId $subscriptionId,
        string $type,
        string $metric,
        int $amount,
        string $source,
        ?string $sourceId = null,
        ?DateTime $recordedAt = null
    ) {
        $this->subscriptionId = $subscriptionId;
        $this->type = $this->validateType($type);
        $this->metric = $metric;
        $this->amount = $this->validateAmount($amount);
        $this->source = $source;
        $this->sourceId = $sourceId;
        $this->metadata = [];
        $this->recordedAt = $recordedAt ?? new DateTime();
        $this->createdAt = new DateTime();
    }

    public static function createDataUsage(
        SubscriptionId $subscriptionId,
        int $bytes,
        string $source,
        ?string $sourceId = null,
        ?DateTime $recordedAt = null
    ): self {
        return new self($subscriptionId, 'data', 'bytes', $bytes, $source, $sourceId, $recordedAt);
    }

    public static function createTimeUsage(
        SubscriptionId $subscriptionId,
        int $minutes,
        string $source,
        ?string $sourceId = null,
        ?DateTime $recordedAt = null
    ): self {
        return new self($subscriptionId, 'time', 'minutes', $minutes, $source, $sourceId, $recordedAt);
    }

    public static function createFeatureUsage(
        SubscriptionId $subscriptionId,
        string $featureName,
        int $count,
        string $source,
        ?string $sourceId = null,
        ?DateTime $recordedAt = null
    ): self {
        $usage = new self($subscriptionId, 'feature', 'count', $count, $source, $sourceId, $recordedAt);
        $usage->metadata['feature_name'] = $featureName;
        return $usage;
    }

    public function updateMetadata(array $metadata): void
    {
        $this->metadata = array_merge($this->metadata, $metadata);
    }

    public function addMetadata(string $key, $value): void
    {
        $this->metadata[$key] = $value;
    }

    public function getMetadataValue(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    public function isDataUsage(): bool
    {
        return $this->type === 'data';
    }

    public function isTimeUsage(): bool
    {
        return $this->type === 'time';
    }

    public function isFeatureUsage(): bool
    {
        return $this->type === 'feature';
    }

    public function getFeatureName(): ?string
    {
        return $this->isFeatureUsage() ? $this->getMetadataValue('feature_name') : null;
    }

    public function getAmountInMB(): ?float
    {
        if (!$this->isDataUsage()) {
            return null;
        }
        
        return $this->amount / (1024 * 1024);
    }

    public function getAmountInGB(): ?float
    {
        if (!$this->isDataUsage()) {
            return null;
        }
        
        return $this->amount / (1024 * 1024 * 1024);
    }

    public function getAmountInHours(): ?float
    {
        if (!$this->isTimeUsage()) {
            return null;
        }
        
        return $this->amount / 60;
    }

    public function getFormattedAmount(): string
    {
        switch ($this->type) {
            case 'data':
                if ($this->amount >= 1024 * 1024 * 1024) {
                    return number_format($this->getAmountInGB(), 2) . ' GB';
                } elseif ($this->amount >= 1024 * 1024) {
                    return number_format($this->getAmountInMB(), 2) . ' MB';
                } elseif ($this->amount >= 1024) {
                    return number_format($this->amount / 1024, 2) . ' KB';
                } else {
                    return $this->amount . ' bytes';
                }
                
            case 'time':
                if ($this->amount >= 60) {
                    $hours = floor($this->amount / 60);
                    $minutes = $this->amount % 60;
                    return $hours . 'h ' . $minutes . 'm';
                } else {
                    return $this->amount . ' minutes';
                }
                
            case 'feature':
                $featureName = $this->getFeatureName() ?? 'feature';
                return $this->amount . ' ' . $featureName . ' usage(s)';
                
            default:
                return $this->amount . ' ' . $this->metric;
        }
    }

    private function validateType(string $type): string
    {
        $validTypes = ['data', 'time', 'feature'];
        
        if (!in_array($type, $validTypes)) {
            throw new \InvalidArgumentException(
                'Invalid usage type. Must be one of: ' . implode(', ', $validTypes)
            );
        }
        
        return $type;
    }

    private function validateAmount(int $amount): int
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Usage amount cannot be negative.');
        }
        
        return $amount;
    }

    // Getters
    public function getSubscriptionId(): SubscriptionId
    {
        return $this->subscriptionId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getMetric(): string
    {
        return $this->metric;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getSourceId(): ?string
    {
        return $this->sourceId;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getRecordedAt(): DateTime
    {
        return $this->recordedAt;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }
}