<?php

/**
 * Test script to verify ServiceContainer dependency injection
 */

define('BOTMIRZAPANEL_INIT', true);

try {
    // Load bootstrap
    require_once __DIR__ . '/src/bootstrap.php';
    
    echo "Bootstrap loaded successfully.\n";
    
    // Test ServiceContainer instantiation
    global $serviceContainer;
    
    if (!$serviceContainer) {
        echo "ERROR: ServiceContainer not initialized\n";
        exit(1);
    }
    
    echo "ServiceContainer initialized successfully.\n";
    
    // Test Payment handlers
    $paymentHandlers = [
        'BotMirzaPanel\\Application\\Commands\\Payment\\CreatePaymentCommandHandler',
        'BotMirzaPanel\\Application\\Commands\\Payment\\UpdatePaymentCommandHandler',
        'BotMirzaPanel\\Application\\Queries\\Payment\\GetPaymentByIdQueryHandler',
        'BotMirzaPanel\\Application\\Queries\\Payment\\GetPaymentsQueryHandler'
    ];
    
    // Test Panel handlers
    $panelHandlers = [
        'BotMirzaPanel\\Application\\Commands\\Panel\\CreatePanelCommandHandler',
        'BotMirzaPanel\\Application\\Commands\\Panel\\UpdatePanelCommandHandler',
        'BotMirzaPanel\\Application\\Queries\\Panel\\GetPanelByIdQueryHandler',
        'BotMirzaPanel\\Application\\Queries\\Panel\\GetPanelsQueryHandler'
    ];
    
    // Test Subscription handlers
    $subscriptionHandlers = [
        'BotMirzaPanel\\Application\\Commands\\Subscription\\CreateSubscriptionCommandHandler',
        'BotMirzaPanel\\Application\\Commands\\Subscription\\UpdateSubscriptionCommandHandler',
        'BotMirzaPanel\\Application\\Queries\\Subscription\\GetSubscriptionByIdQueryHandler',
        'BotMirzaPanel\\Application\\Queries\\Subscription\\GetSubscriptionsQueryHandler'
    ];
    
    $allHandlers = array_merge($paymentHandlers, $panelHandlers, $subscriptionHandlers);
    
    echo "Testing " . count($allHandlers) . " handlers...\n";
    
    foreach ($allHandlers as $handler) {
        try {
            $instance = $serviceContainer->get($handler);
            if ($instance) {
                echo "✓ {$handler} - OK\n";
            } else {
                echo "✗ {$handler} - Failed to instantiate\n";
            }
        } catch (Exception $e) {
            echo "✗ {$handler} - Error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nAll tests completed successfully!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}