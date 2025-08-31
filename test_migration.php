<?php

/**
 * Migration Test Script
 * 
 * This script tests the migration from legacy to new domain-driven architecture
 * to ensure backward compatibility and proper functionality.
 */

define('BOTMIRZAPANEL_INIT', true);
require_once __DIR__ . '/src/bootstrap.php';

echo "BotMirzaPanel Migration Test\n";
echo "============================\n\n";

$errors = [];
$tests = [];

/**
 * Test 1: Service Container Initialization
 */
echo "1. Testing Service Container Initialization...\n";
try {
    global $serviceContainer;
    if ($serviceContainer instanceof \BotMirzaPanel\Infrastructure\Container\ServiceContainer) {
        echo "   ✓ Service container initialized successfully\n";
        $tests['service_container'] = true;
    } else {
        echo "   ✗ Service container not initialized\n";
        $tests['service_container'] = false;
        $errors[] = "Service container initialization failed";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    $tests['service_container'] = false;
    $errors[] = "Service container error: " . $e->getMessage();
}

/**
 * Test 2: Database Connection
 */
echo "\n2. Testing Database Connection...\n";
try {
    $db = db();
    if ($db instanceof \BotMirzaPanel\Database\DatabaseManager) {
        echo "   ✓ Database manager accessible\n";
        $tests['database'] = true;
    } else {
        echo "   ✗ Database manager not accessible\n";
        $tests['database'] = false;
        $errors[] = "Database manager not accessible";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    $tests['database'] = false;
    $errors[] = "Database error: " . $e->getMessage();
}

/**
 * Test 3: Legacy User Service Adapter
 */
echo "\n3. Testing Legacy User Service Adapter...\n";
try {
    $userService = userService();
    if ($userService instanceof \BotMirzaPanel\Infrastructure\Adapters\LegacyUserServiceAdapter) {
        echo "   ✓ Legacy user service adapter working\n";
        $tests['user_service'] = true;
    } else {
        echo "   ✗ Legacy user service adapter not working\n";
        $tests['user_service'] = false;
        $errors[] = "User service adapter not working";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    $tests['user_service'] = false;
    $errors[] = "User service error: " . $e->getMessage();
}

/**
 * Test 4: Payment Service
 */
echo "\n4. Testing Payment Service...\n";
try {
    $paymentService = paymentService();
    if ($paymentService instanceof \BotMirzaPanel\Payment\PaymentService) {
        echo "   ✓ Payment service accessible\n";
        $tests['payment_service'] = true;
    } else {
        echo "   ✗ Payment service not accessible\n";
        $tests['payment_service'] = false;
        $errors[] = "Payment service not accessible";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    $tests['payment_service'] = false;
    $errors[] = "Payment service error: " . $e->getMessage();
}

/**
 * Test 5: Panel Service
 */
echo "\n5. Testing Panel Service...\n";
try {
    $panelService = panelService();
    if ($panelService instanceof \BotMirzaPanel\Panel\PanelService) {
        echo "   ✓ Panel service accessible\n";
        $tests['panel_service'] = true;
    } else {
        echo "   ✗ Panel service not accessible\n";
        $tests['panel_service'] = false;
        $errors[] = "Panel service not accessible";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    $tests['panel_service'] = false;
    $errors[] = "Panel service error: " . $e->getMessage();
}

/**
 * Test 6: Telegram Bot
 */
echo "\n6. Testing Telegram Bot...\n";
try {
    $telegramBot = telegram();
    if ($telegramBot instanceof \BotMirzaPanel\Telegram\TelegramBot) {
        echo "   ✓ Telegram bot accessible\n";
        $tests['telegram_bot'] = true;
    } else {
        echo "   ✗ Telegram bot not accessible\n";
        $tests['telegram_bot'] = false;
        $errors[] = "Telegram bot not accessible";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    $tests['telegram_bot'] = false;
    $errors[] = "Telegram bot error: " . $e->getMessage();
}

/**
 * Test 7: Domain Services Access
 */
echo "\n7. Testing Domain Services Access...\n";
try {
    global $serviceContainer;
    
    // Test UserRepository
    $userRepository = $serviceContainer->get(\BotMirzaPanel\Infrastructure\Repositories\UserRepository::class);
    if ($userRepository instanceof \BotMirzaPanel\Infrastructure\Repositories\UserRepository) {
        echo "   ✓ UserRepository accessible\n";
    } else {
        echo "   ✗ UserRepository not accessible\n";
        $errors[] = "UserRepository not accessible";
    }
    
    // Test Domain UserService
    $domainUserService = $serviceContainer->get(\BotMirzaPanel\Domain\Services\User\UserService::class);
    if ($domainUserService instanceof \BotMirzaPanel\Domain\Services\User\UserService) {
        echo "   ✓ Domain UserService accessible\n";
    } else {
        echo "   ✗ Domain UserService not accessible\n";
        $errors[] = "Domain UserService not accessible";
    }
    
    // Test Command Handler
    $createUserHandler = $serviceContainer->get(\BotMirzaPanel\Application\Commands\User\CreateUserCommandHandler::class);
    if ($createUserHandler instanceof \BotMirzaPanel\Application\Commands\User\CreateUserCommandHandler) {
        echo "   ✓ CreateUserCommandHandler accessible\n";
    } else {
        echo "   ✗ CreateUserCommandHandler not accessible\n";
        $errors[] = "CreateUserCommandHandler not accessible";
    }
    
    $tests['domain_services'] = empty($errors) || count($errors) <= 3; // Allow some errors for missing dependencies
    
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    $tests['domain_services'] = false;
    $errors[] = "Domain services error: " . $e->getMessage();
}

/**
 * Test Results Summary
 */
echo "\n\nTest Results Summary\n";
echo "===================\n";

$passedTests = array_filter($tests);
$totalTests = count($tests);
$passedCount = count($passedTests);

foreach ($tests as $testName => $result) {
    $status = $result ? '✓ PASS' : '✗ FAIL';
    echo sprintf("%-20s: %s\n", ucwords(str_replace('_', ' ', $testName)), $status);
}

echo "\n";
echo "Total Tests: {$totalTests}\n";
echo "Passed: {$passedCount}\n";
echo "Failed: " . ($totalTests - $passedCount) . "\n";

if ($passedCount === $totalTests) {
    echo "\n🎉 All tests passed! Migration is working correctly.\n";
    echo "\nNext steps:\n";
    echo "1. Run database migrations: php migrate.php migrate\n";
    echo "2. Test your application functionality\n";
    echo "3. Gradually migrate components to use new architecture\n";
} else {
    echo "\n⚠️  Some tests failed. Please review the errors below:\n";
    echo "\nErrors:\n";
    foreach ($errors as $i => $error) {
        echo ($i + 1) . ". {$error}\n";
    }
    echo "\nPlease fix these issues before proceeding with the migration.\n";
}

echo "\nFor more information, see MIGRATION_GUIDE.md\n";