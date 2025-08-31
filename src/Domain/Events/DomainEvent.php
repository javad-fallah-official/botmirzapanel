<?php

namespace BotMirzaPanel\Domain\Events;

use DateTime;

/**
 * Domain Event Interface
 * 
 * Base interface for all domain events in the system.
 */
interface DomainEvent
{
    /**
     * Get the unique identifier for this event
     */
    public function getEventId(): string;

    /**
     * Get the name/type of this event
     */
    public function getEventName(): string;

    /**
     * Get when this event occurred
     */
    public function getOccurredAt(): DateTime;

    /**
     * Get the aggregate ID that this event relates to
     */
    public function getAggregateId(): string;

    /**
     * Get the aggregate type that this event relates to
     */
    public function getAggregateType(): string;

    /**
     * Get the event payload/data
     */
    public function getPayload(): array;

    /**
     * Get the event version for schema evolution
     */
    public function getVersion(): int;

    /**
     * Convert the event to an array representation
     */
    public function toArray(): array;
}