<?php

namespace BotMirzaPanel\Domain\Entities\Subscription;

use BotMirzaPanel\Domain\ValueObjects\Subscription\SubscriptionId;
use DateTime;

/**
 * SubscriptionFeature Entity
 * 
 * Represents a feature available in a subscription.
 * This is a child entity of the Subscription aggregate.
 */
class SubscriptionFeature
{
    private SubscriptionId $subscriptionId;
    private string $name;
    private string $description;
    private string $type; // 'boolean', 'numeric', 'text', 'json'
    private $value;
    private ?int $limit; // for numeric features
    private bool $isEnabled;
    private array $metadata;
    private DateTime $createdAt;
    private DateTime $updatedAt;

    public function __construct(
        SubscriptionId $subscriptionId,
        string $name,
        string $description,
        string $type,
        $value,
        ?int $limit = null
    ) {
        $this->subscriptionId = $subscriptionId;
        $this->name = $name;
        $this->description = $description;
        $this->type = $this->validateType($type);
        $this->value = $this->validateValue($value, $type);
        $this->limit = $limit;
        $this->isEnabled = true;
        $this->metadata = [];
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    public static function createBoolean(
        SubscriptionId $subscriptionId,
        string $name,
        string $description,
        bool $value = true
    ): self {
        return new self($subscriptionId, $name, $description, 'boolean', $value);
    }

    public static function createNumeric(
        SubscriptionId $subscriptionId,
        string $name,
        string $description,
        int $value,
        ?int $limit = null
    ): self {
        return new self($subscriptionId, $name, $description, 'numeric', $value, $limit);
    }

    public static function createText(
        SubscriptionId $subscriptionId,
        string $name,
        string $description,
        string $value
    ): self {
        return new self($subscriptionId, $name, $description, 'text', $value);
    }

    public static function createJson(
        SubscriptionId $subscriptionId,
        string $name,
        string $description,
        array $value
    ): self {
        return new self($subscriptionId, $name, $description, 'json', $value);
    }

    public function updateValue($value): void
    {
        $this->value = $this->validateValue($value, $this->type);
        $this->updatedAt = new DateTime();
    }

    public function updateLimit(?int $limit): void
    {
        if ($this->type !== 'numeric') {
            throw new \InvalidArgumentException('Limit can only be set for numeric features.');
        }
        
        $this->limit = $limit;
        $this->updatedAt = new DateTime();
    }

    public function enable(): void
    {
        $this->isEnabled = true;
        $this->updatedAt = new DateTime();
    }

    public function disable(): void
    {
        $this->isEnabled = false;
        $this->updatedAt = new DateTime();
    }

    public function updateDescription(string $description): void
    {
        $this->description = $description;
        $this->updatedAt = new DateTime();
    }

    public function updateMetadata(array $metadata): void
    {
        $this->metadata = array_merge($this->metadata, $metadata);
        $this->updatedAt = new DateTime();
    }

    public function incrementNumericValue(int $amount = 1): void
    {
        if ($this->type !== 'numeric') {
            throw new \InvalidArgumentException('Can only increment numeric features.');
        }
        
        $newValue = $this->value + $amount;
        
        if ($this->limit && $newValue > $this->limit) {
            throw new \DomainException('Feature value would exceed limit.');
        }
        
        $this->value = $newValue;
        $this->updatedAt = new DateTime();
    }

    public function decrementNumericValue(int $amount = 1): void
    {
        if ($this->type !== 'numeric') {
            throw new \InvalidArgumentException('Can only decrement numeric features.');
        }
        
        $newValue = max(0, $this->value - $amount);
        $this->value = $newValue;
        $this->updatedAt = new DateTime();
    }

    public function resetNumericValue(): void
    {
        if ($this->type !== 'numeric') {
            throw new \InvalidArgumentException('Can only reset numeric features.');
        }
        
        $this->value = 0;
        $this->updatedAt = new DateTime();
    }

    public function isLimitReached(): bool
    {
        return $this->type === 'numeric' && $this->limit && $this->value >= $this->limit;
    }

    public function getRemainingLimit(): ?int
    {
        if ($this->type !== 'numeric' || !$this->limit) {
            return null;
        }
        
        return max(0, $this->limit - $this->value);
    }

    public function getLimitUsagePercentage(): ?float
    {
        if ($this->type !== 'numeric' || !$this->limit) {
            return null;
        }
        
        return ($this->value / $this->limit) * 100;
    }

    private function validateType(string $type): string
    {
        $validTypes = ['boolean', 'numeric', 'text', 'json'];
        
        if (!in_array($type, $validTypes)) {
            throw new \InvalidArgumentException(
                'Invalid feature type. Must be one of: ' . implode(', ', $validTypes)
            );
        }
        
        return $type;
    }

    private function validateValue($value, string $type)
    {
        switch ($type) {
            case 'boolean':
                if (!is_bool($value)) {
                    throw new \InvalidArgumentException('Boolean feature value must be a boolean.');
                }
                break;
                
            case 'numeric':
                if (!is_int($value) || $value < 0) {
                    throw new \InvalidArgumentException('Numeric feature value must be a non-negative integer.');
                }
                break;
                
            case 'text':
                if (!is_string($value)) {
                    throw new \InvalidArgumentException('Text feature value must be a string.');
                }
                break;
                
            case 'json':
                if (!is_array($value)) {
                    throw new \InvalidArgumentException('JSON feature value must be an array.');
                }
                break;
        }
        
        return $value;
    }

    // Getters
    public function getSubscriptionId(): SubscriptionId
    {
        return $this->subscriptionId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
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
}