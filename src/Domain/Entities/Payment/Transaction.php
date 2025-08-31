<?php

namespace BotMirzaPanel\Domain\Entities\Payment;

use BotMirzaPanel\Domain\ValueObjects\Common\Id;
use BotMirzaPanel\Domain\ValueObjects\Payment\Money;
use DateTime;

/**
 * Transaction Entity
 * 
 * Represents an individual transaction within a payment.
 * This is a child entity of the Payment aggregate.
 */
class Transaction
{
    private Id $id;
    private Id $paymentId;
    private string $type; // 'charge', 'refund', 'partial_refund', 'chargeback'
    private Money $amount;
    private string $status; // 'pending', 'completed', 'failed'
    private ?string $gatewayTransactionId;
    private ?string $gatewayResponse;
    private ?string $description;
    private ?array $metadata;
    private ?DateTime $processedAt;
    private DateTime $createdAt;

    public function __construct(
        Id $id,
        Id $paymentId,
        string $type,
        Money $amount,
        ?string $description = null,
        ?array $metadata = null
    ) {
        $this->id = $id;
        $this->paymentId = $paymentId;
        $this->type = $type;
        $this->amount = $amount;
        $this->status = 'pending';
        $this->description = $description;
        $this->metadata = $metadata ?? [];
        $this->createdAt = new DateTime();
    }

    public static function createCharge(
        Id $id,
        Id $paymentId,
        Money $amount,
        ?string $description = null,
        ?array $metadata = null
    ): self {
        return new self($id, $paymentId, 'charge', $amount, $description, $metadata);
    }

    public static function createRefund(
        Id $id,
        Id $paymentId,
        Money $amount,
        ?string $description = null,
        ?array $metadata = null
    ): self {
        return new self($id, $paymentId, 'refund', $amount, $description, $metadata);
    }

    public static function createPartialRefund(
        Id $id,
        Id $paymentId,
        Money $amount,
        ?string $description = null,
        ?array $metadata = null
    ): self {
        return new self($id, $paymentId, 'partial_refund', $amount, $description, $metadata);
    }

    public static function createChargeback(
        Id $id,
        Id $paymentId,
        Money $amount,
        ?string $description = null,
        ?array $metadata = null
    ): self {
        return new self($id, $paymentId, 'chargeback', $amount, $description, $metadata);
    }

    public function markAsCompleted(
        string $gatewayTransactionId,
        ?string $gatewayResponse = null,
        ?DateTime $processedAt = null
    ): void {
        $this->status = 'completed';
        $this->gatewayTransactionId = $gatewayTransactionId;
        $this->gatewayResponse = $gatewayResponse;
        $this->processedAt = $processedAt ?? new DateTime();
    }

    public function markAsFailed(?string $gatewayResponse = null): void
    {
        $this->status = 'failed';
        $this->gatewayResponse = $gatewayResponse;
        $this->processedAt = new DateTime();
    }

    public function isCharge(): bool
    {
        return $this->type === 'charge';
    }

    public function isRefund(): bool
    {
        return in_array($this->type, ['refund', 'partial_refund']);
    }

    public function isChargeback(): bool
    {
        return $this->type === 'chargeback';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    // Getters
    public function getId(): Id
    {
        return $this->id;
    }

    public function getPaymentId(): Id
    {
        return $this->paymentId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getGatewayTransactionId(): ?string
    {
        return $this->gatewayTransactionId;
    }

    public function getGatewayResponse(): ?string
    {
        return $this->gatewayResponse;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getProcessedAt(): ?DateTime
    {
        return $this->processedAt;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }
}