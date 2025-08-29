<?php

declare(strict_types=1);

namespace App\Domain\Services;

use App\Domain\Entities\Payment;
use App\Domain\Entities\User;
use App\Domain\ValueObjects\Money;
use App\Domain\Repositories\PaymentRepositoryInterface;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Shared\Utils\StringHelper;
use App\Shared\Constants\AppConstants;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Exceptions\ServiceException;

/**
 * Payment domain service
 * Contains business logic for payment operations
 */
class PaymentDomainService
{
    private PaymentRepositoryInterface $paymentRepository;
    private UserRepositoryInterface $userRepository;

    public function __construct(
        PaymentRepositoryInterface $paymentRepository,
        UserRepositoryInterface $userRepository
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Create a new payment with validation
     */
    public function createPayment(
        string $userId,
        Money $amount,
        string $gateway,
        ?string $description = null,
        ?array $metadata = null
    ): Payment {
        // Validate user exists and is active
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new ValidationException('User not found', ['user_id' => ['User does not exist']]);
        }

        if (!$user->isActive()) {
            throw new ValidationException('User account is not active', ['user_id' => ['Account must be active to make payments']]);
        }

        // Validate amount
        if (!$amount->isPositive()) {
            throw new ValidationException('Invalid amount', ['amount' => ['Amount must be positive']]);
        }

        if ($amount->isLessThan(new Money(100, $amount->getCurrency()))) { // Minimum $1.00
            throw new ValidationException('Amount too small', ['amount' => ['Minimum payment amount is $1.00']]);
        }

        if ($amount->isGreaterThan(new Money(1000000, $amount->getCurrency()))) { // Maximum $10,000
            throw new ValidationException('Amount too large', ['amount' => ['Maximum payment amount is $10,000']]);
        }

        // Validate gateway
        if (!$this->isValidGateway($gateway)) {
            throw new ValidationException('Invalid gateway', ['gateway' => ['Payment gateway is not supported']]);
        }

        // Generate unique order ID
        $orderId = $this->generateUniqueOrderId();

        // Create payment entity
        $payment = new Payment(
            StringHelper::uuid(),
            $userId,
            $orderId,
            $amount,
            $gateway,
            AppConstants::PAYMENT_PENDING,
            $description,
            $metadata
        );

        return $payment;
    }

    /**
     * Process payment completion
     */
    public function completePayment(
        Payment $payment,
        string $transactionId,
        ?array $gatewayResponse = null
    ): void {
        if (!$payment->canBeCompleted()) {
            throw new ServiceException(
                'PaymentDomainService',
                'completePayment',
                'Payment cannot be completed in current state: ' . $payment->getStatus()
            );
        }

        // Check for duplicate transaction ID
        if ($this->paymentRepository->findByTransactionId($transactionId)) {
            throw new ServiceException(
                'PaymentDomainService',
                'completePayment',
                'Transaction ID already exists: ' . $transactionId
            );
        }

        // Mark payment as completed
        $payment->markAsCompleted($transactionId, $gatewayResponse);

        // Add balance to user account
        $user = $this->userRepository->findById($payment->getUserId());
        if ($user) {
            $user->addBalance($payment->getAmount());
            $this->userRepository->save($user);
        }
    }

    /**
     * Process payment failure
     */
    public function failPayment(
        Payment $payment,
        string $reason,
        ?array $gatewayResponse = null
    ): void {
        if (!$payment->canBeFailed()) {
            throw new ServiceException(
                'PaymentDomainService',
                'failPayment',
                'Payment cannot be failed in current state: ' . $payment->getStatus()
            );
        }

        $payment->markAsFailed($reason, $gatewayResponse);
    }

    /**
     * Process payment refund
     */
    public function refundPayment(
        Payment $payment,
        Money $refundAmount,
        string $reason,
        ?array $gatewayResponse = null
    ): void {
        if (!$payment->canBeRefunded()) {
            throw new ServiceException(
                'PaymentDomainService',
                'refundPayment',
                'Payment cannot be refunded in current state: ' . $payment->getStatus()
            );
        }

        if ($refundAmount->isGreaterThan($payment->getAmount())) {
            throw new ValidationException(
                'Refund amount cannot exceed payment amount',
                ['refund_amount' => ['Refund amount is too large']]
            );
        }

        // Mark payment as refunded
        $payment->markAsRefunded($refundAmount, $reason, $gatewayResponse);

        // Deduct refund amount from user balance
        $user = $this->userRepository->findById($payment->getUserId());
        if ($user && $user->hasEnoughBalance($refundAmount)) {
            $user->deductBalance($refundAmount);
            $this->userRepository->save($user);
        }
    }

    /**
     * Cancel expired payments
     */
    public function cancelExpiredPayments(): int
    {
        $expiredPayments = $this->paymentRepository->findExpiredPayments();
        $cancelledCount = 0;

        foreach ($expiredPayments as $payment) {
            if ($payment->canBeCancelled()) {
                $payment->markAsCancelled('Payment expired');
                $this->paymentRepository->save($payment);
                $cancelledCount++;
            }
        }

        return $cancelledCount;
    }

    /**
     * Calculate payment statistics for a user
     */
    public function calculateUserPaymentStats(string $userId): array
    {
        $totalAmount = $this->paymentRepository->getTotalAmountByUser($userId);
        $paymentCount = $this->paymentRepository->countPaymentsByUser($userId);
        $averageAmount = $paymentCount > 0 ? $totalAmount / $paymentCount : 0;
        
        $successfulPayments = $this->paymentRepository->countPaymentsByUserAndStatus(
            $userId,
            AppConstants::PAYMENT_COMPLETED
        );
        
        $failedPayments = $this->paymentRepository->countPaymentsByUserAndStatus(
            $userId,
            AppConstants::PAYMENT_FAILED
        );
        
        $successRate = $paymentCount > 0 ? ($successfulPayments / $paymentCount) * 100 : 0;

        return [
            'total_amount' => $totalAmount,
            'payment_count' => $paymentCount,
            'average_amount' => $averageAmount,
            'successful_payments' => $successfulPayments,
            'failed_payments' => $failedPayments,
            'success_rate' => round($successRate, 2),
            'last_payment_date' => $this->paymentRepository->getLastPaymentDate($userId)
        ];
    }

    /**
     * Calculate gateway performance statistics
     */
    public function calculateGatewayStats(string $gateway, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): array
    {
        $totalPayments = $this->paymentRepository->countPaymentsByGateway($gateway, $from, $to);
        $successfulPayments = $this->paymentRepository->countPaymentsByGatewayAndStatus(
            $gateway,
            AppConstants::PAYMENT_COMPLETED,
            $from,
            $to
        );
        
        $failedPayments = $this->paymentRepository->countPaymentsByGatewayAndStatus(
            $gateway,
            AppConstants::PAYMENT_FAILED,
            $from,
            $to
        );
        
        $totalRevenue = $this->paymentRepository->getTotalRevenueByGateway($gateway, $from, $to);
        $averageAmount = $this->paymentRepository->getAverageAmountByGateway($gateway, $from, $to);
        
        $successRate = $totalPayments > 0 ? ($successfulPayments / $totalPayments) * 100 : 0;
        $failureRate = $totalPayments > 0 ? ($failedPayments / $totalPayments) * 100 : 0;

        return [
            'gateway' => $gateway,
            'total_payments' => $totalPayments,
            'successful_payments' => $successfulPayments,
            'failed_payments' => $failedPayments,
            'success_rate' => round($successRate, 2),
            'failure_rate' => round($failureRate, 2),
            'total_revenue' => $totalRevenue,
            'average_amount' => $averageAmount
        ];
    }

    /**
     * Detect suspicious payment patterns
     */
    public function detectSuspiciousActivity(string $userId): array
    {
        $suspiciousPatterns = [];
        
        // Check for rapid successive payments
        $recentPayments = $this->paymentRepository->findRecentPaymentsByUser($userId, 24); // Last 24 hours
        if (count($recentPayments) > 10) {
            $suspiciousPatterns[] = 'rapid_payments';
        }
        
        // Check for unusual amounts
        $userStats = $this->calculateUserPaymentStats($userId);
        $recentLargePayments = array_filter($recentPayments, function($payment) use ($userStats) {
            return $payment->getAmount()->getAmount() > ($userStats['average_amount'] * 5);
        });
        
        if (count($recentLargePayments) > 0) {
            $suspiciousPatterns[] = 'unusual_amounts';
        }
        
        // Check for multiple failed attempts
        $recentFailedPayments = $this->paymentRepository->findRecentFailedPaymentsByUser($userId, 24);
        if (count($recentFailedPayments) > 5) {
            $suspiciousPatterns[] = 'multiple_failures';
        }
        
        // Check for payments from multiple gateways in short time
        $uniqueGateways = array_unique(array_map(function($payment) {
            return $payment->getGateway();
        }, $recentPayments));
        
        if (count($uniqueGateways) > 3) {
            $suspiciousPatterns[] = 'multiple_gateways';
        }

        return $suspiciousPatterns;
    }

    /**
     * Validate payment gateway
     */
    private function isValidGateway(string $gateway): bool
    {
        $validGateways = [
            'stripe',
            'paypal',
            'zarinpal',
            'nextpay',
            'zibal',
            'idpay'
        ];

        return in_array($gateway, $validGateways, true);
    }

    /**
     * Generate unique order ID
     */
    private function generateUniqueOrderId(): string
    {
        $maxAttempts = 10;
        $attempts = 0;

        do {
            $orderId = $this->generateOrderId();
            $attempts++;
        } while ($this->paymentRepository->findByOrderId($orderId) && $attempts < $maxAttempts);

        if ($attempts >= $maxAttempts) {
            throw new ServiceException(
                'PaymentDomainService',
                'generateUniqueOrderId',
                'Failed to generate unique order ID after maximum attempts'
            );
        }

        return $orderId;
    }

    /**
     * Generate order ID
     */
    private function generateOrderId(): string
    {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(StringHelper::random(8, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'));
    }

    /**
     * Check if payment amount is within user's limits
     */
    public function isWithinUserLimits(User $user, Money $amount): bool
    {
        $userTier = $this->getUserTier($user);
        $dailyLimit = $this->getDailyLimitForTier($userTier);
        
        $todayPayments = $this->paymentRepository->getTotalAmountByUserAndDate(
            $user->getId()->getValue(),
            new \DateTimeImmutable('today')
        );
        
        return ($todayPayments + $amount->getAmount()) <= $dailyLimit;
    }

    /**
     * Get user tier for payment limits
     */
    private function getUserTier(User $user): string
    {
        if ($user->hasRole(AppConstants::ROLE_ADMIN)) {
            return 'admin';
        }
        
        $balance = $user->getBalance()->getAmount();
        
        if ($balance >= 1000) {
            return 'premium';
        }
        
        if ($balance >= 100) {
            return 'standard';
        }
        
        return 'basic';
    }

    /**
     * Get daily payment limit for user tier
     */
    private function getDailyLimitForTier(string $tier): int
    {
        $limits = [
            'admin' => 1000000, // $10,000
            'premium' => 500000, // $5,000
            'standard' => 100000, // $1,000
            'basic' => 50000 // $500
        ];

        return $limits[$tier] ?? $limits['basic'];
    }
}