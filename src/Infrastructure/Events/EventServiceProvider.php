<?php

declare(strict_types=1);

namespace BotMirzaPanel\Infrastructure\Events;

use BotMirzaPanel\Application\EventListeners\UserEventListeners;
use BotMirzaPanel\Application\EventListeners\PaymentEventListeners;
use BotMirzaPanel\Application\EventListeners\SubscriptionEventListeners;
use BotMirzaPanel\Domain\Events\EventDispatcher;
use Psr\Log\LoggerInterface;

/**
 * Event Service Provider
 * 
 * Registers all event listeners with the event dispatcher
 */
class EventServiceProvider
{
    private EventDispatcher $eventDispatcher;
    private UserEventListeners $userEventListeners;
    private PaymentEventListeners $paymentEventListeners;
    private SubscriptionEventListeners $subscriptionEventListeners;
    
    public function __construct(
        EventDispatcher $eventDispatcher,
        ?LoggerInterface $logger = null
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->userEventListeners = new UserEventListeners($logger);
        $this->paymentEventListeners = new PaymentEventListeners($logger);
        $this->subscriptionEventListeners = new SubscriptionEventListeners($logger);
    }
    
    /**
     * Register all event listeners
     */
    public function register(): void
    {
        $this->registerUserEventListeners();
        $this->registerPaymentEventListeners();
        $this->registerSubscriptionEventListeners();
    }
    
    /**
     * Register user event listeners
     */
    private function registerUserEventListeners(): void
    {
        // User created event
        $this->eventDispatcher->listen(
            'user.created',
            [$this->userEventListeners, 'onUserCreated']
        );
        
        // User profile updated event
        $this->eventDispatcher->listen(
            'user.profile_updated',
            [$this->userEventListeners, 'onUserProfileUpdated']
        );
        
        // User blocked event
        $this->eventDispatcher->listen(
            'user.blocked',
            [$this->userEventListeners, 'onUserBlocked']
        );
        
        // User unblocked event
        $this->eventDispatcher->listen(
            'user.unblocked',
            [$this->userEventListeners, 'onUserUnblocked']
        );
    }
    
    /**
     * Register payment event listeners
     */
    private function registerPaymentEventListeners(): void
    {
        // Payment created event
        $this->eventDispatcher->listen(
            'payment.created',
            [$this->paymentEventListeners, 'onPaymentCreated']
        );
        
        // Payment completed event
        $this->eventDispatcher->listen(
            'payment.completed',
            [$this->paymentEventListeners, 'onPaymentCompleted']
        );
        
        // Payment failed event
        $this->eventDispatcher->listen(
            'payment.failed',
            [$this->paymentEventListeners, 'onPaymentFailed']
        );
        
        // Payment refunded event
        $this->eventDispatcher->listen(
            'payment.refunded',
            [$this->paymentEventListeners, 'onPaymentRefunded']
        );
    }
    
    /**
     * Register subscription event listeners
     */
    private function registerSubscriptionEventListeners(): void
    {
        // Subscription created event
        $this->eventDispatcher->listen(
            'subscription.created',
            [$this->subscriptionEventListeners, 'onSubscriptionCreated']
        );
        
        // Subscription renewed event
        $this->eventDispatcher->listen(
            'subscription.renewed',
            [$this->subscriptionEventListeners, 'onSubscriptionRenewed']
        );
        
        // Subscription cancelled event
        $this->eventDispatcher->listen(
            'subscription.cancelled',
            [$this->subscriptionEventListeners, 'onSubscriptionCancelled']
        );
        
        // Subscription expired event
        $this->eventDispatcher->listen(
            'subscription.expired',
            [$this->subscriptionEventListeners, 'onSubscriptionExpired']
        );
        
        // Subscription suspended event
        $this->eventDispatcher->listen(
            'subscription.suspended',
            [$this->subscriptionEventListeners, 'onSubscriptionSuspended']
        );
        
        // Subscription reactivated event
        $this->eventDispatcher->listen(
            'subscription.reactivated',
            [$this->subscriptionEventListeners, 'onSubscriptionReactivated']
        );
    }
    
    /**
     * Get the event dispatcher
     */
    public function getEventDispatcher(): EventDispatcher
    {
        return $this->eventDispatcher;
    }
    
    /**
     * Get user event listeners
     */
    public function getUserEventListeners(): UserEventListeners
    {
        return $this->userEventListeners;
    }
    
    /**
     * Get payment event listeners
     */
    public function getPaymentEventListeners(): PaymentEventListeners
    {
        return $this->paymentEventListeners;
    }
    
    /**
     * Get subscription event listeners
     */
    public function getSubscriptionEventListeners(): SubscriptionEventListeners
    {
        return $this->subscriptionEventListeners;
    }
}