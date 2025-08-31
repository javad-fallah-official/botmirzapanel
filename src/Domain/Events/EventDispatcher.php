<?php

declare(strict_types=1);

namespace BotMirzaPanel\Domain\Events;

/**
 * Event Dispatcher Interface
 * 
 * Responsible for dispatching domain events to their respective handlers
 */
interface EventDispatcher
{
    /**
     * Dispatch a single domain event
     */
    public function dispatch(DomainEvent $event): void;
    
    /**
     * Dispatch multiple domain events
     * 
     * @param DomainEvent[] $events
     */
    public function dispatchAll(array $events): void;
    
    /**
     * Register an event listener for a specific event type
     */
    public function listen(string $eventName, callable $listener): void;
    
    /**
     * Remove an event listener
     */
    public function forget(string $eventName, callable $listener): void;
    
    /**
     * Get all listeners for a specific event
     */
    public function getListeners(string $eventName): array;
}