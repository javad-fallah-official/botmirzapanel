<?php

declare(strict_types=1);

namespace Domain\Services\Payment;

use Domain\Entities\Payment\Payment;
use Domain\ValueObjects\Payment\PaymentId;
use Domain\ValueObjects\Payment\PaymentStatus;
use Domain\ValueObjects\Common\Money;
use Domain\ValueObjects\User\UserId;
use Domain\Exceptions\PaymentValidationException;
use DateTimeImmutable;

/**
 * Payment domain service for handling payment-related business logic
 */
class PaymentService
{
    /**
     * Create a new payment with validation
     * 
     * @param UserId $userId User ID for the payment
     * @param Money $amount Payment amount
     * @param string $gateway Payment gateway identifier
     * @param string|null $description Optional payment description
     * @param array|null $metadata Optional payment metadata
     * @return Payment Created payment entity
     * @throws PaymentValidationException When validation fails
     */
    public function createPayment(
        UserId $userId,
        Money $amount,
        string $gateway,
        ?string $description = null,
        ?array $metadata = null
    ): Payment {
        $this->validatePaymentAmount($amount);
        $this->validatePaymentGateway($gateway);
        
        $paymentId = PaymentId::generate();
        $status = PaymentStatus::pending();
        
        return Payment::create(
            $paymentId,
            $userId,
            $amount,
            $status,
            $gateway,
            $description,
            $metadata
        );
    }
    
    /**
     * Process payment completion
     * 
     * @param Payment $payment Payment entity to complete
     * @param string $transactionId Gateway transaction ID
     * @param array|null $gatewayResponse Optional gateway response data
     * @return Payment Completed payment entity
     * @throws PaymentValidationException When payment cannot be completed
     */
    public function completePayment(
        Payment $payment,
        string $transactionId,
        ?array $gatewayResponse = null
    ): Payment {
        if (!$payment->canBeCompleted()) {
            throw new PaymentValidationException(
                'Payment cannot be completed in current status: ' . $payment->getStatus()->getValue()
            );
        }
        
        $payment->complete($transactionId, $gatewayResponse);
        
        return $payment;
    }
    
    /**
     * Fail a payment
     * 
     * @param Payment $payment Payment entity to fail
     */
    public function failPayment(
        Payment $payment,
        string $reason,
        ?array $gatewayResponse = null
    ): Payment {
        if (!$payment->canBeFailed()) {
            throw new PaymentValidationException(
                'Payment cannot be failed in current status: ' . $payment->getStatus()->getValue()
            );
        }
        
        $payment->fail($reason, $gatewayResponse);
        
        return $payment;
    }
    
    /**
     * Cancel a payment
     */
    public function cancelPayment(Payment $payment, string $reason): Payment
    {
        if (!$payment->canBeCancelled()) {
            throw new PaymentValidationException(
                'Payment cannot be cancelled in current status: ' . $payment->getStatus()->getValue()
            );
        }
        
        $payment->cancel($reason);
        
        return $payment;
    }
    
    /**
     * Refund a payment
     */
    public function refundPayment(
        Payment $payment,
        Money $refundAmount,
        string $reason,
        ?string $refundTransactionId = null
    ): Payment {
        if (!$payment->canBeRefunded()) {
            throw new PaymentValidationException(
                'Payment cannot be refunded in current status: ' . $payment->getStatus()->getValue()
            );
        }
        
        if ($refundAmount->isGreaterThan($payment->getAmount())) {
            throw new PaymentValidationException(
                'Refund amount cannot be greater than payment amount'
            );
        }
        
        $payment->refund($refundAmount, $reason, $refundTransactionId);
        
        return $payment;
    }
    
    /**
     * Check if payment is expired
     */
    public function isPaymentExpired(Payment $payment): bool
    {
        if (!$payment->getStatus()->isPending()) {
            return false;
        }
        
        $expirationTime = $payment->getCreatedAt()->modify('+30 minutes');
        return new DateTimeImmutable() > $expirationTime;
    }
    
    /**
     * Expire a payment
     */
    public function expirePayment(Payment $payment): Payment
    {
        if (!$payment->getStatus()->isPending()) {
            throw new PaymentValidationException(
                'Only pending payments can be expired'
            );
        }
        
        $payment->expire();
        
        return $payment;
    }
    
    /**
     * Calculate payment fee
     */
    public function calculatePaymentFee(Money $amount, string $gateway): Money
    {
        $feePercentage = $this->getGatewayFeePercentage($gateway);
        $fixedFee = $this->getGatewayFixedFee($gateway);
        
        $percentageFee = $amount->multiply($feePercentage / 100);
        
        return $percentageFee->add($fixedFee);
    }
    
    /**
     * Get payment summary for user
     */
    public function getPaymentSummary(UserId $userId, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        // This would typically use a repository to fetch data
        return [
            'total_payments' => 0,
            'successful_payments' => 0,
            'failed_payments' => 0,
            'total_amount' => Money::zero('USD'),
            'average_amount' => Money::zero('USD'),
            'most_used_gateway' => null,
        ];
    }
    
    /**
     * Validate payment amount
     */
    private function validatePaymentAmount(Money $amount): void
    {
        if ($amount->isZero() || $amount->isNegative()) {
            throw new PaymentValidationException(
                'Payment amount must be positive'
            );
        }
        
        $minAmount = Money::fromFloat(1.0, $amount->getCurrency());
        if ($amount->isLessThan($minAmount)) {
            throw new PaymentValidationException(
                'Payment amount is below minimum threshold'
            );
        }
        
        $maxAmount = Money::fromFloat(10000.0, $amount->getCurrency());
        if ($amount->isGreaterThan($maxAmount)) {
            throw new PaymentValidationException(
                'Payment amount exceeds maximum threshold'
            );
        }
    }
    
    /**
     * Validate payment gateway
     */
    private function validatePaymentGateway(string $gateway): void
    {
        $allowedGateways = [
            'zarinpal',
            'paypal',
            'stripe',
            'nowpayments',
            'aqayepardakht',
            'crypto'
        ];
        
        if (!in_array($gateway, $allowedGateways, true)) {
            throw new PaymentValidationException(
                'Invalid payment gateway: ' . $gateway
            );
        }
    }
    
    /**
     * Get gateway fee percentage
     */
    private function getGatewayFeePercentage(string $gateway): float
    {
        $fees = [
            'zarinpal' => 1.5,
            'paypal' => 2.9,
            'stripe' => 2.9,
            'nowpayments' => 0.5,
            'aqayepardakht' => 2.0,
            'crypto' => 1.0,
        ];
        
        return $fees[$gateway] ?? 2.0;
    }
    
    /**
     * Get gateway fixed fee
     */
    private function getGatewayFixedFee(string $gateway): Money
    {
        $fees = [
            'zarinpal' => 0.0,
            'paypal' => 0.30,
            'stripe' => 0.30,
            'nowpayments' => 0.0,
            'aqayepardakht' => 0.0,
            'crypto' => 0.0,
        ];
        
        return Money::fromFloat($fees[$gateway] ?? 0.0, 'USD');
    }
}