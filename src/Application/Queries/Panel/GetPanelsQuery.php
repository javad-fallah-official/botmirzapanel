<?php

namespace BotMirzaPanel\Application\Queries\Panel;

use BotMirzaPanel\Application\Queries\QueryInterface;
use BotMirzaPanel\Domain\ValueObjects\User\UserId;
use BotMirzaPanel\Domain\ValueObjects\Panel\PanelType;

class GetPanelsQuery implements QueryInterface
{
    public function __construct(
        private ?UserId $userId = null,
        private ?PanelType $type = null,
        private ?string $status = null,
        private int $limit = 50,
        private int $offset = 0,
        private array $orderBy = ['created_at' => 'DESC']
    ) {}

    public function getUserId(): ?UserId
    {
        return $this->userId;
    }

    public function getType(): ?PanelType
    {
        return $this->type;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    public function getFilters(): array
    {
        $filters = [];
        
        if ($this->userId) {
            $filters['user_id'] = $this->userId;
        }
        
        if ($this->type) {
            $filters['type'] = $this->type;
        }
        
        if ($this->status) {
            $filters['status'] = $this->status;
        }
        
        return $filters;
    }
}