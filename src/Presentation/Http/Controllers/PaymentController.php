<?php

declare(strict_types=1);

namespace BotMirzaPanel\Presentation\Http\Controllers;

use BotMirzaPanel\Application\Commands\Payment\CreatePaymentCommand;
use BotMirzaPanel\Application\Commands\Payment\CreatePaymentCommandHandler;
use BotMirzaPanel\Application\Commands\Payment\ProcessPaymentCommand;
use BotMirzaPanel\Application\Commands\Payment\ProcessPaymentCommandHandler;
use BotMirzaPanel\Application\Queries\Payment\GetPaymentByIdQuery;
use BotMirzaPanel\Application\Queries\Payment\GetPaymentByIdQueryHandler;
use BotMirzaPanel\Application\Queries\Payment\GetPaymentsQuery;
use BotMirzaPanel\Application\Queries\Payment\GetPaymentsQueryHandler;
use BotMirzaPanel\Domain\ValueObjects\Payment\PaymentId;
use BotMirzaPanel\Domain\ValueObjects\User\UserId;
use BotMirzaPanel\Domain\ValueObjects\Common\Money;
use BotMirzaPanel\Domain\ValueObjects\Common\Currency;
use BotMirzaPanel\Infrastructure\External\Payment\PaymentGatewayInterface;
use BotMirzaPanel\Infrastructure\Container\ServiceContainer;

/**
 * Payment Controller
 * 
 * Handles HTTP requests for payment management
 */
class PaymentController extends BaseController
{
    public function __construct(?ServiceContainer $container = null)
    {
        parent::__construct($container);
    }

    /**
     * Get all payments
     */
    public function index(): array
    {
        try {
            $this->requireAuth();
            $this->requirePermission('payments.view');
            
            $data = $this->getRequestData();
            
            $query = new GetPaymentsQuery(
                limit: (int)($data['limit'] ?? 20),
                offset: (int)($data['offset'] ?? 0),
                userId: isset($data['user_id']) ? new UserId((int)$data['user_id']) : null,
                status: $data['status'] ?? null,
                gateway: $data['gateway'] ?? null
            );
            
            $handler = $this->container->get(GetPaymentsQueryHandler::class);
            $result = $handler->handle($query);
            
            return $this->success([
                'payments' => array_map(fn($payment) => $this->formatPayment($payment), $result->getPayments()),
                'total' => $result->getTotal(),
                'limit' => $query->getLimit(),
                'offset' => $query->getOffset()
            ]);
            
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get payment by ID
     */
    public function show(string $id): array
    {
        try {
            $this->requireAuth();
            $this->requirePermission('payments.view');
            
            $query = new GetPaymentByIdQuery(new PaymentId($id));
            $handler = $this->container->get(GetPaymentByIdQueryHandler::class);
            $payment = $handler->handle($query);
            
            if (!$payment) {
                return $this->error('Payment not found', 404);
            }
            
            return $this->success(['payment' => $this->formatPayment($payment)]);
            
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Create new payment
     */
    public function store(): array
    {
        try {
            $this->requireAuth();
            $this->requirePermission('payments.create');
            
            $data = $this->getRequestData();
            
            $validatedData = $this->validate($data, [
                'user_id' => ['required' => true, 'type' => 'integer'],
                'amount' => ['required' => true, 'type' => 'integer'],
                'currency' => ['required' => true, 'type' => 'string'],
                'gateway' => ['required' => true, 'type' => 'string'],
                'description' => ['type' => 'string', 'max_length' => 255]
            ]);
            
            $command = new CreatePaymentCommand(
                userId: new UserId($validatedData['user_id']),
                amount: new Money(
                    $validatedData['amount'],
                    new Currency($validatedData['currency'])
                ),
                gateway: $validatedData['gateway'],
                description: $validatedData['description'] ?? null
            );
            
            $handler = $this->container->get(CreatePaymentCommandHandler::class);
            $result = $handler->handle($command);
            
            return $this->success([
                'payment_id' => $result->getPaymentId()->getValue(),
                'payment_url' => $result->getPaymentUrl(),
                'expires_at' => $result->getExpiresAt()?->format('c')
            ], 'Payment created successfully');
            
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Process payment callback/webhook
     */
    public function callback(string $gateway): array
    {
        try {
            $data = $this->getRequestData();
            
            $command = new ProcessPaymentCommand(
                gateway: $gateway,
                callbackData: $data
            );
            
            $handler = $this->container->get(ProcessPaymentCommandHandler::class);
            $result = $handler->handle($command);
            
            return $this->success([
                'payment_id' => $result->getPaymentId()->getValue(),
                'status' => $result->getStatus()->getValue(),
                'processed' => true
            ], 'Payment processed successfully');
            
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get payment statistics
     */
    public function statistics(): array
    {
        try {
            $this->requireAuth();
            $this->requirePermission('payments.view');
            
            // This would typically use a dedicated query/handler
            // For now, return placeholder data
            return $this->success([
                'total_payments' => 0,
                'successful_payments' => 0,
                'failed_payments' => 0,
                'pending_payments' => 0,
                'total_revenue' => [
                    'amount' => 0,
                    'currency' => 'USD'
                ],
                'revenue_today' => [
                    'amount' => 0,
                    'currency' => 'USD'
                ],
                'revenue_this_month' => [
                    'amount' => 0,
                    'currency' => 'USD'
                ]
            ]);
            
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get available payment gateways
     */
    public function gateways(): array
    {
        try {
            $this->requireAuth();
            $this->requirePermission('payments.view');
            
            $factory = $this->container->get('payment.gateway.factory');
            $gateways = $factory->getAvailableGateways();
            
            return $this->success(['gateways' => array_map(fn(PaymentGatewayInterface $g) => $g->getName(), $gateways)]);
            
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Format payment for API response
     */
    private function formatPayment(mixed $payment): array
    {
        // Placeholder formatting; adjust based on actual payment entity/DTO
        return is_array($payment) ? $payment : [
            'id' => method_exists($payment, 'getId') ? $payment->getId()->getValue() : null,
            'status' => method_exists($payment, 'getStatus') ? $payment->getStatus()->getValue() : null
        ];
    }
}