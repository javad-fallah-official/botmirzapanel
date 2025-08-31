<?php

namespace BotMirzaPanel\Domain\ValueObjects\Common;

/**
 * DataLimit Value Object
 * 
 * Represents data limits and usage in bytes with conversion utilities.
 */
class DataLimit
{
    private const BYTES_PER_KB = 1024;
    private const BYTES_PER_MB = 1024 * 1024;
    private const BYTES_PER_GB = 1024 * 1024 * 1024;
    private const BYTES_PER_TB = 1024 * 1024 * 1024 * 1024;

    private int $bytes;

    private function __construct(int $bytes)
    {
        $this->validate($bytes);
        $this->bytes = $bytes;
    }

    public static function fromBytes(int $bytes): self
    {
        return new self($bytes);
    }

    public static function fromKilobytes(float $kilobytes): self
    {
        return new self((int) round($kilobytes * self::BYTES_PER_KB));
    }

    public static function fromMegabytes(float $megabytes): self
    {
        return new self((int) round($megabytes * self::BYTES_PER_MB));
    }

    public static function fromGigabytes(float $gigabytes): self
    {
        return new self((int) round($gigabytes * self::BYTES_PER_GB));
    }

    public static function fromTerabytes(float $terabytes): self
    {
        return new self((int) round($terabytes * self::BYTES_PER_TB));
    }

    public static function unlimited(): self
    {
        return new self(0); // 0 represents unlimited
    }

    public static function fromString(string $value): self
    {
        $value = trim(strtolower($value));
        
        if ($value === 'unlimited' || $value === '0') {
            return self::unlimited();
        }
        
        // Parse value with unit (e.g., "10GB", "500MB", "1.5TB")
        if (preg_match('/^([0-9]*\.?[0-9]+)\s*(b|kb|mb|gb|tb)?$/', $value, $matches)) {
            $amount = (float) $matches[1];
            $unit = $matches[2] ?? 'b';
            
            return match ($unit) {
                'b' => self::fromBytes((int) $amount),
                'kb' => self::fromKilobytes($amount),
                'mb' => self::fromMegabytes($amount),
                'gb' => self::fromGigabytes($amount),
                'tb' => self::fromTerabytes($amount),
                default => throw new \InvalidArgumentException("Invalid unit: {$unit}")
            };
        }
        
        throw new \InvalidArgumentException("Invalid data limit format: {$value}");
    }

    public function getBytes(): int
    {
        return $this->bytes;
    }

    public function getKilobytes(): float
    {
        return $this->isUnlimited() ? 0 : $this->bytes / self::BYTES_PER_KB;
    }

    public function getMegabytes(): float
    {
        return $this->isUnlimited() ? 0 : $this->bytes / self::BYTES_PER_MB;
    }

    public function getGigabytes(): float
    {
        return $this->isUnlimited() ? 0 : $this->bytes / self::BYTES_PER_GB;
    }

    public function getTerabytes(): float
    {
        return $this->isUnlimited() ? 0 : $this->bytes / self::BYTES_PER_TB;
    }

    public function isUnlimited(): bool
    {
        return $this->bytes === 0;
    }

    public function isLimited(): bool
    {
        return !$this->isUnlimited();
    }

    public function equals(DataLimit $other): bool
    {
        return $this->bytes === $other->bytes;
    }

    public function isGreaterThan(DataLimit $other): bool
    {
        if ($this->isUnlimited()) {
            return !$other->isUnlimited();
        }
        
        if ($other->isUnlimited()) {
            return false;
        }
        
        return $this->bytes > $other->bytes;
    }

    public function isLessThan(DataLimit $other): bool
    {
        if ($other->isUnlimited()) {
            return $this->isLimited();
        }
        
        if ($this->isUnlimited()) {
            return false;
        }
        
        return $this->bytes < $other->bytes;
    }

    public function isGreaterThanOrEqual(DataLimit $other): bool
    {
        return $this->equals($other) || $this->isGreaterThan($other);
    }

    public function isLessThanOrEqual(DataLimit $other): bool
    {
        return $this->equals($other) || $this->isLessThan($other);
    }

    public function add(DataLimit $other): self
    {
        if ($this->isUnlimited() || $other->isUnlimited()) {
            return self::unlimited();
        }
        
        return new self($this->bytes + $other->bytes);
    }

    public function subtract(DataLimit $other): self
    {
        if ($this->isUnlimited()) {
            return self::unlimited();
        }
        
        if ($other->isUnlimited()) {
            throw new \InvalidArgumentException('Cannot subtract unlimited from limited data');
        }
        
        $result = $this->bytes - $other->bytes;
        return new self(max(0, $result));
    }

    public function multiply(float $factor): self
    {
        if ($this->isUnlimited()) {
            return self::unlimited();
        }
        
        return new self((int) round($this->bytes * $factor));
    }

    public function divide(float $divisor): self
    {
        if ($divisor <= 0) {
            throw new \InvalidArgumentException('Divisor must be greater than zero');
        }
        
        if ($this->isUnlimited()) {
            return self::unlimited();
        }
        
        return new self((int) round($this->bytes / $divisor));
    }

    public function percentage(DataLimit $total): float
    {
        if ($total->isUnlimited()) {
            return 0.0;
        }
        
        if ($this->isUnlimited()) {
            return 100.0;
        }
        
        if ($total->bytes === 0) {
            return 0.0;
        }
        
        return ($this->bytes / $total->bytes) * 100;
    }

    public function remainingFrom(DataLimit $total): self
    {
        if ($total->isUnlimited()) {
            return self::unlimited();
        }
        
        return $total->subtract($this);
    }

    public function isExceeded(DataLimit $used): bool
    {
        if ($this->isUnlimited()) {
            return false;
        }
        
        if ($used->isUnlimited()) {
            return true;
        }
        
        return $used->bytes > $this->bytes;
    }

    public function format(int $precision = 2): string
    {
        if ($this->isUnlimited()) {
            return 'Unlimited';
        }
        
        if ($this->bytes >= self::BYTES_PER_TB) {
            return round($this->getTerabytes(), $precision) . ' TB';
        }
        
        if ($this->bytes >= self::BYTES_PER_GB) {
            return round($this->getGigabytes(), $precision) . ' GB';
        }
        
        if ($this->bytes >= self::BYTES_PER_MB) {
            return round($this->getMegabytes(), $precision) . ' MB';
        }
        
        if ($this->bytes >= self::BYTES_PER_KB) {
            return round($this->getKilobytes(), $precision) . ' KB';
        }
        
        return $this->bytes . ' B';
    }

    public function formatShort(): string
    {
        return $this->format(1);
    }

    public function formatBytes(): string
    {
        if ($this->isUnlimited()) {
            return 'Unlimited';
        }
        
        return number_format($this->bytes) . ' bytes';
    }

    public function getBestUnit(): string
    {
        if ($this->isUnlimited()) {
            return 'unlimited';
        }
        
        if ($this->bytes >= self::BYTES_PER_TB) {
            return 'TB';
        }
        
        if ($this->bytes >= self::BYTES_PER_GB) {
            return 'GB';
        }
        
        if ($this->bytes >= self::BYTES_PER_MB) {
            return 'MB';
        }
        
        if ($this->bytes >= self::BYTES_PER_KB) {
            return 'KB';
        }
        
        return 'B';
    }

    public function getValueInBestUnit(int $precision = 2): float
    {
        if ($this->isUnlimited()) {
            return 0;
        }
        
        return match ($this->getBestUnit()) {
            'TB' => round($this->getTerabytes(), $precision),
            'GB' => round($this->getGigabytes(), $precision),
            'MB' => round($this->getMegabytes(), $precision),
            'KB' => round($this->getKilobytes(), $precision),
            'B' => (float) $this->bytes,
            default => 0.0,
        };
    }

    public function __toString(): string
    {
        return $this->format();
    }

    public function toArray(): array
    {
        return [
            'bytes' => $this->bytes,
            'kilobytes' => $this->getKilobytes(),
            'megabytes' => $this->getMegabytes(),
            'gigabytes' => $this->getGigabytes(),
            'terabytes' => $this->getTerabytes(),
            'is_unlimited' => $this->isUnlimited(),
            'formatted' => $this->format(),
            'formatted_short' => $this->formatShort(),
            'best_unit' => $this->getBestUnit(),
            'value_in_best_unit' => $this->getValueInBestUnit(),
        ];
    }

    private function validate(int $bytes): void
    {
        if ($bytes < 0) {
            throw new \InvalidArgumentException('Data limit cannot be negative.');
        }
    }
}