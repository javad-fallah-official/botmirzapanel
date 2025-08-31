<?php

declare(strict_types=1);

namespace BotMirzaPanel\Application\Queries;

/**
 * Base interface for all query handlers in the CQRS pattern
 */
interface QueryHandlerInterface
{
    /**
     * Handle the query and return the result
     */
    public function handle(QueryInterface $query): mixed;
}