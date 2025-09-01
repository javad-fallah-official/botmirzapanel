<?php

namespace BotMirzaPanel\Application\Commands\Panel;

use BotMirzaPanel\Application\Commands\CommandHandlerInterface;
use BotMirzaPanel\Domain\Entities\Panel\Panel;
use BotMirzaPanel\Domain\Repositories\PanelRepositoryInterface;
use BotMirzaPanel\Domain\ValueObjects\Panel\PanelId;
use BotMirzaPanel\Domain\Services\Panel\PanelService;

class CreatePanelCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private PanelRepositoryInterface $panelRepository,
        private PanelService $panelService
    ) {}

    public function handle(CreatePanelCommand $command): PanelId
    {
        $panelId = PanelId::generate();
        
        $panel = new Panel(
            $panelId,
            $command->getUserId(),
            $command->getName(),
            $command->getType(),
            $command->getConfiguration(),
            $command->getDescription()
        );

        // Validate panel configuration through domain service
        $this->panelService->validateConfiguration($panel);

        $this->panelRepository->save($panel);

        return $panelId;
    }
}