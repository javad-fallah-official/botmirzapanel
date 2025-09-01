<?php

namespace BotMirzaPanel\Application\Queries\Panel;

use BotMirzaPanel\Application\Queries\QueryHandlerInterface;
use BotMirzaPanel\Domain\Entities\Panel\Panel;
use BotMirzaPanel\Domain\Repositories\PanelRepositoryInterface;
use BotMirzaPanel\Domain\Exceptions\EntityNotFoundException;

class GetPanelByIdQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private PanelRepositoryInterface $panelRepository
    ) {}

    public function handle(GetPanelByIdQuery $query): ?Panel
    {
        $panel = $this->panelRepository->findById($query->getPanelId());
        
        if (!$panel) {
            throw new EntityNotFoundException(
                'Panel not found: ' . $query->getPanelId()->getValue()
            );
        }

        return $panel;
    }
}