<?php

namespace BotMirzaPanel\Domain\ValueObjects\Subscription;

/**
 * SubscriptionType Value Object
 * 
 * Represents the type of subscription in the system.
 */
class SubscriptionType
{
    private const BASIC = 'basic';
    private const PREMIUM = 'premium';
    private const ENTERPRISE = 'enterprise';
    private const TRIAL = 'trial';
    private const CUSTOM = 'custom';
    private const UNLIMITED = 'unlimited';
    private const LIMITED = 'limited';
    private const FAMILY = 'family';
    private const STUDENT = 'student';
    private const BUSINESS = 'business';

    private const VALID_TYPES = [
        self::BASIC,
        self::PREMIUM,
        self::ENTERPRISE,
        self::TRIAL,
        self::CUSTOM,
        self::UNLIMITED,
        self::LIMITED,
        self::FAMILY,
        self::STUDENT,
        self::BUSINESS,
    ];

    private string $value;

    private function __construct(string $type): void
    {
        $this->validate($type);
        $this->value = $type;
    }

    public static function basic(): self
    {
        return new self(self::BASIC);
    }

    public static function premium(): self
    {
        return new self(self::PREMIUM);
    }

    public static function enterprise(): self
    {
        return new self(self::ENTERPRISE);
    }

    public static function trial(): self
    {
        return new self(self::TRIAL);
    }

    public static function custom(): self
    {
        return new self(self::CUSTOM);
    }

    public static function unlimited(): self
    {
        return new self(self::UNLIMITED);
    }

    public static function limited(): self
    {
        return new self(self::LIMITED);
    }

    public static function family(): self
    {
        return new self(self::FAMILY);
    }

    public static function student(): self
    {
        return new self(self::STUDENT);
    }

    public static function business(): self
    {
        return new self(self::BUSINESS);
    }

    public static function fromString(string $type): self
    {
        return new self(strtolower($type));
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(SubscriptionType $other): bool
    {
        return $this->value === $other->value;
    }

    public function isBasic(): bool
    {
        return $this->value === self::BASIC;
    }

    public function isPremium(): bool
    {
        return $this->value === self::PREMIUM;
    }

    public function isEnterprise(): bool
    {
        return $this->value === self::ENTERPRISE;
    }

    public function isTrial(): bool
    {
        return $this->value === self::TRIAL;
    }

    public function isCustom(): bool
    {
        return $this->value === self::CUSTOM;
    }

    public function isUnlimited(): bool
    {
        return $this->value === self::UNLIMITED;
    }

    public function isLimited(): bool
    {
        return $this->value === self::LIMITED;
    }

    public function isFamily(): bool
    {
        return $this->value === self::FAMILY;
    }

    public function isStudent(): bool
    {
        return $this->value === self::STUDENT;
    }

    public function isBusiness(): bool
    {
        return $this->value === self::BUSINESS;
    }

    public function isPaid(): bool
    {
        return !in_array($this->value, [self::TRIAL]);
    }

    public function isFree(): bool
    {
        return $this->value === self::TRIAL;
    }

    public function hasDataLimits(): bool
    {
        return in_array($this->value, [
            self::BASIC,
            self::LIMITED,
            self::TRIAL,
            self::STUDENT,
        ]);
    }

    public function hasUnlimitedData(): bool
    {
        return in_array($this->value, [
            self::PREMIUM,
            self::ENTERPRISE,
            self::UNLIMITED,
            self::FAMILY,
            self::BUSINESS,
        ]);
    }

    public function supportsMultipleDevices(): bool
    {
        return in_array($this->value, [
            self::PREMIUM,
            self::ENTERPRISE,
            self::FAMILY,
            self::BUSINESS,
            self::UNLIMITED,
        ]);
    }

    public function supportsPrioritySupport(): bool
    {
        return in_array($this->value, [
            self::PREMIUM,
            self::ENTERPRISE,
            self::BUSINESS,
        ]);
    }

    public function supportsAdvancedFeatures(): bool
    {
        return in_array($this->value, [
            self::PREMIUM,
            self::ENTERPRISE,
            self::BUSINESS,
            self::UNLIMITED,
        ]);
    }

    public function supportsCustomConfiguration(): bool
    {
        return in_array($this->value, [
            self::ENTERPRISE,
            self::CUSTOM,
            self::BUSINESS,
        ]);
    }

    public function hasDiscountEligibility(): bool
    {
        return in_array($this->value, [
            self::STUDENT,
            self::FAMILY,
        ]);
    }

    public function getMaxDevices(): ?int
    {
        return match ($this->value) {
            self::BASIC => 1,
            self::TRIAL => 1,
            self::STUDENT => 2,
            self::PREMIUM => 5,
            self::FAMILY => 10,
            self::BUSINESS => 25,
            self::ENTERPRISE => null, // unlimited
            self::UNLIMITED => null, // unlimited
            self::LIMITED => 3,
            self::CUSTOM => null, // configurable
        };
    }

    public function getDefaultDataLimit(): ?int
    {
        // Returns data limit in GB, null for unlimited
        return match ($this->value) {
            self::BASIC => 10,
            self::TRIAL => 5,
            self::STUDENT => 20,
            self::LIMITED => 15,
            self::PREMIUM => null, // unlimited
            self::FAMILY => null, // unlimited
            self::BUSINESS => null, // unlimited
            self::ENTERPRISE => null, // unlimited
            self::UNLIMITED => null, // unlimited
            self::CUSTOM => null, // configurable
        };
    }

    public function getDefaultRenewalPeriod(): int
    {
        // Returns renewal period in days
        return match ($this->value) {
            self::TRIAL => 7,
            self::BASIC => 30,
            self::PREMIUM => 30,
            self::STUDENT => 30,
            self::FAMILY => 30,
            self::LIMITED => 30,
            self::BUSINESS => 30,
            self::ENTERPRISE => 365, // annual
            self::UNLIMITED => 30,
            self::CUSTOM => 30,
        };
    }

    public function getDisplayName(): string
    {
        return match ($this->value) {
            self::BASIC => 'Basic',
            self::PREMIUM => 'Premium',
            self::ENTERPRISE => 'Enterprise',
            self::TRIAL => 'Trial',
            self::CUSTOM => 'Custom',
            self::UNLIMITED => 'Unlimited',
            self::LIMITED => 'Limited',
            self::FAMILY => 'Family',
            self::STUDENT => 'Student',
            self::BUSINESS => 'Business',
        };
    }

    public function getDescription(): string
    {
        return match ($this->value) {
            self::BASIC => 'Basic VPN access with limited features',
            self::PREMIUM => 'Premium VPN with advanced features and unlimited data',
            self::ENTERPRISE => 'Enterprise-grade VPN solution with custom features',
            self::TRIAL => 'Free trial with limited access and features',
            self::CUSTOM => 'Customized subscription with tailored features',
            self::UNLIMITED => 'Unlimited access to all VPN features',
            self::LIMITED => 'Limited access with basic features',
            self::FAMILY => 'Family plan for multiple users',
            self::STUDENT => 'Discounted plan for students',
            self::BUSINESS => 'Business plan with team management features',
        };
    }

    public function getColor(): string
    {
        return match ($this->value) {
            self::BASIC => 'blue',
            self::PREMIUM => 'gold',
            self::ENTERPRISE => 'purple',
            self::TRIAL => 'green',
            self::CUSTOM => 'orange',
            self::UNLIMITED => 'red',
            self::LIMITED => 'gray',
            self::FAMILY => 'pink',
            self::STUDENT => 'cyan',
            self::BUSINESS => 'indigo',
        };
    }

    public function getIcon(): string
    {
        return match ($this->value) {
            self::BASIC => 'user',
            self::PREMIUM => 'star',
            self::ENTERPRISE => 'building',
            self::TRIAL => 'gift',
            self::CUSTOM => 'settings',
            self::UNLIMITED => 'infinity',
            self::LIMITED => 'lock',
            self::FAMILY => 'users',
            self::STUDENT => 'graduation-cap',
            self::BUSINESS => 'briefcase',
        };
    }

    public function getPriority(): int
    {
        return match ($this->value) {
            self::ENTERPRISE => 1,
            self::BUSINESS => 2,
            self::UNLIMITED => 3,
            self::PREMIUM => 4,
            self::FAMILY => 5,
            self::CUSTOM => 6,
            self::BASIC => 7,
            self::STUDENT => 8,
            self::LIMITED => 9,
            self::TRIAL => 10,
        };
    }

    public function getFeatures(): array
    {
        $features = [];
        
        if ($this->hasUnlimitedData()) {
            $features[] = 'Unlimited Data';
        } else {
            $limit = $this->getDefaultDataLimit();
            $features[] = $limit ? "{$limit}GB Data" : 'Limited Data';
        }
        
        $maxDevices = $this->getMaxDevices();
        if ($maxDevices) {
            $features[] = "{$maxDevices} Device" . ($maxDevices > 1 ? 's' : '');
        } else {
            $features[] = 'Unlimited Devices';
        }
        
        if ($this->supportsPrioritySupport()) {
            $features[] = 'Priority Support';
        }
        
        if ($this->supportsAdvancedFeatures()) {
            $features[] = 'Advanced Features';
        }
        
        if ($this->supportsCustomConfiguration()) {
            $features[] = 'Custom Configuration';
        }
        
        if ($this->hasDiscountEligibility()) {
            $features[] = 'Discounted Price';
        }
        
        return $features;
    }

    public static function getAllTypes(): array
    {
        return self::VALID_TYPES;
    }

    public static function getPaidTypes(): array
    {
        return array_filter(self::VALID_TYPES, function ($type) {
            return (new self($type))->isPaid();
        });
    }

    public static function getFreeTypes(): array
    {
        return array_filter(self::VALID_TYPES, function ($type) {
            return (new self($type))->isFree();
        });
    }

    public static function getPopularTypes(): array
    {
        return [self::BASIC, self::PREMIUM, self::FAMILY, self::BUSINESS];
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'display_name' => $this->getDisplayName(),
            'description' => $this->getDescription(),
            'color' => $this->getColor(),
            'icon' => $this->getIcon(),
            'priority' => $this->getPriority(),
            'features' => $this->getFeatures(),
            'max_devices' => $this->getMaxDevices(),
            'default_data_limit' => $this->getDefaultDataLimit(),
            'default_renewal_period' => $this->getDefaultRenewalPeriod(),
            'is_paid' => $this->isPaid(),
            'is_free' => $this->isFree(),
            'has_data_limits' => $this->hasDataLimits(),
            'has_unlimited_data' => $this->hasUnlimitedData(),
            'supports_multiple_devices' => $this->supportsMultipleDevices(),
            'supports_priority_support' => $this->supportsPrioritySupport(),
            'supports_advanced_features' => $this->supportsAdvancedFeatures(),
            'supports_custom_configuration' => $this->supportsCustomConfiguration(),
            'has_discount_eligibility' => $this->hasDiscountEligibility(),
        ];
    }

    private function validate(string $type): void
    {
        if (!in_array($type, self::VALID_TYPES)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid subscription type "%s". Valid types are: %s',
                    $type,
                    implode(', ', self::VALID_TYPES)
                )
            );
        }
    }
}