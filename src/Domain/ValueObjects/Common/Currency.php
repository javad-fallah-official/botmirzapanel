<?php

declare(strict_types=1);

namespace BotMirzaPanel\Domain\ValueObjects\Common;

use BotMirzaPanel\Domain\ValueObjects\ValueObject;
use BotMirzaPanel\Shared\Exceptions\DomainException;

/**
 * Currency value object
 */
class Currency extends ValueObject
{
    private string $code;
    private string $symbol;
    private string $name;

    // Common currencies
    public const USD = 'USD';
    public const EUR = 'EUR';
    public const IRR = 'IRR';
    public const BTC = 'BTC';
    public const ETH = 'ETH';
    public const USDT = 'USDT';

    private const SUPPORTED_CURRENCIES = [
        self::USD => ['symbol' => '$', 'name' => 'US Dollar'],
        self::EUR => ['symbol' => '€', 'name' => 'Euro'],
        self::IRR => ['symbol' => '﷼', 'name' => 'Iranian Rial'],
        self::BTC => ['symbol' => '₿', 'name' => 'Bitcoin'],
        self::ETH => ['symbol' => 'Ξ', 'name' => 'Ethereum'],
        self::USDT => ['symbol' => '₮', 'name' => 'Tether'],
    ];

    public function __construct(string $code)
    {
        $this->validate($code);
        $this->code = strtoupper($code);
        $this->symbol = self::SUPPORTED_CURRENCIES[$this->code]['symbol'];
        $this->name = self::SUPPORTED_CURRENCIES[$this->code]['name'];
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self && $this->code === $other->code;
    }

    public function toString(): string
    {
        return $this->code;
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'symbol' => $this->symbol,
            'name' => $this->name
        ];
    }

    private function validate(string $code): void
    {
        $upperCode = strtoupper($code);
        
        if (!isset(self::SUPPORTED_CURRENCIES[$upperCode])) {
            throw DomainException::businessRuleViolation(
                'currency_not_supported',
                sprintf('Currency code "%s" is not supported', $code),
                'Currency',
                [
                    'provided_code' => $code,
                    'supported_currencies' => array_keys(self::SUPPORTED_CURRENCIES)
                ]
            );
        }
    }

    /**
     * Create USD currency
     */
    public static function usd(): self
    {
        return new self(self::USD);
    }

    /**
     * Create EUR currency
     */
    public static function eur(): self
    {
        return new self(self::EUR);
    }

    /**
     * Create IRR currency
     */
    public static function irr(): self
    {
        return new self(self::IRR);
    }

    /**
     * Create BTC currency
     */
    public static function btc(): self
    {
        return new self(self::BTC);
    }

    /**
     * Get all supported currencies
     */
    public static function getSupportedCurrencies(): array
    {
        return self::SUPPORTED_CURRENCIES;
    }

    /**
     * Check if currency is supported
     */
    public static function isSupported(string $code): bool
    {
        return isset(self::SUPPORTED_CURRENCIES[strtoupper($code)]);
    }
}