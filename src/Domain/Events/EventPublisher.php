<?php

declare(strict_types=1);

namespace BotMirzaPanel\Domain\Events;

/**
 * Event Publisher Trait
 * 
 * Provides event publishing capabilities to domain entities
 */
trait EventPublisher
{
    private array $domainEvents = [];
    
    /**
     * Raise a domain event
     */
    protected function raise(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }
    
    /**
     * Get all raised domain events
     * 
     * @return DomainEvent[]
     */
    public function getEvents(): array
    {
        return $this->domainEvents;
    }
    
    /**
     * Clear all domain events
     */
    public function clearEvents(): void
    {
        $this->domainEvents = [];
    }
    
    /**
     * Check if there are any pending events
     */
    public function hasEvents(): bool
    {
        return !empty($this->domainEvents);
    }
    
    /**
     * Get the count of pending events
     */
    public function getEventCount(): int
    {
        return count($this->domainEvents);
    }
}