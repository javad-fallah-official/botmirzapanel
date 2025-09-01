<?php

declare(strict_types=1);

namespace BotMirzaPanel\Infrastructure\Events;

use BotMirzaPanel\Domain\Events\DomainEvent;
use BotMirzaPanel\Domain\Events\EventDispatcher;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * In-Memory Event Dispatcher
 * 
 * Simple implementation that dispatches events synchronously in memory
 */
class InMemoryEventDispatcher implements EventDispatcher
{
    private array $listeners = [];
    private LoggerInterface $logger;
    
    public function __construct(?LoggerInterface $logger = null): void
    {
        $this->logger = $logger ?? new NullLogger();
    }
    
    public function dispatch(DomainEvent $event): void
    {
        $eventName = $event->getEventName();
        
        $this->logger->info('Dispatching domain event', [
            'event_name' => $eventName,
            'event_id' => $event->getEventId(),
            'aggregate_id' => $event->getAggregateId(),
            'aggregate_type' => $event->getAggregateType(),
        ]);
        
        $listeners = $this->getListeners($eventName);
        
        foreach ($listeners as $listener) {
            try {
                $listener($event);
            } catch (\Throwable $e) {
                $this->logger->error('Error handling domain event', [
                    'event_name' => $eventName,
                    'event_id' => $event->getEventId(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                // Continue processing other listeners even if one fails
                continue;
            }
        }
    }
    
    public function dispatchAll(array $events): void
    {
        foreach ($events as $event) {
            if ($event instanceof DomainEvent) {
                $this->dispatch($event);
            }
        }
    }
    
    public function listen(string $eventName, callable $listener): void
    {
        if (!isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = [];
        }
        
        $this->listeners[$eventName][] = $listener;
    }
    
    public function forget(string $eventName, callable $listener): void
    {
        if (!isset($this->listeners[$eventName])) {
            return;
        }
        
        $this->listeners[$eventName] = array_filter(
            $this->listeners[$eventName],
            fn($registeredListener) => $registeredListener !== $listener
        );
        
        // Clean up empty arrays
        if (empty($this->listeners[$eventName])) {
            unset($this->listeners[$eventName]);
        }
    }
    
    public function getListeners(string $eventName): array
    {
        return $this->listeners[$eventName] ?? [];
    }
    
    /**
     * Get all registered listeners
     */
    public function getAllListeners(): array
    {
        return $this->listeners;
    }
    
    /**
     * Clear all listeners
     */
    public function clearListeners(): void
    {
        $this->listeners = [];
    }
    
    /**
     * Get the count of listeners for a specific event
     */
    public function getListenerCount(string $eventName): int
    {
        return count($this->getListeners($eventName));
    }
}