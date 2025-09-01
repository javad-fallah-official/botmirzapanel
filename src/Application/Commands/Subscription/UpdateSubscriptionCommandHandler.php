<?php

namespace BotMirzaPanel\Application\Commands\Subscription;

use BotMirzaPanel\Application\Commands\CommandHandlerInterface;
use BotMirzaPanel\Domain\Entities\Subscription\Subscription;
use BotMirzaPanel\Domain\Repositories\SubscriptionRepositoryInterface;
use BotMirzaPanel\Domain\Services\Subscription\SubscriptionService;
use BotMirzaPanel\Domain\Exceptions\EntityNotFoundException;

class UpdateSubscriptionCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private SubscriptionService $subscriptionService
    ) {}

    public function handle(UpdateSubscriptionCommand $command): Subscription
    {
        // Find subscription
        $subscription = $this->subscriptionRepository->findById($command->getSubscriptionId());
        
        if (!$subscription) {
            throw new EntityNotFoundException(
                'Subscription not found: ' . $command->getSubscriptionId()->getValue()
            );
        }

        // Update subscription properties
        if ($command->hasPlan()) {
            $subscription->updatePlan($command->getPlan());
        }

        if ($command->hasAmount()) {
            $subscription->updateAmount($command->getAmount());
        }

        if ($command->hasStatus()) {
            $subscription->updateStatus($command->getStatus());
        }

        if ($command->hasEndDate()) {
            $subscription->updateEndDate($command->getEndDate());
        }

        if ($command->hasMetadata()) {
            $subscription->updateMetadata($command->getMetadata());
        }

        // Validate subscription update
        $this->subscriptionService->validateSubscriptionUpdate($subscription);

        // Save subscription
        $this->subscriptionRepository->save($subscription);

        return $subscription;
    }
}