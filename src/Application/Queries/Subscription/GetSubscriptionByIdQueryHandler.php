<?php

namespace BotMirzaPanel\Application\Queries\Subscription;

use BotMirzaPanel\Application\Queries\QueryHandlerInterface;
use BotMirzaPanel\Domain\Entities\Subscription\Subscription;
use BotMirzaPanel\Domain\Repositories\SubscriptionRepositoryInterface;
use BotMirzaPanel\Domain\Exceptions\EntityNotFoundException;

class GetSubscriptionByIdQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository
    ) {}

    public function handle(GetSubscriptionByIdQuery $query): ?Subscription
    {
        $subscription = $this->subscriptionRepository->findById($query->getSubscriptionId());
        
        if (!$subscription) {
            throw new EntityNotFoundException(
                'Subscription not found: ' . $query->getSubscriptionId()->getValue()
            );
        }

        return $subscription;
    }
}