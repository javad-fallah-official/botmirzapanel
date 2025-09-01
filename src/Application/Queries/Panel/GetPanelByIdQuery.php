<?php

namespace BotMirzaPanel\Application\Queries\Panel;

use BotMirzaPanel\Application\Queries\QueryInterface;
use BotMirzaPanel\Domain\ValueObjects\Panel\PanelId;

class GetPanelByIdQuery implements QueryInterface
{
    public function __construct(
        private PanelId $panelId
    ) {}

    public function getPanelId(): PanelId
    {
        return $this->panelId;
    }
}