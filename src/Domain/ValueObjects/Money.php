<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

/**
 * Money value object
 * Handles monetary amounts with currency and precision
 */
class Money
{
    private int $amount; // Amount in smallest currency unit (e.g., cents)
    private string $currency;
    private static array $currencyPrecision = [
        'USD' => 2,
        'EUR' => 2,
        'GBP' => 2,
        'JPY' => 0,
        'BTC' => 8,
        'ETH' => 18,
        'IRR' => 0,
        'RUB' => 2,
    ];

    public function __construct(float|int $amount, string $currency = 'USD')
    {
        $this->currency = strtoupper($currency);
        $precision = self::$currencyPrecision[$this->currency] ?? 2;
        
        // Convert to smallest unit
        if (is_float($amount)) {
            $this->amount = (int) round($amount * pow(10, $precision));
        } else {
            $this->amount = $amount * pow(10, $precision);
        }
    }

    public function getAmount(): float
    {
        $precision = self::$currencyPrecision[$this->currency] ?? 2;
        return $this->amount / pow(10, $precision);
    }

    public function getAmountInSmallestUnit(): int
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getPrecision(): int
    {
        return self::$currencyPrecision[$this->currency] ?? 2;
    }

    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    public function isNegative(): bool
    {
        return $this->amount < 0;
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    public function isGreaterThan(Money $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount > $other->amount;
    }

    public function isGreaterThanOrEqual(Money $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount >= $other->amount;
    }

    public function isLessThan(Money $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount < $other->amount;
    }

    public function isLessThanOrEqual(Money $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount <= $other->amount;
    }

    public function add(Money $other): Money
    {
        $this->assertSameCurrency($other);
        $newAmount = $this->amount + $other->amount;
        return self::fromSmallestUnit($newAmount, $this->currency);
    }

    public function subtract(Money $other): Money
    {
        $this->assertSameCurrency($other);
        $newAmount = $this->amount - $other->amount;
        return self::fromSmallestUnit($newAmount, $this->currency);
    }

    public function multiply(float $multiplier): Money
    {
        $newAmount = (int) round($this->amount * $multiplier);
        return self::fromSmallestUnit($newAmount, $this->currency);
    }

    public function divide(float $divisor): Money
    {
        if ($divisor == 0) {
            throw new \InvalidArgumentException('Cannot divide by zero');
        }
        
        $newAmount = (int) round($this->amount / $divisor);
        return self::fromSmallestUnit($newAmount, $this->currency);
    }

    public function percentage(float $percentage): Money
    {
        return $this->multiply($percentage / 100);
    }

    public function abs(): Money
    {
        return self::fromSmallestUnit(abs($this->amount), $this->currency);
    }

    public function negate(): Money
    {
        return self::fromSmallestUnit(-$this->amount, $this->currency);
    }

    /**
     * Create Money from smallest currency unit
     */
    public static function fromSmallestUnit(int $amount, string $currency = 'USD'): self
    {
        $money = new self(0, $currency);
        $money->amount = $amount;
        return $money;
    }

    /**
     * Create Money from string representation
     */
    public static function fromString(string $amount, string $currency = 'USD'): self
    {
        $floatAmount = filter_var($amount, FILTER_VALIDATE_FLOAT);
        
        if ($floatAmount === false) {
            throw new \InvalidArgumentException('Invalid money amount format');
        }
        
        return new self($floatAmount, $currency);
    }

    /**
     * Format money for display
     */
    public function format(bool $showCurrency = true): string
    {
        $amount = number_format($this->getAmount(), $this->getPrecision());
        
        if (!$showCurrency) {
            return $amount;
        }
        
        return $amount . ' ' . $this->currency;
    }

    /**
     * Format with currency symbol
     */
    public function formatWithSymbol(): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'IRR' => '﷼',
            'RUB' => '₽',
        ];
        
        $symbol = $symbols[$this->currency] ?? $this->currency;
        $amount = number_format($this->getAmount(), $this->getPrecision());
        
        // For some currencies, symbol goes after
        if (in_array($this->currency, ['EUR', 'IRR', 'RUB'])) {
            return $amount . ' ' . $symbol;
        }
        
        return $symbol . $amount;
    }

    public function __toString(): string
    {
        return $this->format();
    }

    /**
     * Convert to array for serialization
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->getAmount(),
            'currency' => $this->currency,
            'amount_in_smallest_unit' => $this->amount,
        ];
    }

    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException(
                sprintf('Currency mismatch: %s vs %s', $this->currency, $other->currency)
            );
        }
    }

    /**
     * Add support for new currency
     */
    public static function addCurrencySupport(string $currency, int $precision): void
    {
        self::$currencyPrecision[strtoupper($currency)] = $precision;
    }

    /**
     * Get supported currencies
     */
    public static function getSupportedCurrencies(): array
    {
        return array_keys(self::$currencyPrecision);
    }
}