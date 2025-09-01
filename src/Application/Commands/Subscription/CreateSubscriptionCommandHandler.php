<?php

namespace BotMirzaPanel\Application\Commands\Subscription;

use BotMirzaPanel\Application\Commands\CommandHandlerInterface;
use BotMirzaPanel\Domain\Entities\Subscription\Subscription;
use BotMirzaPanel\Domain\Repositories\SubscriptionRepositoryInterface;
use BotMirzaPanel\Domain\Services\Subscription\SubscriptionService;
use BotMirzaPanel\Domain\ValueObjects\Subscription\SubscriptionId;
use BotMirzaPanel\Domain\ValueObjects\Subscription\SubscriptionStatus;

class CreateSubscriptionCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private SubscriptionService $subscriptionService
    ) {}

    public function handle(CreateSubscriptionCommand $command): Subscription
    {
        // Validate subscription creation
        $this->subscriptionService->validateSubscriptionCreation(
            $command->getUserId(),
            $command->getPanelId(),
            $command->getPlan()
        );

        // Create subscription
        $subscription = Subscription::create(
            SubscriptionId::generate(),
            $command->getUserId(),
            $command->getPanelId(),
            $command->getPlan(),
            $command->getAmount(),
            SubscriptionStatus::active(),
            $command->getStartDate(),
            $command->getEndDate(),
            $command->getMetadata()
        );

        // Save subscription
        $this->subscriptionRepository->save($subscription);

        return $subscription;
    }
}