<?php

namespace BotMirzaPanel\Application\Queries\Payment;

use BotMirzaPanel\Application\Queries\QueryHandlerInterface;
use BotMirzaPanel\Domain\Repositories\PaymentRepositoryInterface;

class GetPaymentsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository
    ) {}

    public function handle(GetPaymentsQuery $query): array
    {
        return $this->paymentRepository->findBy(
            $query->getFilters(),
            $query->getOrderBy(),
            $query->getLimit(),
            $query->getOffset()
        );
    }
}