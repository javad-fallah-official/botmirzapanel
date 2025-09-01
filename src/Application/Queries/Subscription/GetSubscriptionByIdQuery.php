<?php

namespace BotMirzaPanel\Application\Queries\Subscription;

use BotMirzaPanel\Application\Queries\QueryInterface;
use BotMirzaPanel\Domain\ValueObjects\Subscription\SubscriptionId;

class GetSubscriptionByIdQuery implements QueryInterface
{
    public function __construct(
        private SubscriptionId $subscriptionId
    ) {}

    public function getSubscriptionId(): SubscriptionId
    {
        return $this->subscriptionId;
    }
}