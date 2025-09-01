<?php

namespace BotMirzaPanel\Application\Commands\Subscription;

use BotMirzaPanel\Application\Commands\CommandInterface;
use BotMirzaPanel\Domain\ValueObjects\User\UserId;
use BotMirzaPanel\Domain\ValueObjects\Panel\PanelId;
use BotMirzaPanel\Domain\ValueObjects\Subscription\SubscriptionPlan;
use BotMirzaPanel\Domain\ValueObjects\Common\Money;

class CreateSubscriptionCommand implements CommandInterface
{
    public function __construct(
        private UserId $userId,
        private PanelId $panelId,
        private SubscriptionPlan $plan,
        private Money $amount,
        private \DateTimeImmutable $startDate,
        private \DateTimeImmutable $endDate,
        private ?array $metadata = null
    ) {}

    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function getPanelId(): PanelId
    {
        return $this->panelId;
    }

    public function getPlan(): SubscriptionPlan
    {
        return $this->plan;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): \DateTimeImmutable
    {
        return $this->endDate;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }
}