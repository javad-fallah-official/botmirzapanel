<?php

declare(strict_types=1);

namespace BotMirzaPanel\Application\Commands;

/**
 * Base interface for all commands in the CQRS pattern
 */
interface CommandInterface
{
    /**
     * Convert command to array representation
     */
    public function toArray(): array;
}