<?php

namespace BotMirzaPanel\Payment;

use BotMirzaPanel\Config\ConfigManager;
use BotMirzaPanel\Database\DatabaseManager;
use BotMirzaPanel\Infrastructure\External\Payment\PaymentGatewayInterface;

/**
 * Payment service that manages all payment operations
 * Uses strategy pattern for different payment gateways
 */
class PaymentService
{
    private ConfigManager $config;
    private DatabaseManager $db;
    /** @var array<string, PaymentGatewayInterface> */
    private array $gateways = [];
    private array $gatewaySettings = [];

    public function __construct(ConfigManager $config, DatabaseManager $db): void
    {
        $this->config = $config;
        $this->db = $db;
        $this->loadGatewaySettings();
        $this->initializeGateways();
    }

    /**
     * Get available payment gateways
     */
    public function getAvailableGateways(): array
    {
        $available = [];
        
        foreach ($this->gateways as $name => $gateway) {
            if ($this->isGatewayEnabled($name)) {
                $available[$name] = [
                    'name' => $name,
                    'display_name' => $gateway->getDisplayName(),
                    'description' => $gateway->getDescription(),
                    'supported_currencies' => $gateway->getSupportedCurrencies(),
                    'min_amount' => $gateway->getMinAmount(),
                    'max_amount' => $gateway->getMaxAmount()
                ];
            }
        }
        
        return $available;
    }

    /**
     * Create a new payment
     */
    public function createPayment(array $paymentData): array
    {
        $gateway = $this->getGateway($paymentData['gateway']);
        
        if (!$gateway) {
            throw new \Exception("Gateway not found: {$paymentData['gateway']}");
        }
        
        if (!$this->isGatewayEnabled($paymentData['gateway'])) {
            throw new \Exception("Gateway is disabled: {$paymentData['gateway']}");
        }
        
        // Validate payment data
        $this->validatePaymentData($paymentData, $gateway);
        
        // Generate unique order ID
        $orderId = $this->generateOrderId();
        
        // Create payment record
        $paymentRecord = [
            'user_id' => $paymentData['user_id'],
            'order_id' => $orderId,
            'amount' => $paymentData['amount'],
            'currency' => $paymentData['currency'] ?? 'USD',
            'gateway' => $paymentData['gateway'],
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'description' => $paymentData['description'] ?? '',
            'callback_url' => $this->generateCallbackUrl($paymentData['gateway'], $orderId)
        ];
        
        // Save to database
        $paymentId = $this->db->insert('Payment_report', $paymentRecord);
        $paymentRecord['id'] = $paymentId;
        
        // Create payment with gateway
        try {
            $gatewayResponse = $gateway->createPayment($paymentRecord);
            
            // Update payment record with gateway response
            $this->db->update('Payment_report', [
                'gateway_transaction_id' => $gatewayResponse['transaction_id'] ?? null,
                'gateway_data' => json_encode($gatewayResponse),
                'payment_url' => $gatewayResponse['payment_url'] ?? null
            ], ['id' => $paymentId]);
            
            return [
                'success' => true,
                'payment_id' => $paymentId,
                'order_id' => $orderId,
                'payment_url' => $gatewayResponse['payment_url'] ?? null,
                'gateway_response' => $gatewayResponse
            ];
            
        } catch (\Exception $e) {
            // Update payment status to failed
            $this->db->update('Payment_report', [
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ], ['id' => $paymentId]);
            
            throw $e;
        }
    }

    /**
     * Process payment callback
     */
    public function processCallback(string $gateway, array $callbackData): array
    {
        $gatewayInstance = $this->getGateway($gateway);
        
        if (!$gatewayInstance) {
            throw new \Exception("Gateway not found: {$gateway}");
        }
        
        // Verify callback with gateway
        $verificationResult = $gatewayInstance->verifyCallback($callbackData);
        
        if (!$verificationResult['valid']) {
            return [
                'success' => false,
                'message' => 'Invalid callback signature'
            ];
        }
        
        $orderId = $verificationResult['order_id'];
        $status = $verificationResult['status'];
        $transactionId = $verificationResult['transaction_id'] ?? null;
        
        // Get payment record
        $payment = $this->getPaymentByOrderId($orderId);
        
        if (!$payment) {
            return [
                'success' => false,
                'message' => 'Payment not found'
            ];
        }
        
        // Update payment status
        $updateData = [
            'status' => $status,
            'gateway_transaction_id' => $transactionId,
            'completed_at' => date('Y-m-d H:i:s'),
            'callback_data' => json_encode($callbackData)
        ];
        
        $this->db->update('Payment_report', $updateData, ['id' => $payment['id']]);
        
        // If payment is successful, process it
        if ($status === 'completed') {
            $this->processSuccessfulPayment($payment);
        }
        
        return [
            'success' => true,
            'payment_id' => $payment['id'],
            'status' => $status
        ];
    }

    /**
     * Get payment by order ID
     */
    public function getPaymentByOrderId(string $orderId): ?array
    {
        return $this->db->findOne('Payment_report', ['order_id' => $orderId]);
    }

    /**
     * Get payment by ID
     */
    public function getPaymentById(int $paymentId): ?array
    {
        return $this->db->findOne('Payment_report', ['id' => $paymentId]);
    }

    /**
     * Get user payment history
     */
    public function getUserPayments(int $userId, int $limit = 50, int $offset = 0): array
    {
        return $this->db->findAll('Payment_report', 
            ['user_id' => $userId], 
            ['created_at' => 'DESC'], 
            $limit, 
            $offset
        );
    }

    /**
     * Get payment statistics
     */
    public function getPaymentStats(array $filters = []): array
    {
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (isset($filters['date_from'])) {
            $whereClause .= " AND created_at >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (isset($filters['date_to'])) {
            $whereClause .= " AND created_at <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (isset($filters['gateway'])) {
            $whereClause .= " AND gateway = ?";
            $params[] = $filters['gateway'];
        }
        
        $sql = "
            SELECT 
                gateway,
                status,
                COUNT(*) as count,
                SUM(amount) as total_amount,
                AVG(amount) as avg_amount
            FROM Payment_report 
            {$whereClause}
            GROUP BY gateway, status
        ";
        
        return $this->db->query($sql, $params);
    }

    /**
     * Check if gateway is enabled
     */
    public function isGatewayEnabled(string $gateway): bool
    {
        $statusField = $this->getGatewayStatusField($gateway);
        return (bool)($this->gatewaySettings[$statusField] ?? false);
    }

    /**
     * Enable/disable gateway
     */
    public function toggleGateway(string $gateway, bool $enabled): bool
    {
        $statusField = $this->getGatewayStatusField($gateway);
        
        return $this->db->update('PaySetting', 
            [$statusField => $enabled ? 1 : 0], 
            ['id' => 1]
        ) > 0;
    }

    /**
     * Update gateway settings
     */
    public function updateGatewaySettings(string $gateway, array $settings): bool
    {
        $updateData = [];
        
        foreach ($settings as $key => $value) {
            $fieldName = $this->getGatewaySettingField($gateway, $key);
            if ($fieldName) {
                $updateData[$fieldName] = $value;
            }
        }
        
        if (empty($updateData)) {
            return false;
        }
        
        return $this->db->update('PaySetting', $updateData, ['id' => 1]) > 0;
    }

    /**
     * Process successful payment
     */
    private function processSuccessfulPayment(array $payment): void
    {
        // Add balance to user account
        $this->addUserBalance($payment['user_id'], $payment['amount']);
        
        // Log transaction
        $this->logTransaction($payment);
        
        // Send notification to user
        $this->sendPaymentNotification($payment);
    }

    /**
     * Add balance to user account
     */
    private function addUserBalance(int $userId, float $amount): void
    {
        // Use execute() for non-SELECT queries
        $this->db->execute(
            "UPDATE users SET balance = balance + ? WHERE id = ?",
            [$amount, $userId]
        );
    }

    /**
     * Log transaction
     */
    private function logTransaction(array $payment): void
    {
        $this->db->insert('transactions', [
            'user_id' => $payment['user_id'],
            'type' => 'payment',
            'amount' => $payment['amount'],
            'description' => "Payment via {$payment['gateway']}",
            'reference_id' => $payment['order_id'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Send payment notification
     */
    private function sendPaymentNotification(array $payment): void
    {
        // This would integrate with the Telegram bot to send notification
        // For now, just log it
        error_log("Payment notification: User {$payment['user_id']} paid {$payment['amount']}");
    }

    /**
     * Load gateway settings from database
     */
    private function loadGatewaySettings(): void
    {
        $settings = $this->db->findOne('PaySetting', ['id' => 1]);
        $this->gatewaySettings = $settings ?: [];
    }

    /**
     * Initialize payment gateways
     */
    private function initializeGateways(): void
    {
        // Build gateways map conditionally based on class availability to avoid Class not found errors
        $this->gateways = [];

        $gatewayClassMap = [
            'nowpayments' => 'BotMirzaPanel\\Payment\\Gateways\\NowPaymentsGateway',
            'aqayepardakht' => 'BotMirzaPanel\\Payment\\Gateways\\AqayePardakhtGateway',
            'carttocart' => 'BotMirzaPanel\\Payment\\Gateways\\CartToCartGateway',
            'iranpay' => 'BotMirzaPanel\\Payment\\Gateways\\IranPayGateway',
        ];

        foreach ($gatewayClassMap as $name => $class) {
            if (class_exists($class)) {
                $this->gateways[$name] = new $class($this->config, $this->gatewaySettings);
            }
        }
    }

    /**
     * Get gateway instance
     */
    private function getGateway(string $name): ?PaymentGatewayInterface
    {
        return $this->gateways[$name] ?? null;
    }

    /**
     * Validate payment data
     */
    private function validatePaymentData(array $data, PaymentGatewayInterface $gateway): void
    {
        if (!isset($data['user_id']) || !is_numeric($data['user_id'])) {
            throw new \Exception('Invalid user ID');
        }
        
        if (!isset($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
            throw new \Exception('Invalid amount');
        }
        
        if ($data['amount'] < $gateway->getMinAmount()) {
            throw new \Exception("Amount below minimum: {$gateway->getMinAmount()}");
        }
        
        if ($data['amount'] > $gateway->getMaxAmount()) {
            throw new \Exception("Amount above maximum: {$gateway->getMaxAmount()}");
        }
    }

    /**
     * Generate unique order ID
     */
    private function generateOrderId(): string
    {
        return 'ORD_' . time() . '_' . mt_rand(1000, 9999);
    }

    /**
     * Generate callback URL for gateway
     */
    private function generateCallbackUrl(string $gateway, string $orderId): string
    {
        $baseUrl = $this->config->get('app.base_url', 'https://example.com');
        return "{$baseUrl}/payment/{$gateway}/callback?order_id={$orderId}";
    }

    /**
     * Get gateway status field name
     */
    private function getGatewayStatusField(string $gateway): string
    {
        $statusFields = [
            'nowpayments' => 'nowpaymentstatus',
            'aqayepardakht' => 'statusaqayepardakht',
            'carttocart' => 'Cartstatus',
            'iranpay' => 'digistatus'
        ];
        
        return $statusFields[$gateway] ?? '';
    }

    /**
     * Get gateway setting field name
     */
    private function getGatewaySettingField(string $gateway, string $setting): ?string
    {
        $settingFields = [
            'nowpayments' => [
                'api_key' => 'apinowpayment'
            ],
            'aqayepardakht' => [
                'merchant_id' => 'merchant_id_aqayepardakht'
            ]
        ];
        
        return $settingFields[$gateway][$setting] ?? null;
    }
}