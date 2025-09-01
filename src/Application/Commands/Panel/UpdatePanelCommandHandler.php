<?php

namespace BotMirzaPanel\Application\Commands\Panel;

use BotMirzaPanel\Application\Commands\CommandHandlerInterface;
use BotMirzaPanel\Domain\Repositories\PanelRepositoryInterface;
use BotMirzaPanel\Domain\Services\Panel\PanelService;
use BotMirzaPanel\Domain\Exceptions\EntityNotFoundException;

class UpdatePanelCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private PanelRepositoryInterface $panelRepository,
        private PanelService $panelService
    ) {}

    public function handle(UpdatePanelCommand $command): bool
    {
        $panel = $this->panelRepository->findById($command->getPanelId());
        
        if (!$panel) {
            throw new EntityNotFoundException(
                'Panel not found: ' . $command->getPanelId()->getValue()
            );
        }

        if ($command->hasName()) {
            $panel->updateName($command->getName());
        }

        if ($command->hasType()) {
            $panel->updateType($command->getType());
        }

        if ($command->hasConfiguration()) {
            $panel->updateConfiguration($command->getConfiguration());
            // Validate new configuration
            $this->panelService->validateConfiguration($panel);
        }

        if ($command->hasDescription()) {
            $panel->updateDescription($command->getDescription());
        }

        $this->panelRepository->save($panel);

        return true;
    }
}