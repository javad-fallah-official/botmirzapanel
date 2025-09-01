<?php

namespace BotMirzaPanel\Application\Commands\Subscription;

use BotMirzaPanel\Application\Commands\CommandInterface;
use BotMirzaPanel\Domain\ValueObjects\Subscription\SubscriptionId;
use BotMirzaPanel\Domain\ValueObjects\Subscription\SubscriptionPlan;
use BotMirzaPanel\Domain\ValueObjects\Subscription\SubscriptionStatus;
use BotMirzaPanel\Domain\ValueObjects\Common\Money;

class UpdateSubscriptionCommand implements CommandInterface
{
    public function __construct(
        private SubscriptionId $subscriptionId,
        private ?SubscriptionPlan $plan = null,
        private ?Money $amount = null,
        private ?SubscriptionStatus $status = null,
        private ?\DateTimeImmutable $endDate = null,
        private ?array $metadata = null
    ) {}

    public function getSubscriptionId(): SubscriptionId
    {
        return $this->subscriptionId;
    }

    public function getPlan(): ?SubscriptionPlan
    {
        return $this->plan;
    }

    public function getAmount(): ?Money
    {
        return $this->amount;
    }

    public function getStatus(): ?SubscriptionStatus
    {
        return $this->status;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function hasPlan(): bool
    {
        return $this->plan !== null;
    }

    public function hasAmount(): bool
    {
        return $this->amount !== null;
    }

    public function hasStatus(): bool
    {
        return $this->status !== null;
    }

    public function hasEndDate(): bool
    {
        return $this->endDate !== null;
    }

    public function hasMetadata(): bool
    {
        return $this->metadata !== null;
    }
}