<?php

namespace BotMirzaPanel\Domain\ValueObjects\Common;

/**
 * PhoneNumber Value Object
 * 
 * Represents a valid phone number with proper validation and formatting.
 */
class PhoneNumber
{
    private string $value;
    private string $countryCode;
    private string $nationalNumber;

    public function __construct(string $phoneNumber, ?string $defaultCountryCode = null): void
    {
        $this->parseAndValidate($phoneNumber, $defaultCountryCode);
    }

    public static function fromString(string $phoneNumber, ?string $defaultCountryCode = null): self
    {
        return new self($phoneNumber, $defaultCountryCode);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function getNationalNumber(): string
    {
        return $this->nationalNumber;
    }

    public function getInternationalFormat(): string
    {
        return '+' . $this->countryCode . ' ' . $this->formatNationalNumber();
    }

    public function getNationalFormat(): string
    {
        return $this->formatNationalNumber();
    }

    public function getE164Format(): string
    {
        return '+' . $this->countryCode . $this->nationalNumber;
    }

    public function equals(PhoneNumber $other): bool
    {
        return $this->value === $other->value;
    }

    public function isMobile(): bool
    {
        // Basic mobile detection for common country codes
        $mobilePatterns = [
            '1' => '/^[2-9]\d{9}$/', // US/Canada
            '44' => '/^7\d{9}$/', // UK
            '49' => '/^1[5-7]\d{8}$/', // Germany
            '33' => '/^[67]\d{8}$/', // France
            '39' => '/^3\d{8,9}$/', // Italy
            '34' => '/^[67]\d{8}$/', // Spain
            '86' => '/^1[3-9]\d{9}$/', // China
            '81' => '/^[789]0\d{8}$/', // Japan
            '82' => '/^1[0-9]\d{7,8}$/', // South Korea
            '91' => '/^[6-9]\d{9}$/', // India
            '98' => '/^9\d{9}$/', // Iran
        ];

        if (isset($mobilePatterns[$this->countryCode])) {
            return preg_match($mobilePatterns[$this->countryCode], $this->nationalNumber) === 1;
        }

        // Default: assume mobile if starts with common mobile prefixes
        return preg_match('/^[6-9]/', $this->nationalNumber) === 1;
    }

    public function getCountryName(): ?string
    {
        $countries = [
            '1' => 'United States/Canada',
            '7' => 'Russia/Kazakhstan',
            '20' => 'Egypt',
            '27' => 'South Africa',
            '30' => 'Greece',
            '31' => 'Netherlands',
            '32' => 'Belgium',
            '33' => 'France',
            '34' => 'Spain',
            '36' => 'Hungary',
            '39' => 'Italy',
            '40' => 'Romania',
            '41' => 'Switzerland',
            '43' => 'Austria',
            '44' => 'United Kingdom',
            '45' => 'Denmark',
            '46' => 'Sweden',
            '47' => 'Norway',
            '48' => 'Poland',
            '49' => 'Germany',
            '51' => 'Peru',
            '52' => 'Mexico',
            '53' => 'Cuba',
            '54' => 'Argentina',
            '55' => 'Brazil',
            '56' => 'Chile',
            '57' => 'Colombia',
            '58' => 'Venezuela',
            '60' => 'Malaysia',
            '61' => 'Australia',
            '62' => 'Indonesia',
            '63' => 'Philippines',
            '64' => 'New Zealand',
            '65' => 'Singapore',
            '66' => 'Thailand',
            '81' => 'Japan',
            '82' => 'South Korea',
            '84' => 'Vietnam',
            '86' => 'China',
            '90' => 'Turkey',
            '91' => 'India',
            '92' => 'Pakistan',
            '93' => 'Afghanistan',
            '94' => 'Sri Lanka',
            '95' => 'Myanmar',
            '98' => 'Iran',
        ];

        return $countries[$this->countryCode] ?? null;
    }

    public function __toString(): string
    {
        return $this->getInternationalFormat();
    }

    private function parseAndValidate(string $phoneNumber, ?string $defaultCountryCode): void
    {
        if (empty($phoneNumber)) {
            throw new \InvalidArgumentException('Phone number cannot be empty.');
        }

        // Remove all non-digit characters except +
        $cleaned = preg_replace('/[^\d+]/', '', $phoneNumber);
        
        if (empty($cleaned)) {
            throw new \InvalidArgumentException('Phone number must contain digits.');
        }

        // Handle international format
        if (str_starts_with($cleaned, '+')) {
            $cleaned = substr($cleaned, 1);
            $this->parseInternationalNumber($cleaned);
        } else {
            $this->parseNationalNumber($cleaned, $defaultCountryCode);
        }

        $this->value = $this->getE164Format();
        $this->validateLength();
    }

    private function parseInternationalNumber(string $number): void
    {
        if (strlen($number) < 7 || strlen($number) > 15) {
            throw new \InvalidArgumentException('Invalid phone number length.');
        }

        // Try to extract country code
        $countryCodeLength = $this->detectCountryCodeLength($number);
        
        if ($countryCodeLength === 0) {
            throw new \InvalidArgumentException('Invalid country code.');
        }

        $this->countryCode = substr($number, 0, $countryCodeLength);
        $this->nationalNumber = substr($number, $countryCodeLength);
    }

    private function parseNationalNumber(string $number, ?string $defaultCountryCode): void
    {
        if (!$defaultCountryCode) {
            throw new \InvalidArgumentException('Country code is required for national numbers.');
        }

        $this->countryCode = $defaultCountryCode;
        $this->nationalNumber = $number;
    }

    private function detectCountryCodeLength(string $number): int
    {
        // Common country codes and their lengths
        $countryCodes = [
            // 1-digit codes
            '1' => 1, '7' => 1,
            // 2-digit codes
            '20' => 2, '27' => 2, '30' => 2, '31' => 2, '32' => 2, '33' => 2, '34' => 2,
            '36' => 2, '39' => 2, '40' => 2, '41' => 2, '43' => 2, '44' => 2, '45' => 2,
            '46' => 2, '47' => 2, '48' => 2, '49' => 2, '51' => 2, '52' => 2, '53' => 2,
            '54' => 2, '55' => 2, '56' => 2, '57' => 2, '58' => 2, '60' => 2, '61' => 2,
            '62' => 2, '63' => 2, '64' => 2, '65' => 2, '66' => 2, '81' => 2, '82' => 2,
            '84' => 2, '86' => 2, '90' => 2, '91' => 2, '92' => 2, '93' => 2, '94' => 2,
            '95' => 2, '98' => 2,
        ];

        // Try 3-digit codes first, then 2-digit, then 1-digit
        for ($length = 3; $length >= 1; $length--) {
            $code = substr($number, 0, $length);
            if (isset($countryCodes[$code])) {
                return $length;
            }
        }

        return 0;
    }

    private function formatNationalNumber(): string
    {
        $number = $this->nationalNumber;
        $length = strlen($number);

        // Basic formatting for common lengths
        switch ($length) {
            case 10: // US format: (XXX) XXX-XXXX
                return sprintf('(%s) %s-%s', 
                    substr($number, 0, 3),
                    substr($number, 3, 3),
                    substr($number, 6, 4)
                );
            case 11: // UK format: XXXX XXX XXXX
                return sprintf('%s %s %s',
                    substr($number, 0, 4),
                    substr($number, 4, 3),
                    substr($number, 7, 4)
                );
            default:
                // Default: add spaces every 3-4 digits
                return chunk_split($number, 3, ' ');
        }
    }

    private function validateLength(): void
    {
        $totalLength = strlen($this->countryCode . $this->nationalNumber);
        
        if ($totalLength < 7) {
            throw new \InvalidArgumentException('Phone number is too short.');
        }
        
        if ($totalLength > 15) {
            throw new \InvalidArgumentException('Phone number is too long.');
        }
        
        if (strlen($this->nationalNumber) < 4) {
            throw new \InvalidArgumentException('National number is too short.');
        }
    }
}