<?php

declare(strict_types=1);

namespace Application\Queries;

/**
 * Base interface for all queries in the CQRS pattern
 */
interface QueryInterface
{
    /**
     * Convert query to array representation
     */
    public function toArray(): array;
}