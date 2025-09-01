<?php

namespace BotMirzaPanel\Domain\ValueObjects\Panel;

/**
 * PanelType Value Object
 * 
 * Represents the type of VPN panel in the system.
 */
class PanelType
{
    private const MARZBAN = 'marzban';
    private const MIKROTIK = 'mikrotik';
    private const X_UI = 'x-ui';
    private const S_UI = 's-ui';
    private const WIREGUARD = 'wireguard';
    private const OPENVPN = 'openvpn';
    private const SHADOWSOCKS = 'shadowsocks';
    private const V2RAY = 'v2ray';
    private const TROJAN = 'trojan';
    private const HYSTERIA = 'hysteria';
    private const OUTLINE = 'outline';
    private const PRITUNL = 'pritunl';

    private const VALID_TYPES = [
        self::MARZBAN,
        self::MIKROTIK,
        self::X_UI,
        self::S_UI,
        self::WIREGUARD,
        self::OPENVPN,
        self::SHADOWSOCKS,
        self::V2RAY,
        self::TROJAN,
        self::HYSTERIA,
        self::OUTLINE,
        self::PRITUNL,
    ];

    private string $value;

    private function __construct(string $type): void
    {
        $this->validate($type);
        $this->value = $type;
    }

    public static function marzban(): self
    {
        return new self(self::MARZBAN);
    }

    public static function mikrotik(): self
    {
        return new self(self::MIKROTIK);
    }

    public static function xUi(): self
    {
        return new self(self::X_UI);
    }

    public static function sUi(): self
    {
        return new self(self::S_UI);
    }

    public static function wireguard(): self
    {
        return new self(self::WIREGUARD);
    }

    public static function openvpn(): self
    {
        return new self(self::OPENVPN);
    }

    public static function shadowsocks(): self
    {
        return new self(self::SHADOWSOCKS);
    }

    public static function v2ray(): self
    {
        return new self(self::V2RAY);
    }

    public static function trojan(): self
    {
        return new self(self::TROJAN);
    }

    public static function hysteria(): self
    {
        return new self(self::HYSTERIA);
    }

    public static function outline(): self
    {
        return new self(self::OUTLINE);
    }

    public static function pritunl(): self
    {
        return new self(self::PRITUNL);
    }

    public static function fromString(string $type): self
    {
        return new self(strtolower($type));
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(PanelType $other): bool
    {
        return $this->value === $other->value;
    }

    public function isMarzban(): bool
    {
        return $this->value === self::MARZBAN;
    }

    public function isMikrotik(): bool
    {
        return $this->value === self::MIKROTIK;
    }

    public function isXUi(): bool
    {
        return $this->value === self::X_UI;
    }

    public function isSUi(): bool
    {
        return $this->value === self::S_UI;
    }

    public function isWireguard(): bool
    {
        return $this->value === self::WIREGUARD;
    }

    public function isOpenvpn(): bool
    {
        return $this->value === self::OPENVPN;
    }

    public function isShadowsocks(): bool
    {
        return $this->value === self::SHADOWSOCKS;
    }

    public function isV2ray(): bool
    {
        return $this->value === self::V2RAY;
    }

    public function isTrojan(): bool
    {
        return $this->value === self::TROJAN;
    }

    public function isHysteria(): bool
    {
        return $this->value === self::HYSTERIA;
    }

    public function isOutline(): bool
    {
        return $this->value === self::OUTLINE;
    }

    public function isPritunl(): bool
    {
        return $this->value === self::PRITUNL;
    }

    public function supportsUserManagement(): bool
    {
        return in_array($this->value, [
            self::MARZBAN,
            self::MIKROTIK,
            self::X_UI,
            self::S_UI,
            self::OUTLINE,
            self::PRITUNL,
        ]);
    }

    public function supportsDataLimits(): bool
    {
        return in_array($this->value, [
            self::MARZBAN,
            self::MIKROTIK,
            self::X_UI,
            self::S_UI,
            self::OUTLINE,
        ]);
    }

    public function supportsExpiryDates(): bool
    {
        return in_array($this->value, [
            self::MARZBAN,
            self::MIKROTIK,
            self::X_UI,
            self::S_UI,
            self::OUTLINE,
            self::PRITUNL,
        ]);
    }

    public function supportsMultipleProtocols(): bool
    {
        return in_array($this->value, [
            self::MARZBAN,
            self::X_UI,
            self::S_UI,
            self::V2RAY,
        ]);
    }

    public function supportsInboundManagement(): bool
    {
        return in_array($this->value, [
            self::MARZBAN,
            self::X_UI,
            self::S_UI,
            self::V2RAY,
        ]);
    }

    public function requiresApiKey(): bool
    {
        return in_array($this->value, [
            self::MARZBAN,
            self::OUTLINE,
        ]);
    }

    public function requiresCredentials(): bool
    {
        return in_array($this->value, [
            self::MIKROTIK,
            self::X_UI,
            self::S_UI,
            self::PRITUNL,
        ]);
    }

    public function getDisplayName(): string
    {
        return match ($this->value) {
            self::MARZBAN => 'Marzban',
            self::MIKROTIK => 'MikroTik',
            self::X_UI => 'X-UI',
            self::S_UI => 'S-UI',
            self::WIREGUARD => 'WireGuard',
            self::OPENVPN => 'OpenVPN',
            self::SHADOWSOCKS => 'Shadowsocks',
            self::V2RAY => 'V2Ray',
            self::TROJAN => 'Trojan',
            self::HYSTERIA => 'Hysteria',
            self::OUTLINE => 'Outline',
            self::PRITUNL => 'Pritunl',
        };
    }

    public function getDescription(): string
    {
        return match ($this->value) {
            self::MARZBAN => 'Unified GUI Censorship Resistant Solution Powered by Xray',
            self::MIKROTIK => 'RouterOS-based VPN solution',
            self::X_UI => 'Multi-protocol multi-user xray panel',
            self::S_UI => 'Sing-box based multi-protocol panel',
            self::WIREGUARD => 'Modern VPN protocol',
            self::OPENVPN => 'Open source VPN solution',
            self::SHADOWSOCKS => 'Secure socks5 proxy',
            self::V2RAY => 'Platform for building proxies',
            self::TROJAN => 'Unidentifiable mechanism for bypassing GFW',
            self::HYSTERIA => 'Feature-packed proxy & relay tool',
            self::OUTLINE => 'Easy-to-use VPN server',
            self::PRITUNL => 'Enterprise VPN server',
        };
    }

    public function getDefaultPort(): int
    {
        return match ($this->value) {
            self::MARZBAN => 8000,
            self::MIKROTIK => 8728,
            self::X_UI => 54321,
            self::S_UI => 2053,
            self::WIREGUARD => 51820,
            self::OPENVPN => 1194,
            self::SHADOWSOCKS => 8388,
            self::V2RAY => 10086,
            self::TROJAN => 443,
            self::HYSTERIA => 36712,
            self::OUTLINE => 8080,
            self::PRITUNL => 443,
        };
    }

    public function getSupportedProtocols(): array
    {
        return match ($this->value) {
            self::MARZBAN => ['vmess', 'vless', 'trojan', 'shadowsocks'],
            self::MIKROTIK => ['pptp', 'l2tp', 'sstp', 'ovpn'],
            self::X_UI => ['vmess', 'vless', 'trojan', 'shadowsocks'],
            self::S_UI => ['vmess', 'vless', 'trojan', 'shadowsocks', 'hysteria'],
            self::WIREGUARD => ['wireguard'],
            self::OPENVPN => ['openvpn'],
            self::SHADOWSOCKS => ['shadowsocks'],
            self::V2RAY => ['vmess', 'vless'],
            self::TROJAN => ['trojan'],
            self::HYSTERIA => ['hysteria'],
            self::OUTLINE => ['shadowsocks'],
            self::PRITUNL => ['openvpn'],
        };
    }

    public function getIcon(): string
    {
        return match ($this->value) {
            self::MARZBAN => 'shield',
            self::MIKROTIK => 'router',
            self::X_UI => 'layers',
            self::S_UI => 'box',
            self::WIREGUARD => 'wifi',
            self::OPENVPN => 'lock',
            self::SHADOWSOCKS => 'eye-off',
            self::V2RAY => 'zap',
            self::TROJAN => 'shield-check',
            self::HYSTERIA => 'wind',
            self::OUTLINE => 'globe',
            self::PRITUNL => 'server',
        };
    }

    public static function getAllTypes(): array
    {
        return self::VALID_TYPES;
    }

    public static function getPopularTypes(): array
    {
        return [self::MARZBAN, self::X_UI, self::S_UI, self::OUTLINE];
    }

    public static function getEnterpriseTypes(): array
    {
        return [self::MIKROTIK, self::PRITUNL, self::OPENVPN];
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
            'default_port' => $this->getDefaultPort(),
            'supported_protocols' => $this->getSupportedProtocols(),
            'icon' => $this->getIcon(),
            'supports_user_management' => $this->supportsUserManagement(),
            'supports_data_limits' => $this->supportsDataLimits(),
            'supports_expiry_dates' => $this->supportsExpiryDates(),
            'supports_multiple_protocols' => $this->supportsMultipleProtocols(),
            'requires_api_key' => $this->requiresApiKey(),
            'requires_credentials' => $this->requiresCredentials(),
        ];
    }

    private function validate(string $type): void
    {
        if (!in_array($type, self::VALID_TYPES)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid panel type "%s". Valid types are: %s',
                    $type,
                    implode(', ', self::VALID_TYPES)
                )
            );
        }
    }
}