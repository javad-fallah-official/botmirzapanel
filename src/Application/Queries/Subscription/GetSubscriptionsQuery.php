<?php

namespace BotMirzaPanel\Application\Queries\Subscription;

use BotMirzaPanel\Application\Queries\QueryInterface;
use BotMirzaPanel\Domain\ValueObjects\User\UserId;
use BotMirzaPanel\Domain\ValueObjects\Panel\PanelId;
use BotMirzaPanel\Domain\ValueObjects\Subscription\SubscriptionStatus;
use BotMirzaPanel\Domain\ValueObjects\Subscription\SubscriptionPlan;

class GetSubscriptionsQuery implements QueryInterface
{
    public function __construct(
        private ?UserId $userId = null,
        private ?PanelId $panelId = null,
        private ?SubscriptionStatus $status = null,
        private ?SubscriptionPlan $plan = null,
        private ?bool $activeOnly = null,
        private int $limit = 50,
        private int $offset = 0,
        private array $orderBy = ['created_at' => 'DESC']
    ) {}

    public function getUserId(): ?UserId
    {
        return $this->userId;
    }

    public function getPanelId(): ?PanelId
    {
        return $this->panelId;
    }

    public function getStatus(): ?SubscriptionStatus
    {
        return $this->status;
    }

    public function getPlan(): ?SubscriptionPlan
    {
        return $this->plan;
    }

    public function getActiveOnly(): ?bool
    {
        return $this->activeOnly;
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
        
        if ($this->panelId) {
            $filters['panel_id'] = $this->panelId;
        }
        
        if ($this->status) {
            $filters['status'] = $this->status;
        }
        
        if ($this->plan) {
            $filters['plan'] = $this->plan;
        }
        
        if ($this->activeOnly !== null) {
            $filters['active_only'] = $this->activeOnly;
        }
        
        return $filters;
    }
}