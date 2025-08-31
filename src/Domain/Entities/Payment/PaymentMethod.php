<?php

namespace BotMirzaPanel\Domain\Entities\Payment;

use BotMirzaPanel\Domain\ValueObjects\Common\Id;
use DateTime;

/**
 * PaymentMethod Entity
 * 
 * Represents a payment method used for a payment.
 * This is a child entity of the Payment aggregate.
 */
class PaymentMethod
{
    private Id $paymentId;
    private string $type; // 'card', 'bank_transfer', 'crypto', 'wallet', etc.
    private string $provider; // 'visa', 'mastercard', 'bitcoin', 'paypal', etc.
    private ?string $last4; // Last 4 digits for cards
    private ?string $brand; // Card brand or crypto currency
    private ?string $country; // Issuing country
    private ?array $metadata;
    private DateTime $createdAt;

    public function __construct(
        Id $paymentId,
        string $type,
        string $provider,
        ?string $last4 = null,
        ?string $brand = null,
        ?string $country = null,
        ?array $metadata = null
    ) {
        $this->paymentId = $paymentId;
        $this->type = $type;
        $this->provider = $provider;
        $this->last4 = $last4;
        $this->brand = $brand;
        $this->country = $country;
        $this->metadata = $metadata ?? [];
        $this->createdAt = new DateTime();
    }

    public static function createCard(
        Id $paymentId,
        string $provider,
        string $last4,
        string $brand,
        ?string $country = null,
        ?array $metadata = null
    ): self {
        return new self(
            $paymentId,
            'card',
            $provider,
            $last4,
            $brand,
            $country,
            $metadata
        );
    }

    public static function createBankTransfer(
        Id $paymentId,
        string $provider,
        ?string $country = null,
        ?array $metadata = null
    ): self {
        return new self(
            $paymentId,
            'bank_transfer',
            $provider,
            null,
            null,
            $country,
            $metadata
        );
    }

    public static function createCrypto(
        Id $paymentId,
        string $currency,
        ?array $metadata = null
    ): self {
        return new self(
            $paymentId,
            'crypto',
            'crypto',
            null,
            $currency,
            null,
            $metadata
        );
    }

    public static function createWallet(
        Id $paymentId,
        string $provider,
        ?array $metadata = null
    ): self {
        return new self(
            $paymentId,
            'wallet',
            $provider,
            null,
            null,
            null,
            $metadata
        );
    }

    public function isCard(): bool
    {
        return $this->type === 'card';
    }

    public function isBankTransfer(): bool
    {
        return $this->type === 'bank_transfer';
    }

    public function isCrypto(): bool
    {
        return $this->type === 'crypto';
    }

    public function isWallet(): bool
    {
        return $this->type === 'wallet';
    }

    public function getDisplayName(): string
    {
        switch ($this->type) {
            case 'card':
                return $this->brand . ' ending in ' . $this->last4;
            case 'bank_transfer':
                return 'Bank Transfer (' . $this->provider . ')';
            case 'crypto':
                return $this->brand . ' (Cryptocurrency)';
            case 'wallet':
                return $this->provider . ' Wallet';
            default:
                return $this->provider;
        }
    }

    // Getters
    public function getPaymentId(): Id
    {
        return $this->paymentId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getLast4(): ?string
    {
        return $this->last4;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }
}