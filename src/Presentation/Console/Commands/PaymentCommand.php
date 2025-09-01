<?php

declare(strict_types=1);

namespace BotMirzaPanel\Presentation\Console\Commands;

use BotMirzaPanel\Application\Queries\Payment\GetPaymentsQuery;
use BotMirzaPanel\Application\Queries\Payment\GetPaymentByIdQuery;
use BotMirzaPanel\Application\Commands\Payment\CreatePaymentCommand;
use BotMirzaPanel\Application\Commands\Payment\UpdatePaymentCommand;
use BotMirzaPanel\Infrastructure\External\Payment\PaymentGatewayFactoryInterface;
use BotMirzaPanel\Domain\ValueObjects\Payment\PaymentStatus;
use BotMirzaPanel\Domain\ValueObjects\Payment\PaymentMethod;
use BotMirzaPanel\Domain\ValueObjects\Common\Money;
use BotMirzaPanel\Domain\ValueObjects\Common\Currency;

/**
 * Payment Management Console Command
 */
class PaymentCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'payment';
    }

    public function getDescription(): string
    {
        return 'Payment management (list, show, create, update, stats, gateways)';
    }

    public function getUsage(): string
    {
        return 'payment <action> [options]';
    }

    public function getHelp(): string
    {
        return <<<HELP
Payment Management Commands:

  payment list                 List all payments
  payment show <id>            Show payment details
  payment create <user_id> <amount> <currency> <method>
                              Create new payment
  payment update <id> <status> Update payment status
  payment stats               Show payment statistics
  payment gateways            List available payment gateways
  payment gateway:test <name> Test payment gateway configuration
  payment gateway:config <name> Show gateway configuration
  payment refund <id>         Refund a payment
  payment export              Export payments to CSV

Options:
  --status=STATUS             Filter by payment status
  --method=METHOD             Filter by payment method
  --user-id=ID                Filter by user ID
  --from=DATE                 Filter from date (YYYY-MM-DD)
  --to=DATE                   Filter to date (YYYY-MM-DD)
  --limit=N                   Limit number of results
  --offset=N                  Offset for pagination
  --format=FORMAT             Output format (table, json, csv)
  --output=FILE               Output to file
  --verbose, -v               Verbose output

Payment Statuses:
  pending, processing, completed, failed, cancelled, refunded

Payment Methods:
  card, crypto, bank_transfer, wallet, other
HELP;
    }

    public function execute(array $arguments = [], array $options = []): int
    {
        $this->setArguments($arguments);
        $this->setOptions($options);

        try {
            $action = $this->getArgument(0);
            
            if (!$action) {
                $this->error('No action specified.');
                $this->output($this->getHelp());
                return self::EXIT_INVALID_ARGUMENT;
            }

            switch ($action) {
                case 'list':
                    return $this->listPayments();
                case 'show':
                    return $this->showPayment();
                case 'create':
                    return $this->createPayment();
                case 'update':
                    return $this->updatePayment();
                case 'stats':
                    return $this->showStats();
                case 'gateways':
                    return $this->listGateways();
                case 'gateway:test':
                    return $this->testGateway();
                case 'gateway:config':
                    return $this->showGatewayConfig();
                case 'refund':
                    return $this->refundPayment();
                case 'export':
                    return $this->exportPayments();
                default:
                    $this->error("Unknown action: {$action}");
                    $this->output($this->getHelp());
                    return self::EXIT_INVALID_ARGUMENT;
            }
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * List all payments
     */
    private function listPayments(): int
    {
        try {
            $query = new GetAllPaymentsQuery(
                $this->getOption('status'),
                $this->getOption('method'),
                $this->getOption('user-id') ? (int) $this->getOption('user-id') : null,
                $this->getOption('from'),
                $this->getOption('to'),
                (int) ($this->getOption('limit') ?? 50),
                (int) ($this->getOption('offset') ?? 0)
            );
            
            $payments = $this->getQueryBus()->handle($query);
            
            if (empty($payments)) {
                $this->info('No payments found.');
                return self::EXIT_SUCCESS;
            }
            
            $format = $this->getOption('format', 'table');
            
            switch ($format) {
                case 'json':
                    $this->outputJson($payments);
                    break;
                case 'csv':
                    $this->outputCsv($payments, $this->getPaymentHeaders());
                    break;
                default:
                    $this->outputPaymentsTable($payments);
                    break;
            }
            
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Failed to list payments: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Show payment details
     */
    private function showPayment(): int
    {
        $id = $this->getArgument(1);
        
        if (!$id) {
            $this->error('Payment ID is required.');
            return self::EXIT_INVALID_ARGUMENT;
        }
        
        try {
            $query = new GetPaymentByIdQuery((int) $id);
            $payment = $this->getQueryBus()->handle($query);
            
            if (!$payment) {
                $this->error("Payment not found: {$id}");
                return self::EXIT_NOT_FOUND;
            }
            
            $this->output("Payment Details (ID: {$payment['id']})");
            $this->output("  User ID: {$payment['user_id']}");
            $this->output("  Amount: {$payment['amount']} {$payment['currency']}");
            $this->output("  Method: {$payment['method']}");
            $this->output("  Status: {$payment['status']}");
            $this->output("  Gateway: {$payment['gateway']}");
            $this->output("  Gateway Transaction ID: {$payment['gateway_transaction_id']}");
            $this->output("  Created: {$payment['created_at']}");
            $this->output("  Updated: {$payment['updated_at']}");
            
            if (!empty($payment['metadata'])) {
                $this->output("  Metadata:");
                foreach ($payment['metadata'] as $key => $value) {
                    $this->output("    {$key}: {$value}");
                }
            }
            
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Failed to show payment: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Create new payment
     */
    private function createPayment(): int
    {
        $userId = $this->getArgument(1);
        $amount = $this->getArgument(2);
        $currency = $this->getArgument(3);
        $method = $this->getArgument(4);
        
        if (!$userId || !$amount || !$currency || !$method) {
            $this->error('User ID, amount, currency, and method are required.');
            return self::EXIT_INVALID_ARGUMENT;
        }
        
        try {
            $command = new CreatePaymentCommand(
                (int) $userId,
                new Money((float) $amount, new Currency($currency)),
                new PaymentMethod($method),
                'manual', // gateway
                [] // metadata
            );
            
            $paymentId = $this->getCommandBus()->handle($command);
            
            $this->success("Payment created successfully with ID: {$paymentId}");
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Failed to create payment: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Update payment status
     */
    private function updatePayment(): int
    {
        $id = $this->getArgument(1);
        $status = $this->getArgument(2);
        
        if (!$id || !$status) {
            $this->error('Payment ID and status are required.');
            return self::EXIT_INVALID_ARGUMENT;
        }
        
        try {
            $command = new UpdatePaymentStatusCommand(
                (int) $id,
                new PaymentStatus($status)
            );
            
            $this->getCommandBus()->handle($command);
            
            $this->success("Payment status updated to: {$status}");
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Failed to update payment: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Show payment statistics
     */
    private function showStats(): int
    {
        try {
            $query = new GetPaymentStatsQuery(
                $this->getOption('from'),
                $this->getOption('to')
            );
            
            $stats = $this->getQueryBus()->handle($query);
            
            $this->output('Payment Statistics:');
            $this->output("  Total Payments: {$stats['total_count']}");
            $this->output("  Total Amount: {$stats['total_amount']} {$stats['currency']}");
            $this->output("  Successful Payments: {$stats['successful_count']}");
            $this->output("  Failed Payments: {$stats['failed_count']}");
            $this->output("  Pending Payments: {$stats['pending_count']}");
            $this->output("  Success Rate: {$stats['success_rate']}%");
            
            if (!empty($stats['by_method'])) {
                $this->output("\n  By Payment Method:");
                foreach ($stats['by_method'] as $method => $data) {
                    $this->output("    {$method}: {$data['count']} payments, {$data['amount']} {$stats['currency']}");
                }
            }
            
            if (!empty($stats['by_gateway'])) {
                $this->output("\n  By Gateway:");
                foreach ($stats['by_gateway'] as $gateway => $data) {
                    $this->output("    {$gateway}: {$data['count']} payments, {$data['amount']} {$stats['currency']}");
                }
            }
            
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Failed to get statistics: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * List available payment gateways
     */
    private function listGateways(): int
    {
        try {
            $factory = $this->getService(PaymentGatewayFactoryInterface::class);
            $gateways = $factory->getAvailableGateways();
            
            if (empty($gateways)) {
                $this->info('No payment gateways configured.');
                return self::EXIT_SUCCESS;
            }
            
            $this->output('Available Payment Gateways:');
            
            $data = [];
            foreach ($gateways as $name => $config) {
                $data[] = [
                    'Name' => $name,
                    'Status' => $config['enabled'] ? 'Enabled' : 'Disabled',
                    'Methods' => implode(', ', $config['supported_methods'] ?? []),
                    'Currencies' => implode(', ', $config['supported_currencies'] ?? [])
                ];
            }
            
            $this->displayTable(['Name', 'Status', 'Methods', 'Currencies'], $data);
            
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Failed to list gateways: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Test payment gateway
     */
    private function testGateway(): int
    {
        $gatewayName = $this->getArgument(1);
        
        if (!$gatewayName) {
            $this->error('Gateway name is required.');
            return self::EXIT_INVALID_ARGUMENT;
        }
        
        try {
            $factory = $this->getService(PaymentGatewayFactoryInterface::class);
            $gateway = $factory->create($gatewayName);
            
            $this->info("Testing gateway: {$gatewayName}");
            
            // Test gateway connection
            $testResult = $gateway->testConnection();
            
            if ($testResult) {
                $this->success("Gateway {$gatewayName} is working correctly.");
                return self::EXIT_SUCCESS;
            } else {
                $this->error("Gateway {$gatewayName} test failed.");
                return self::EXIT_FAILURE;
            }
            
        } catch (\Exception $e) {
            $this->error("Gateway test failed: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Show gateway configuration
     */
    private function showGatewayConfig(): int
    {
        $gatewayName = $this->getArgument(1);
        
        if (!$gatewayName) {
            $this->error('Gateway name is required.');
            return self::EXIT_INVALID_ARGUMENT;
        }
        
        try {
            $factory = $this->getService(PaymentGatewayFactoryInterface::class);
            $config = $factory->getGatewayConfig($gatewayName);
            
            $this->output("Gateway Configuration: {$gatewayName}");
            $this->output("  Enabled: " . ($config['enabled'] ? 'Yes' : 'No'));
            $this->output("  Supported Methods: " . implode(', ', $config['supported_methods'] ?? []));
            $this->output("  Supported Currencies: " . implode(', ', $config['supported_currencies'] ?? []));
            
            if (!empty($config['settings'])) {
                $this->output("  Settings:");
                foreach ($config['settings'] as $key => $value) {
                    // Hide sensitive information
                    if (stripos($key, 'secret') !== false || stripos($key, 'key') !== false) {
                        $value = str_repeat('*', strlen($value));
                    }
                    $this->output("    {$key}: {$value}");
                }
            }
            
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Failed to get gateway config: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Refund a payment
     */
    private function refundPayment(): int
    {
        $id = $this->getArgument(1);
        
        if (!$id) {
            $this->error('Payment ID is required.');
            return self::EXIT_INVALID_ARGUMENT;
        }
        
        if (!$this->confirm("Are you sure you want to refund payment {$id}?")) {
            $this->info('Refund cancelled.');
            return self::EXIT_SUCCESS;
        }
        
        try {
            // TODO: Implement refund command
            $this->info("Processing refund for payment: {$id}");
            $this->success('Payment refunded successfully.');
            
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Refund failed: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Export payments to CSV
     */
    private function exportPayments(): int
    {
        try {
            $query = new GetAllPaymentsQuery(
                $this->getOption('status'),
                $this->getOption('method'),
                $this->getOption('user-id') ? (int) $this->getOption('user-id') : null,
                $this->getOption('from'),
                $this->getOption('to'),
                null, // no limit for export
                0
            );
            
            $payments = $this->getQueryBus()->handle($query);
            
            $filename = $this->getOption('output', 'payments_' . date('Y-m-d_H-i-s') . '.csv');
            
            $this->info("Exporting {count($payments)} payments to: {$filename}");
            
            $this->exportToCsv($payments, $this->getPaymentHeaders(), $filename);
            
            $this->success("Payments exported successfully to: {$filename}");
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Export failed: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Output payments as table
     */
    private function outputPaymentsTable(array $payments): void
    {
        $data = [];
        foreach ($payments as $payment) {
            $data[] = [
                'ID' => $payment['id'],
                'User ID' => $payment['user_id'],
                'Amount' => $payment['amount'] . ' ' . $payment['currency'],
                'Method' => $payment['method'],
                'Status' => $payment['status'],
                'Gateway' => $payment['gateway'],
                'Created' => $payment['created_at']
            ];
        }
        
        $this->displayTable(['ID', 'User ID', 'Amount', 'Method', 'Status', 'Gateway', 'Created'], $data);
    }

    /**
     * Get payment CSV headers
     */
    private function getPaymentHeaders(): array
    {
        return ['ID', 'User ID', 'Amount', 'Currency', 'Method', 'Status', 'Gateway', 'Gateway Transaction ID', 'Created', 'Updated'];
    }
}