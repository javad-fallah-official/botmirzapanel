<?php

namespace BotMirzaPanel\Application\Queries\Payment;

use BotMirzaPanel\Application\Queries\QueryInterface;
use BotMirzaPanel\Domain\ValueObjects\User\UserId;
use BotMirzaPanel\Domain\ValueObjects\Payment\PaymentStatus;
use BotMirzaPanel\Domain\ValueObjects\Payment\PaymentMethod;

class GetPaymentsQuery implements QueryInterface
{
    public function __construct(
        private ?UserId $userId = null,
        private ?PaymentStatus $status = null,
        private ?PaymentMethod $method = null,
        private int $limit = 50,
        private int $offset = 0,
        private array $orderBy = ['created_at' => 'DESC']
    ) {}

    public function getUserId(): ?UserId
    {
        return $this->userId;
    }

    public function getStatus(): ?PaymentStatus
    {
        return $this->status;
    }

    public function getMethod(): ?PaymentMethod
    {
        return $this->method;
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
        
        if ($this->status) {
            $filters['status'] = $this->status;
        }
        
        if ($this->method) {
            $filters['method'] = $this->method;
        }
        
        return $filters;
    }
}