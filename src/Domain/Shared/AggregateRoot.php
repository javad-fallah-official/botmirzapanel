<?php

namespace BotMirzaPanel\Domain\Shared;

use BotMirzaPanel\Domain\Events\DomainEvent;

/**
 * Base Aggregate Root
 * 
 * Provides domain event management capabilities for aggregate roots.
 */
abstract class AggregateRoot
{
    /** @var DomainEvent[] */
    private array $domainEvents = [];

    /**
     * Record a domain event
     */
    protected function recordEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * Get all recorded domain events
     * 
     * @return DomainEvent[]
     */
    public function getRecordedEvents(): array
    {
        return $this->domainEvents;
    }

    /**
     * Clear all recorded domain events
     */
    public function clearRecordedEvents(): void
    {
        $this->domainEvents = [];
    }

    /**
     * Check if there are any recorded events
     */
    public function hasRecordedEvents(): bool
    {
        return !empty($this->domainEvents);
    }

    /**
     * Get the count of recorded events
     */
    public function getRecordedEventsCount(): int
    {
        return count($this->domainEvents);
    }
}