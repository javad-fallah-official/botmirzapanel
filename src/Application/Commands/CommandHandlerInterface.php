<?php

declare(strict_types=1);

namespace Application\Commands;

/**
 * Base interface for all command handlers in the CQRS pattern
 */
interface CommandHandlerInterface
{
    /**
     * Handle the command and return the result
     */
    public function handle(CommandInterface $command): mixed;
}