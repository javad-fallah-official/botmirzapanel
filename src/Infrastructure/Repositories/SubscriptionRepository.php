<?php

namespace BotMirzaPanel\Infrastructure\Repositories;

use BotMirzaPanel\Domain\Entities\Subscription\Subscription;
use BotMirzaPanel\Domain\Repositories\SubscriptionRepositoryInterface;
use BotMirzaPanel\Domain\ValueObjects\Subscription\SubscriptionId;
use BotMirzaPanel\Domain\ValueObjects\User\UserId;
use BotMirzaPanel\Database\BaseRepository;
use BotMirzaPanel\Database\DatabaseManager;

class SubscriptionRepository extends BaseRepository implements SubscriptionRepositoryInterface
{
    public function __construct()
    {
        parent::__construct($databaseManager, 'subscriptions');
    }

    public function findById(SubscriptionId $id): ?Subscription
    {
        $data = $this->findOneBy(['id' => $id->getValue()]);
        
        return $data ? $this->mapToEntity($data) : null;
    }

    public function findByUserId(UserId $userId): array
    {
        $data = $this->findBy(['user_id' => $userId->getValue()]);
        
        return array_map([$this, 'mapToEntity'], $data);
    }

    public function findActiveByUserId(UserId $userId): array
    {
        $data = $this->findBy([
            'user_id' => $userId->getValue(),
            'status' => 'active'
        ]);
        
        return array_map([$this, 'mapToEntity'], $data);
    }

    public function save(Subscription $subscription): void
    {
        $data = $this->mapToArray($subscription);
        
        if ($this->exists(['id' => $subscription->getId()->getValue()])) {
            $this->update($data, ['id' => $subscription->getId()->getValue()]);
        } else {
            $this->insert($data);
        }
    }

    public function delete(SubscriptionId $id): void
    {
        $this->deleteBy(['id' => $id->getValue()]);
    }

    public function findBy(array $criteria, array $orderBy = [], int $limit = null, int $offset = null): array
    {
        $data = parent::findBy($criteria, $orderBy, $limit, $offset);
        
        return array_map([$this, 'mapToEntity'], $data);
    }

    private function mapToEntity(array $data): Subscription
    {
        // This would need to be implemented based on the Subscription entity structure
        // For now, returning a basic implementation
        return new Subscription(
            new SubscriptionId($data['id']),
            new UserId($data['user_id']),
            $data['type'],
            $data['status'],
            new \DateTime($data['start_date']),
            $data['end_date'] ? new \DateTime($data['end_date']) : null
        );
    }

    private function mapToArray(Subscription $subscription): array
    {
        return [
            'id' => $subscription->getId()->getValue(),
            'user_id' => $subscription->getUserId()->getValue(),
            'type' => $subscription->getType(),
            'status' => $subscription->getStatus(),
            'start_date' => $subscription->getStartDate()->format('Y-m-d H:i:s'),
            'end_date' => $subscription->getEndDate()?->format('Y-m-d H:i:s'),
            'created_at' => $subscription->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $subscription->getUpdatedAt()->format('Y-m-d H:i:s')
        ];
    }
}