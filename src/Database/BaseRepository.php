<?php

namespace BotMirzaPanel\Database;

/**
 * Base repository class providing common CRUD operations
 * Implements repository pattern for clean data access
 */
class BaseRepository
{
    protected DatabaseManager $db;
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];

    public function __construct(DatabaseManager $db, string $table = null): void
    {
        $this->db = $db;
        $this->table = $table ?? $this->getTableName();
    }

    /**
     * Get table name from class name
     */
    protected function getTableName(): string
    {
        $className = (new \ReflectionClass($this))->getShortName();
        return strtolower(str_replace('Repository', '', $className));
    }

    /**
     * Find record by ID
     */
    public function find(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id",
            ['id' => $id]
        );
    }

    /**
     * Find record by criteria
     */
    public function findBy(array $criteria): ?array
    {
        $whereParts = [];
        foreach (array_keys($criteria) as $column) {
            $whereParts[] = "{$column} = :{$column}";
        }
        $whereClause = implode(' AND ', $whereParts);
        
        return $this->db->fetchOne(
            "SELECT * FROM {$this->table} WHERE {$whereClause}",
            $criteria
        );
    }

    /**
     * Find all records by criteria
     */
    public function findAllBy(array $criteria = []): array
    {
        if (empty($criteria)) {
            return $this->db->fetchAll("SELECT * FROM {$this->table}");
        }
        
        $whereParts = [];
        foreach (array_keys($criteria) as $column) {
            $whereParts[] = "{$column} = :{$column}";
        }
        $whereClause = implode(' AND ', $whereParts);
        
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} WHERE {$whereClause}",
            $criteria
        );
    }

    /**
     * Create new record
     */
    public function create(array $data): int
    {
        $filteredData = $this->filterFillable($data);
        return $this->db->insert($this->table, $filteredData);
    }

    /**
     * Update record by ID
     */
    public function update(int $id, array $data): bool
    {
        $filteredData = $this->filterFillable($data);
        $affected = $this->db->update(
            $this->table,
            $filteredData,
            [$this->primaryKey => $id]
        );
        
        return $affected > 0;
    }

    /**
     * Update records by criteria
     */
    public function updateBy(array $criteria, array $data): int
    {
        $filteredData = $this->filterFillable($data);
        return $this->db->update($this->table, $filteredData, $criteria);
    }

    /**
     * Delete record by ID
     */
    public function delete(int $id): bool
    {
        $affected = $this->db->delete($this->table, [$this->primaryKey => $id]);
        return $affected > 0;
    }

    /**
     * Delete records by criteria
     */
    public function deleteBy(array $criteria): int
    {
        return $this->db->delete($this->table, $criteria);
    }

    /**
     * Count records
     */
    public function count(array $criteria = []): int
    {
        if (empty($criteria)) {
            $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM {$this->table}");
        } else {
            $whereParts = [];
            foreach (array_keys($criteria) as $column) {
                $whereParts[] = "{$column} = :{$column}";
            }
            $whereClause = implode(' AND ', $whereParts);
            
            $result = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM {$this->table} WHERE {$whereClause}",
                $criteria
            );
        }
        
        return (int) ($result['count'] ?? 0);
    }

    /**
     * Check if record exists
     */
    public function exists(array $criteria): bool
    {
        return $this->count($criteria) > 0;
    }

    /**
     * Get paginated results
     */
    public function paginate(int $page = 1, int $perPage = 20, array $criteria = []): array
    {
        $offset = ($page - 1) * $perPage;
        
        if (empty($criteria)) {
            $sql = "SELECT * FROM {$this->table} LIMIT :limit OFFSET :offset";
            $params = ['limit' => $perPage, 'offset' => $offset];
        } else {
            $whereParts = [];
            foreach (array_keys($criteria) as $column) {
                $whereParts[] = "{$column} = :{$column}";
            }
            $whereClause = implode(' AND ', $whereParts);
            
            $sql = "SELECT * FROM {$this->table} WHERE {$whereClause} LIMIT :limit OFFSET :offset";
            $params = array_merge($criteria, ['limit' => $perPage, 'offset' => $offset]);
        }
        
        $items = $this->db->fetchAll($sql, $params);
        $total = $this->count($criteria);
        
        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Execute custom query
     */
    public function query(string $sql, array $params = []): array
    {
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Execute custom query and return single result
     */
    public function queryOne(string $sql, array $params = []): ?array
    {
        return $this->db->fetchOne($sql, $params);
    }

    /**
     * Filter data based on fillable fields
     */
    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }

    /**
     * Begin database transaction
     */
    public function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    /**
     * Commit database transaction
     */
    public function commit(): void
    {
        $this->db->commit();
    }

    /**
     * Rollback database transaction
     */
    public function rollback(): void
    {
        $this->db->rollback();
    }
}