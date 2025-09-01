<?php

namespace BotMirzaPanel\Application\Queries\Panel;

use BotMirzaPanel\Application\Queries\QueryHandlerInterface;
use BotMirzaPanel\Domain\Repositories\PanelRepositoryInterface;

class GetPanelsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private PanelRepositoryInterface $panelRepository
    ) {}

    public function handle(GetPanelsQuery $query): array
    {
        return $this->panelRepository->findBy(
            $query->getFilters(),
            $query->getOrderBy(),
            $query->getLimit(),
            $query->getOffset()
        );
    }
}