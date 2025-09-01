<?php

namespace BotMirzaPanel\Application\Commands\Panel;

use BotMirzaPanel\Application\Commands\CommandInterface;
use BotMirzaPanel\Domain\ValueObjects\User\UserId;
use BotMirzaPanel\Domain\ValueObjects\Panel\PanelType;

class CreatePanelCommand implements CommandInterface
{
    public function __construct(
        private UserId $userId,
        private string $name,
        private PanelType $type,
        private array $configuration = [],
        private ?string $description = null
    ) {}

    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): PanelType
    {
        return $this->type;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}