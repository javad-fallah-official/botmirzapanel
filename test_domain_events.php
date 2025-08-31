<?php

require_once __DIR__ . '/src/bootstrap.php';

use BotMirzaPanel\Domain\Events\UserCreated;
use BotMirzaPanel\Domain\Events\PaymentCompleted;
use BotMirzaPanel\Domain\Events\SubscriptionCreated;
use BotMirzaPanel\Domain\ValueObjects\Money;
use BotMirzaPanel\Domain\ValueObjects\Email;
use BotMirzaPanel\Infrastructure\Container\ServiceContainer;
use BotMirzaPanel\Domain\Events\EventDispatcher;
use BotMirzaPanel\Infrastructure\Events\EventServiceProvider;

echo "=== Domain Events System Test ===\n\n";

try {
    // Get the service container
    $serviceContainer = app()->get(ServiceContainer::class);
    echo "✓ Service container initialized\n";
    
    // Get event dispatcher
    $eventDispatcher = $serviceContainer->get(EventDispatcher::class);
    echo "✓ Event dispatcher retrieved\n";
    
    // Get event service provider
    $eventServiceProvider = $serviceContainer->get(EventServiceProvider::class);
    echo "✓ Event service provider retrieved\n";
    
    // Test 1: Create and dispatch a UserCreated event
    echo "\n--- Test 1: User Created Event ---\n";
    $userCreatedEvent = UserCreated::create(
        'user_123',
        'testuser',
        'John',
        'Doe',
        new Email('john.doe@example.com'),
        '+1234567890',
        'referrer_456'
    );
    
    echo "Event created: {$userCreatedEvent->getEventName()}\n";
    echo "Event ID: {$userCreatedEvent->getEventId()}\n";
    echo "Aggregate ID: {$userCreatedEvent->getAggregateId()}\n";
    echo "Aggregate Type: {$userCreatedEvent->getAggregateType()}\n";
    
    $eventDispatcher->dispatch($userCreatedEvent);
    echo "✓ User created event dispatched\n";
    
    // Test 2: Create and dispatch a PaymentCompleted event
    echo "\n--- Test 2: Payment Completed Event ---\n";
    $paymentCompletedEvent = PaymentCompleted::create(
        'payment_789',
        'user_123',
        new Money(2500, 'USD'), // $25.00
        'txn_abc123',
        'stripe',
        ['stripe_payment_intent' => 'pi_abc123']
    );
    
    echo "Event created: {$paymentCompletedEvent->getEventName()}\n";
    echo "Event ID: {$paymentCompletedEvent->getEventId()}\n";
    echo "Aggregate ID: {$paymentCompletedEvent->getAggregateId()}\n";
    echo "Aggregate Type: {$paymentCompletedEvent->getAggregateType()}\n";
    
    $eventDispatcher->dispatch($paymentCompletedEvent);
    echo "✓ Payment completed event dispatched\n";
    
    // Test 3: Create and dispatch a SubscriptionCreated event
    echo "\n--- Test 3: Subscription Created Event ---\n";
    $subscriptionCreatedEvent = SubscriptionCreated::create(
        'subscription_456',
        'user_123',
        'plan_premium',
        new Money(2500, 'USD'),
        new \DateTimeImmutable('2024-01-01 00:00:00'),
        new \DateTimeImmutable('2024-02-01 00:00:00'),
        'active'
    );
    
    echo "Event created: {$subscriptionCreatedEvent->getEventName()}\n";
    echo "Event ID: {$subscriptionCreatedEvent->getEventId()}\n";
    echo "Aggregate ID: {$subscriptionCreatedEvent->getAggregateId()}\n";
    echo "Aggregate Type: {$subscriptionCreatedEvent->getAggregateType()}\n";
    
    $eventDispatcher->dispatch($subscriptionCreatedEvent);
    echo "✓ Subscription created event dispatched\n";
    
    // Test 4: Test event listener registration
    echo "\n--- Test 4: Event Listener Registration ---\n";
    $userListeners = $eventDispatcher->getListeners('user.created');
    echo "User created listeners: " . count($userListeners) . "\n";
    
    $paymentListeners = $eventDispatcher->getListeners('payment.completed');
    echo "Payment completed listeners: " . count($paymentListeners) . "\n";
    
    $subscriptionListeners = $eventDispatcher->getListeners('subscription.created');
    echo "Subscription created listeners: " . count($subscriptionListeners) . "\n";
    
    // Test 5: Test batch event dispatching
    echo "\n--- Test 5: Batch Event Dispatching ---\n";
    $events = [
        UserCreated::create('user_999', 'batchuser', 'Jane', 'Smith'),
        PaymentCompleted::create(
            'payment_999',
            'user_999',
            new Money(1000, 'USD'),
            'txn_batch123',
            'paypal'
        )
    ];
    
    $eventDispatcher->dispatchAll($events);
    echo "✓ Batch events dispatched (" . count($events) . " events)\n";
    
    // Test 6: Test event data serialization
    echo "\n--- Test 6: Event Serialization ---\n";
    $eventArray = $userCreatedEvent->toArray();
    echo "Event serialized to array:\n";
    echo "- Event ID: {$eventArray['event_id']}\n";
    echo "- Event Name: {$eventArray['event_name']}\n";
    echo "- Aggregate ID: {$eventArray['aggregate_id']}\n";
    echo "- Aggregate Type: {$eventArray['aggregate_type']}\n";
    echo "- Version: {$eventArray['version']}\n";
    echo "- Occurred At: {$eventArray['occurred_at']}\n";
    echo "- Payload Keys: " . implode(', ', array_keys($eventArray['payload'])) . "\n";
    
    echo "\n=== All Domain Events Tests Passed! ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}