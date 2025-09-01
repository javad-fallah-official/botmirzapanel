<?php

namespace BotMirzaPanel\Domain\Events;

use DateTime;

/**
 * Abstract Domain Event
 * 
 * Base implementation for domain events providing common functionality.
 */
abstract class AbstractDomainEvent implements DomainEvent
{
    private string $eventId;
    private DateTime $occurredAt;
    private int $version;
    protected string $aggregateId;
    protected array $payload;

    public function __construct(int $version = 1): void
    {
        $this->eventId = $this->generateEventId();
        $this->occurredAt = new DateTime();
        $this->version = $version;
        $this->payload = [];
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getOccurredAt(): DateTime
    {
        return $this->occurredAt;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function toArray(): array
    {
        return [
            'event_id' => $this->getEventId(),
            'event_name' => $this->getEventName(),
            'aggregate_id' => $this->getAggregateId(),
            'aggregate_type' => $this->getAggregateType(),
            'payload' => $this->getPayload(),
            'version' => $this->getVersion(),
            'occurred_at' => $this->getOccurredAt()->format('Y-m-d H:i:s.u'),
        ];
    }

    /**
     * Generate a unique event ID
     */
    private function generateEventId(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Get the aggregate ID
     */
    public function getAggregateId(): string
    {
        return $this->aggregateId;
    }
    
    /**
     * Get the event payload
     */
    public function getPayload(): array
    {
        return $this->payload;
    }
    
    /**
     * Abstract methods that must be implemented by concrete events
     */
    abstract public function getEventName(): string;
    abstract public function getAggregateType(): string;
}