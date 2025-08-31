<?php

declare(strict_types=1);

namespace BotMirzaPanel\Infrastructure\External\Payment;

use BotMirzaPanel\Config\ConfigManager;
use BotMirzaPanel\Domain\ValueObjects\Money;
use BotMirzaPanel\Infrastructure\External\Payment\PaymentGatewayInterface;

/**
 * NowPayments gateway implementation
 * Handles cryptocurrency payments through NowPayments API
 */
class NowPaymentsGateway implements PaymentGatewayInterface
{
    private ConfigManager $config;
    private array $settings;
    private string $apiUrl = 'https://api.nowpayments.io/v1';
    private string $apiKey;

    public function __construct(ConfigManager $config, array $settings)
    {
        $this->config = $config;
        $this->settings = $settings;
        $this->apiKey = $settings['apinowpayment'] ?? '';
    }

    public function getName(): string
    {
        return 'nowpayments';
    }

    public function isEnabled(): bool
    {
        return !empty($this->apiKey);
    }

    public function getSupportedCurrencies(): array
    {
        return ['USD', 'EUR', 'BTC', 'ETH', 'LTC', 'TRX', 'USDT'];
    }

    public function createPayment(
        string $orderId,
        Money $amount,
        string $description,
        string $callbackUrl,
        array $metadata = []
    ): array {
        if (!$this->isEnabled()) {
            throw new \Exception('NowPayments gateway is not configured');
        }

        // Get current exchange rates
        $exchangeRates = $this->getExchangeRates();
        
        // Convert amount to crypto if needed
        $cryptoCurrency = 'TRX'; // Default crypto currency
        $cryptoAmount = $this->convertToCrypto(
            $amount->getAmount(),
            $amount->getCurrency(),
            $cryptoCurrency,
            $exchangeRates
        );

        $requestData = [
            'price_amount' => $amount->getAmount(),
            'price_currency' => $amount->getCurrency(),
            'pay_currency' => $cryptoCurrency,
            'pay_amount' => $cryptoAmount,
            'order_id' => $orderId,
            'order_description' => $description,
            'ipn_callback_url' => $callbackUrl,
            'success_url' => $this->config->get('app.base_url') . '/payment/success',
            'cancel_url' => $this->config->get('app.base_url') . '/payment/cancel'
        ];

        $response = $this->makeApiRequest('POST', '/payment', $requestData);

        if (!$response['success']) {
            throw new \Exception('Failed to create NowPayments payment: ' . $response['message']);
        }

        return [
            'transaction_id' => $response['data']['payment_id'],
            'payment_url' => $response['data']['payment_url'],
            'crypto_amount' => $cryptoAmount,
            'crypto_currency' => $cryptoCurrency,
            'payment_address' => $response['data']['pay_address'] ?? null,
            'qr_code' => $response['data']['qr_code'] ?? null,
            'expires_at' => $response['data']['expiration_estimate_date'] ?? null
        ];
    }

    public function verifyPayment(string $paymentId, array $callbackData): array
    {
        // Verify IPN signature
        if (!$this->verifyIpnSignature($callbackData)) {
            return [
                'valid' => false,
                'message' => 'Invalid IPN signature'
            ];
        }

        $status = $this->mapPaymentStatus($callbackData['payment_status'] ?? '');
        
        return [
            'valid' => true,
            'order_id' => $callbackData['order_id'] ?? '',
            'transaction_id' => $callbackData['payment_id'] ?? '',
            'status' => $status,
            'amount' => $callbackData['price_amount'] ?? 0,
            'currency' => $callbackData['price_currency'] ?? '',
            'crypto_amount' => $callbackData['pay_amount'] ?? 0,
            'crypto_currency' => $callbackData['pay_currency'] ?? '',
            'network_fee' => $callbackData['network_fee'] ?? 0,
            'tx_hash' => $callbackData['outcome_hash'] ?? null
        ];
    }

    public function getPaymentStatus(string $paymentId): array
    {
        $response = $this->makeApiRequest('GET', "/payment/{$paymentId}");

        if (!$response['success']) {
            throw new \Exception('Failed to get payment status: ' . $response['message']);
        }

        $data = $response['data'];
        $status = $this->mapPaymentStatus($data['payment_status'] ?? '');

        return [
            'status' => $status,
            'amount' => $data['price_amount'] ?? 0,
            'currency' => $data['price_currency'] ?? '',
            'crypto_amount' => $data['pay_amount'] ?? 0,
            'crypto_currency' => $data['pay_currency'] ?? '',
            'created_at' => $data['created_at'] ?? null,
            'updated_at' => $data['updated_at'] ?? null,
            'tx_hash' => $data['outcome_hash'] ?? null
        ];
    }

    public function refundPayment(string $paymentId, ?Money $amount = null, string $reason = ''): array
    {
        // NowPayments doesn't support automatic refunds
        throw new \Exception('NowPayments does not support automatic refunds');
    }

    public function getConfigurationFields(): array
    {
        return [
            'api_key' => [
                'label' => 'API Key',
                'type' => 'text',
                'required' => true,
                'description' => 'Your NowPayments API key'
            ]
        ];
    }

    public function validateConfiguration(array $config): bool
    {
        return !empty($config['api_key']);
    }

    public function getMinimumAmount(): Money
    {
        return new Money(1.0, 'USD');
    }

    public function getMaximumAmount(): Money
    {
        return new Money(10000.0, 'USD');
    }

    public function calculateFee(Money $amount): Money
    {
        // NowPayments typically charges 0.5% fee
        $feeAmount = $amount->getAmount() * 0.005;
        return new Money($feeAmount, $amount->getCurrency());
    }

    /**
     * Get current exchange rates
     */
    private function getExchangeRates(): array
    {
        $response = $this->makeApiRequest('GET', '/exchange-rates');
        
        if (!$response['success']) {
            throw new \Exception('Failed to get exchange rates');
        }
        
        return $response['data'] ?? [];
    }

    /**
     * Convert fiat amount to cryptocurrency
     */
    private function convertToCrypto(float $amount, string $fromCurrency, string $toCurrency, array $exchangeRates): float
    {
        // Find exchange rate
        $rate = null;
        foreach ($exchangeRates as $rateData) {
            if ($rateData['currency_from'] === $fromCurrency && $rateData['currency_to'] === $toCurrency) {
                $rate = $rateData['rate'];
                break;
            }
        }
        
        if ($rate === null) {
            throw new \Exception("Exchange rate not found for {$fromCurrency} to {$toCurrency}");
        }
        
        return round($amount * $rate, 8);
    }

    /**
     * Verify IPN signature
     */
    private function verifyIpnSignature(array $callbackData): bool
    {
        // NowPayments IPN verification
        $receivedSignature = $_SERVER['HTTP_X_NOWPAYMENTS_SIG'] ?? '';
        
        if (empty($receivedSignature)) {
            return false;
        }
        
        $payload = json_encode($callbackData, JSON_UNESCAPED_SLASHES);
        $expectedSignature = hash_hmac('sha512', $payload, $this->apiKey);
        
        return hash_equals($expectedSignature, $receivedSignature);
    }

    /**
     * Map NowPayments status to internal status
     */
    private function mapPaymentStatus(string $nowPaymentsStatus): string
    {
        $statusMap = [
            'waiting' => 'pending',
            'confirming' => 'pending',
            'confirmed' => 'pending',
            'sending' => 'pending',
            'partially_paid' => 'pending',
            'finished' => 'completed',
            'failed' => 'failed',
            'refunded' => 'refunded',
            'expired' => 'expired'
        ];
        
        return $statusMap[$nowPaymentsStatus] ?? 'unknown';
    }

    /**
     * Make API request to NowPayments
     */
    private function makeApiRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->apiUrl . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'x-api-key: ' . $this->apiKey
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method
        ]);
        
        if ($method === 'POST' && !empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception('cURL error: ' . $error);
        }
        
        $decodedResponse = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'data' => $decodedResponse
            ];
        } else {
            return [
                'success' => false,
                'message' => $decodedResponse['message'] ?? 'Unknown error',
                'code' => $httpCode
            ];
        }
    }
}