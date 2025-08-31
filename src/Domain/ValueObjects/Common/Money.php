<?php

namespace BotMirzaPanel\Domain\ValueObjects\Common;

/**
 * Money Value Object
 * 
 * Represents a monetary amount with currency, ensuring precision and proper calculations.
 */
class Money
{
    private int $amount; // Amount in smallest currency unit (e.g., cents)
    private string $currency;
    private static array $currencyDecimals = [
        'USD' => 2, 'EUR' => 2, 'GBP' => 2, 'JPY' => 0, 'KRW' => 0,
        'CNY' => 2, 'INR' => 2, 'BRL' => 2, 'CAD' => 2, 'AUD' => 2,
        'CHF' => 2, 'SEK' => 2, 'NOK' => 2, 'DKK' => 2, 'PLN' => 2,
        'CZK' => 2, 'HUF' => 2, 'RUB' => 2, 'TRY' => 2, 'ZAR' => 2,
        'MXN' => 2, 'SGD' => 2, 'HKD' => 2, 'NZD' => 2, 'THB' => 2,
        'MYR' => 2, 'PHP' => 2, 'IDR' => 2, 'VND' => 0, 'IRR' => 2,
        'IQD' => 3, 'BHD' => 3, 'KWD' => 3, 'OMR' => 3, 'JOD' => 3,
        'LYD' => 3, 'TND' => 3,
    ];

    public function __construct(int $amount, string $currency)
    {
        $this->validateCurrency($currency);
        $this->amount = $amount;
        $this->currency = strtoupper($currency);
    }

    public static function fromFloat(float $amount, string $currency): self
    {
        $currency = strtoupper($currency);
        $decimals = self::$currencyDecimals[$currency] ?? 2;
        $intAmount = (int) round($amount * pow(10, $decimals));
        
        return new self($intAmount, $currency);
    }

    public static function fromString(string $amount, string $currency): self
    {
        if (!is_numeric($amount)) {
            throw new \InvalidArgumentException('Amount must be numeric.');
        }
        
        return self::fromFloat((float) $amount, $currency);
    }

    public static function zero(string $currency): self
    {
        return new self(0, $currency);
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getAmountAsFloat(): float
    {
        $decimals = self::$currencyDecimals[$this->currency] ?? 2;
        return $this->amount / pow(10, $decimals);
    }

    public function getFormattedAmount(bool $includeSymbol = true): string
    {
        $decimals = self::$currencyDecimals[$this->currency] ?? 2;
        $amount = number_format($this->getAmountAsFloat(), $decimals);
        
        if (!$includeSymbol) {
            return $amount;
        }
        
        $symbol = $this->getCurrencySymbol();
        
        // Different formatting based on currency
        switch ($this->currency) {
            case 'EUR':
                return $amount . ' ' . $symbol;
            case 'JPY':
            case 'KRW':
            case 'CNY':
                return $symbol . $amount;
            default:
                return $symbol . $amount;
        }
    }

    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->amount - $other->amount, $this->currency);
    }

    public function multiply(float $multiplier): self
    {
        return new self((int) round($this->amount * $multiplier), $this->currency);
    }

    public function divide(float $divisor): self
    {
        if ($divisor == 0) {
            throw new \InvalidArgumentException('Cannot divide by zero.');
        }
        
        return new self((int) round($this->amount / $divisor), $this->currency);
    }

    public function percentage(float $percentage): self
    {
        return $this->multiply($percentage / 100);
    }

    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    public function greaterThan(Money $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount > $other->amount;
    }

    public function greaterThanOrEqual(Money $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount >= $other->amount;
    }

    public function lessThan(Money $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount < $other->amount;
    }

    public function lessThanOrEqual(Money $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount <= $other->amount;
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    public function isNegative(): bool
    {
        return $this->amount < 0;
    }

    public function abs(): self
    {
        return new self(abs($this->amount), $this->currency);
    }

    public function negate(): self
    {
        return new self(-$this->amount, $this->currency);
    }

    public function allocate(array $ratios): array
    {
        if (empty($ratios)) {
            throw new \InvalidArgumentException('Ratios cannot be empty.');
        }
        
        $total = array_sum($ratios);
        if ($total <= 0) {
            throw new \InvalidArgumentException('Sum of ratios must be positive.');
        }
        
        $remainder = $this->amount;
        $results = [];
        
        foreach ($ratios as $ratio) {
            $amount = (int) floor($this->amount * $ratio / $total);
            $results[] = new self($amount, $this->currency);
            $remainder -= $amount;
        }
        
        // Distribute remainder
        for ($i = 0; $i < $remainder; $i++) {
            $results[$i] = new self($results[$i]->amount + 1, $this->currency);
        }
        
        return $results;
    }

    public function getCurrencySymbol(): string
    {
        $symbols = [
            'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'JPY' => '¥', 'KRW' => '₩',
            'CNY' => '¥', 'INR' => '₹', 'BRL' => 'R$', 'CAD' => 'C$', 'AUD' => 'A$',
            'CHF' => 'CHF', 'SEK' => 'kr', 'NOK' => 'kr', 'DKK' => 'kr', 'PLN' => 'zł',
            'CZK' => 'Kč', 'HUF' => 'Ft', 'RUB' => '₽', 'TRY' => '₺', 'ZAR' => 'R',
            'MXN' => '$', 'SGD' => 'S$', 'HKD' => 'HK$', 'NZD' => 'NZ$', 'THB' => '฿',
            'MYR' => 'RM', 'PHP' => '₱', 'IDR' => 'Rp', 'VND' => '₫', 'IRR' => '﷼',
            'IQD' => 'ع.د', 'BHD' => '.د.ب', 'KWD' => 'د.ك', 'OMR' => 'ر.ع.',
            'JOD' => 'د.ا', 'LYD' => 'ل.د', 'TND' => 'د.ت',
        ];
        
        return $symbols[$this->currency] ?? $this->currency;
    }

    public function getDecimalPlaces(): int
    {
        return self::$currencyDecimals[$this->currency] ?? 2;
    }

    public function __toString(): string
    {
        return $this->getFormattedAmount();
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'formatted' => $this->getFormattedAmount(),
            'float_amount' => $this->getAmountAsFloat(),
        ];
    }

    private function validateCurrency(string $currency): void
    {
        $currency = strtoupper($currency);
        
        if (strlen($currency) !== 3) {
            throw new \InvalidArgumentException('Currency code must be 3 characters long.');
        }
        
        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new \InvalidArgumentException('Currency code must contain only uppercase letters.');
        }
    }

    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException(
                sprintf('Currency mismatch: %s vs %s', $this->currency, $other->currency)
            );
        }
    }
}