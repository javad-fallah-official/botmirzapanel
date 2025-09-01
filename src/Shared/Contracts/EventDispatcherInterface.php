<?php

declare(strict_types=1);

namespace BotMirzaPanel\Shared\Contracts;

/**
 * Event dispatcher interface for decoupled communication between components
 */
interface EventDispatcherInterface
{
    /**
     * Dispatch an event to all registered listeners
     * 
     * @param string $eventName The name of the event
     * @param mixed $eventData Data to pass to event listeners
     * @return void
     */
    public function dispatch(string $eventName, mixed $eventData = null): void;

    /**
     * Register an event listener
     * 
     * @param string $eventName The event name to listen for
     * @param callable $listener The listener callback
     * @param int $priority Priority level (higher = earlier execution)
     * @return void
     */
    public function addListener(string $eventName, callable $listener, int $priority = 0): void;

    /**
     * Remove an event listener
     * 
     * @param string $eventName The event name
     * @param callable $listener The listener to remove
     * @return void
     */
    public function removeListener(string $eventName, callable $listener): void;

    /**
     * Check if there are listeners for an event
     * 
     * @param string $eventName The event name
     * @return bool True if listeners exist
     */
    public function hasListeners(string $eventName): bool;

    /**
     * Get all listeners for an event
     * 
     * @param string $eventName The event name
     * @return callable[] Array of listeners
     */
    public function getListeners(string $eventName): array;
}