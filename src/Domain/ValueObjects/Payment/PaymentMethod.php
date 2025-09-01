<?php

declare(strict_types=1);

namespace BotMirzaPanel\Domain\ValueObjects\Payment;

use BotMirzaPanel\Domain\ValueObjects\ValueObject;
use BotMirzaPanel\Shared\Exceptions\DomainException;

/**
 * Payment Method value object
 */
class PaymentMethod extends ValueObject
{
    private string $value;

    // Supported payment methods
    public const CREDIT_CARD = 'credit_card';
    public const BANK_TRANSFER = 'bank_transfer';
    public const CRYPTO = 'crypto';
    public const NOWPAYMENTS = 'nowpayments';
    public const AQAYEPARDAKHT = 'aqayepardakht';
    public const WALLET = 'wallet';
    public const CASH = 'cash';

    private const SUPPORTED_METHODS = [
        self::CREDIT_CARD => 'Credit Card',
        self::BANK_TRANSFER => 'Bank Transfer',
        self::CRYPTO => 'Cryptocurrency',
        self::NOWPAYMENTS => 'NOWPayments',
        self::AQAYEPARDAKHT => 'Aqaye Pardakht',
        self::WALLET => 'Wallet',
        self::CASH => 'Cash',
    ];

    public function __construct(string $value): void
    {
        $this->validate($value);
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getDisplayName(): string
    {
        return self::SUPPORTED_METHODS[$this->value];
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self && $this->value === $other->value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'display_name' => $this->getDisplayName()
        ];
    }

    private function validate(string $value): void
    {
        if (!isset(self::SUPPORTED_METHODS[$value])) {
            throw DomainException::businessRuleViolation(
                'payment_method_not_supported',
                sprintf('Payment method "%s" is not supported', $value),
                'Payment',
                [
                    'provided_method' => $value,
                    'supported_methods' => array_keys(self::SUPPORTED_METHODS)
                ]
            );
        }
    }

    /**
     * Create credit card payment method
     */
    public static function creditCard(): self
    {
        return new self(self::CREDIT_CARD);
    }

    /**
     * Create bank transfer payment method
     */
    public static function bankTransfer(): self
    {
        return new self(self::BANK_TRANSFER);
    }

    /**
     * Create crypto payment method
     */
    public static function crypto(): self
    {
        return new self(self::CRYPTO);
    }

    /**
     * Create NOWPayments method
     */
    public static function nowPayments(): self
    {
        return new self(self::NOWPAYMENTS);
    }

    /**
     * Create Aqaye Pardakht method
     */
    public static function aqayePardakht(): self
    {
        return new self(self::AQAYEPARDAKHT);
    }

    /**
     * Create wallet payment method
     */
    public static function wallet(): self
    {
        return new self(self::WALLET);
    }

    /**
     * Get all supported payment methods
     */
    public static function getSupportedMethods(): array
    {
        return self::SUPPORTED_METHODS;
    }

    /**
     * Check if payment method is supported
     */
    public static function isSupported(string $method): bool
    {
        return isset(self::SUPPORTED_METHODS[$method]);
    }

    /**
     * Check if this is a crypto payment method
     */
    public function isCrypto(): bool
    {
        return in_array($this->value, [self::CRYPTO, self::NOWPAYMENTS]);
    }

    /**
     * Check if this requires external gateway
     */
    public function requiresExternalGateway(): bool
    {
        return in_array($this->value, [self::NOWPAYMENTS, self::AQAYEPARDAKHT]);
    }
}