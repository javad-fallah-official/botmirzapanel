<?php

namespace BotMirzaPanel\Application\Queries\Subscription;

use BotMirzaPanel\Application\Queries\QueryHandlerInterface;
use BotMirzaPanel\Domain\Repositories\SubscriptionRepositoryInterface;

class GetSubscriptionsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository
    ) {}

    public function handle(GetSubscriptionsQuery $query): array
    {
        if ($query->getActiveOnly()) {
            return $this->subscriptionRepository->findActiveSubscriptions(
                $query->getUserId(),
                $query->getLimit(),
                $query->getOffset()
            );
        }

        return $this->subscriptionRepository->findBy(
            $query->getFilters(),
            $query->getOrderBy(),
            $query->getLimit(),
            $query->getOffset()
        );
    }
}