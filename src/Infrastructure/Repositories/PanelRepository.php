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

    /**
     * Find panel by ID
     * 
     * @param PanelId $id Panel ID
     * @return Panel|null Panel entity or null if not found
     */
    public function findById(PanelId $id): ?Panel
    {
        $data = $this->findOneBy(['id' => $id->getValue()]);
        
        return $data ? $this->mapToEntity($data) : null;
    }

    /**
     * Find panels by user ID
     * 
     * @param string $userId User ID
     * @return array<Panel> Array of panel entities
     */
    public function findByUserId(string $userId): array
    {
        $data = $this->findBy(['user_id' => $userId]);
        
        return array_map([$this, 'mapToEntity'], $data);
    }

    /**
     * Save panel entity
     * 
     * @param Panel $panel Panel entity to save
     * @return void
     */
    public function save(Panel $panel): void
    {
        $data = $this->mapToArray($panel);
        
        if ($this->exists(['id' => $panel->getId()->getValue()])) {
            $this->update($data, ['id' => $panel->getId()->getValue()]);
        } else {
            $this->insert($data);
        }
    }

    /**
     * Delete panel by ID
     * 
     * @param PanelId $id Panel ID
     * @return void
     */
    public function delete(PanelId $id): void
    {
        $this->deleteBy(['id' => $id->getValue()]);
    }

    /**
     * Find panels by criteria
     * 
     * @param array<string, mixed> $criteria Search criteria
     * @param array<string, string> $orderBy Order by fields
     * @param int|null $limit Maximum results
     * @param int|null $offset Results offset
     * @return array<Panel> Panels matching criteria
     */
    public function findBy(array $criteria, array $orderBy = [], int $limit = null, int $offset = null): array
    {
        $data = parent::findBy($criteria, $orderBy, $limit, $offset);
        
        return array_map([$this, 'mapToEntity'], $data);
    }

    /**
     * Map database array to Panel entity
     * 
     * @param array<string, mixed> $data Database row data
     * @return Panel Panel entity
     */
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

    /**
     * Map Panel entity to database array
     * 
     * @param Panel $panel Panel entity
     * @return array<string, mixed> Database array
     */
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