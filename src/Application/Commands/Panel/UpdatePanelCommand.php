<?php

namespace BotMirzaPanel\Application\Commands\Panel;

use BotMirzaPanel\Application\Commands\CommandInterface;
use BotMirzaPanel\Domain\ValueObjects\Panel\PanelId;
use BotMirzaPanel\Domain\ValueObjects\Panel\PanelType;

class UpdatePanelCommand implements CommandInterface
{
    public function __construct(
        private PanelId $panelId,
        private ?string $name = null,
        private ?PanelType $type = null,
        private ?array $configuration = null,
        private ?string $description = null
    ) {}

    public function getPanelId(): PanelId
    {
        return $this->panelId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getType(): ?PanelType
    {
        return $this->type;
    }

    public function getConfiguration(): ?array
    {
        return $this->configuration;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function hasName(): bool
    {
        return $this->name !== null;
    }

    public function hasType(): bool
    {
        return $this->type !== null;
    }

    public function hasConfiguration(): bool
    {
        return $this->configuration !== null;
    }

    public function hasDescription(): bool
    {
        return $this->description !== null;
    }
}