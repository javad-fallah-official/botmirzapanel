<?php

namespace BotMirzaPanel\Infrastructure\Repositories;

use BotMirzaPanel\Domain\Entities\Panel\Panel;
use BotMirzaPanel\Domain\Repositories\PanelRepositoryInterface;
use BotMirzaPanel\Domain\ValueObjects\Panel\PanelId;
use BotMirzaPanel\Database\BaseRepository;
use BotMirzaPanel\Database\DatabaseManager;

class PanelRepository extends BaseRepository implements PanelRepositoryInterface
{
    public function __construct(DatabaseManager $databaseManager): void
    {
        parent::__construct($databaseManager, 'panels');
    }

    public function findById(PanelId $id): ?Panel
    {
        $data = $this->findOneBy(['id' => $id->getValue()]);
        
        return $data ? $this->mapToEntity($data) : null;
    }

    public function findByUserId(string $userId): array
    {
        $data = $this->findBy(['user_id' => $userId]);
        
        return array_map([$this, 'mapToEntity'], $data);
    }

    public function save(Panel $panel): void
    {
        $data = $this->mapToArray($panel);
        
        if ($this->exists(['id' => $panel->getId()->getValue()])) {
            $this->update($data, ['id' => $panel->getId()->getValue()]);
        } else {
            $this->insert($data);
        }
    }

    public function delete(PanelId $id): void
    {
        $this->deleteBy(['id' => $id->getValue()]);
    }

    public function findBy(array $criteria, array $orderBy = [], int $limit = null, int $offset = null): array
    {
        $data = parent::findBy($criteria, $orderBy, $limit, $offset);
        
        return array_map([$this, 'mapToEntity'], $data);
    }

    private function mapToEntity(array $data): Panel
    {
        // This would need to be implemented based on the Panel entity structure
        // For now, returning a basic implementation
        return new Panel(
            new PanelId($data['id']),
            $data['name'],
            $data['type'],
            $data['config'] ?? []
        );
    }

    private function mapToArray(Panel $panel): array
    {
        return [
            'id' => $panel->getId()->getValue(),
            'name' => $panel->getName(),
            'type' => $panel->getType(),
            'config' => json_encode($panel->getConfig()),
            'created_at' => $panel->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $panel->getUpdatedAt()->format('Y-m-d H:i:s')
        ];
    }
}