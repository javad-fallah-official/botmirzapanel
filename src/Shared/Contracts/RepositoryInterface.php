<?php

declare(strict_types=1);

namespace BotMirzaPanel\Shared\Contracts;

/**
 * Base repository interface defining common CRUD operations
 * 
 * @template T The entity type this repository manages
 */
interface RepositoryInterface
{
    /**
     * Find an entity by its identifier
     * 
     * @param mixed $id The entity identifier
     * @return T|null The entity or null if not found
     */
    public function findById(mixed $id): ?object;

    /**
     * Find all entities matching the given criteria
     * 
     * @param array $criteria Search criteria
     * @param array|null $orderBy Sorting criteria
     * @param int|null $limit Maximum number of results
     * @param int|null $offset Starting offset
     * @return T[] Array of entities
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /**
     * Find a single entity matching the given criteria
     * 
     * @param array $criteria Search criteria
     * @return T|null The entity or null if not found
     */
    public function findOneBy(array $criteria): ?object;

    /**
     * Find all entities
     * 
     * @return T[] Array of all entities
     */
    public function findAll(): array;

    /**
     * Save an entity
     * 
     * @param T $entity The entity to save
     * @return T The saved entity
     */
    public function save(object $entity): object;

    /**
     * Remove an entity
     * 
     * @param T $entity The entity to remove
     * @return void
     */
    public function remove(object $entity): void;

    /**
     * Count entities matching the given criteria
     * 
     * @param array $criteria Search criteria
     * @return int Number of matching entities
     */
    public function count(array $criteria = []): int;
}