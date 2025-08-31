<?php

declare(strict_types=1);

namespace BotMirzaPanel\Application\Queries\User;

use BotMirzaPanel\Application\Queries\QueryInterface;

/**
 * Query to get users with optional filtering and pagination
 */
class GetUsersQuery implements QueryInterface
{
    private ?int $limit;
    private ?int $offset;
    private array $filters;
    private ?array $orderBy;

    public function __construct(
        ?int $limit = null,
        ?int $offset = null,
        array $filters = [],
        ?array $orderBy = null
    ) {
        $this->limit = $limit;
        $this->offset = $offset;
        $this->filters = $filters;
        $this->orderBy = $orderBy;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getOrderBy(): ?array
    {
        return $this->orderBy;
    }

    public function toArray(): array
    {
        return [
            'limit' => $this->limit,
            'offset' => $this->offset,
            'filters' => $this->filters,
            'order_by' => $this->orderBy
        ];
    }
}