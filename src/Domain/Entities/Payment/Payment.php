<?php

namespace BotMirzaPanel\Domain\Entities\Payment;

use BotMirzaPanel\Domain\ValueObjects\Common\Id;
use BotMirzaPanel\Domain\ValueObjects\User\UserId;
use BotMirzaPanel\Domain\ValueObjects\Payment\Money;
use BotMirzaPanel\Domain\ValueObjects\Payment\PaymentStatus;
use BotMirzaPanel\Domain\Events\Payment\PaymentCreated;
use BotMirzaPanel\Domain\Events\Payment\PaymentCompleted;
use BotMirzaPanel\Domain\Events\Payment\PaymentFailed;
use DateTime;

/**
 * Payment Aggregate Root
 * 
 * Represents a payment transaction in the system with all business rules and invariants.
 * This is the main entry point for all payment-related operations.
 */
class Payment
{
    private Id $id;
    private UserId $userId;
    private Money $amount;
    private PaymentStatus $status;
    private string $gateway;
    private ?string $gatewayTransactionId;
    private ?string $gatewayReference;
    private ?string $description;
    private ?array $metadata;
    private ?DateTime $paidAt;
    private ?DateTime $failedAt;
    private ?string $failureReason;
    private DateTime $createdAt;
    private DateTime $updatedAt;
    private array $domainEvents = [];
    
    private ?PaymentMethod $paymentMethod;
    private array $transactions = [];

    public function __construct(
        Id $id,
        UserId $userId,
        Money $amount,
        string $gateway,
        ?string $description = null,
        ?array $metadata = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->amount = $amount;
        $this->status = PaymentStatus::pending();
        $this->gateway = $gateway;
        $this->description = $description;
        $this->metadata = $metadata ?? [];
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
        
        $this->addDomainEvent(new PaymentCreated($this->id, $this->userId, $this->amount, $this->gateway));
    }

    public static function create(
        Id $id,
        UserId $userId,
        Money $amount,
        string $gateway,
        ?string $description = null,
        ?array $metadata = null
    ): self {
        if ($amount->getAmount() <= 0) {
            throw new \InvalidArgumentException('Payment amount must be greater than zero.');
        }
        
        return new self($id, $userId, $amount, $gateway, $description, $metadata);
    }

    public function markAsPaid(
        string $gatewayTransactionId,
        ?string $gatewayReference = null,
        ?DateTime $paidAt = null
    ): void {
        if ($this->status->equals(PaymentStatus::completed())) {
            return; // Already completed
        }
        
        if (!$this->status->equals(PaymentStatus::pending()) && !$this->status->equals(PaymentStatus::processing())) {
            throw new \DomainException('Cannot mark payment as paid. Current status: ' . $this->status->getValue());
        }
        
        $this->status = PaymentStatus::completed();
        $this->gatewayTransactionId = $gatewayTransactionId;
        $this->gatewayReference = $gatewayReference;
        $this->paidAt = $paidAt ?? new DateTime();
        $this->updatedAt = new DateTime();
        
        $this->addDomainEvent(new PaymentCompleted($this->id, $this->userId, $this->amount));
    }

    public function markAsFailed(string $reason, ?DateTime $failedAt = null): void
    {
        if ($this->status->equals(PaymentStatus::failed()) || $this->status->equals(PaymentStatus::completed())) {
            return; // Already in final state
        }
        
        $this->status = PaymentStatus::failed();
        $this->failureReason = $reason;
        $this->failedAt = $failedAt ?? new DateTime();
        $this->updatedAt = new DateTime();
        
        $this->addDomainEvent(new PaymentFailed($this->id, $this->userId, $this->amount, $reason));
    }

    public function markAsProcessing(): void
    {
        if (!$this->status->equals(PaymentStatus::pending())) {
            throw new \DomainException('Cannot mark payment as processing. Current status: ' . $this->status->getValue());
        }
        
        $this->status = PaymentStatus::processing();
        $this->updatedAt = new DateTime();
    }

    public function cancel(): void
    {
        if (!$this->status->equals(PaymentStatus::pending())) {
            throw new \DomainException('Cannot cancel payment. Current status: ' . $this->status->getValue());
        }
        
        $this->status = PaymentStatus::cancelled();
        $this->updatedAt = new DateTime();
    }

    public function refund(): void
    {
        if (!$this->status->equals(PaymentStatus::completed())) {
            throw new \DomainException('Cannot refund payment. Payment must be completed first.');
        }
        
        $this->status = PaymentStatus::refunded();
        $this->updatedAt = new DateTime();
    }

    public function updateGatewayReference(string $reference): void
    {
        $this->gatewayReference = $reference;
        $this->updatedAt = new DateTime();
    }

    public function addMetadata(string $key, $value): void
    {
        $this->metadata[$key] = $value;
        $this->updatedAt = new DateTime();
    }

    public function setPaymentMethod(PaymentMethod $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
        $this->updatedAt = new DateTime();
    }

    public function addTransaction(Transaction $transaction): void
    {
        $this->transactions[] = $transaction;
        $this->updatedAt = new DateTime();
    }

    public function isPending(): bool
    {
        return $this->status->equals(PaymentStatus::pending());
    }

    public function isCompleted(): bool
    {
        return $this->status->equals(PaymentStatus::completed());
    }

    public function isFailed(): bool
    {
        return $this->status->equals(PaymentStatus::failed());
    }

    public function isCancelled(): bool
    {
        return $this->status->equals(PaymentStatus::cancelled());
    }

    public function isRefunded(): bool
    {
        return $this->status->equals(PaymentStatus::refunded());
    }

    // Getters
    public function getId(): Id
    {
        return $this->id;
    }

    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getStatus(): PaymentStatus
    {
        return $this->status;
    }

    public function getGateway(): string
    {
        return $this->gateway;
    }

    public function getGatewayTransactionId(): ?string
    {
        return $this->gatewayTransactionId;
    }

    public function getGatewayReference(): ?string
    {
        return $this->gatewayReference;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getPaidAt(): ?DateTime
    {
        return $this->paidAt;
    }

    public function getFailedAt(): ?DateTime
    {
        return $this->failedAt;
    }

    public function getFailureReason(): ?string
    {
        return $this->failureReason;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function getPaymentMethod(): ?PaymentMethod
    {
        return $this->paymentMethod;
    }

    public function getTransactions(): array
    {
        return $this->transactions;
    }

    // Domain Events
    public function getDomainEvents(): array
    {
        return $this->domainEvents;
    }

    public function clearDomainEvents(): void
    {
        $this->domainEvents = [];
    }

    private function addDomainEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }
}