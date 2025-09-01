<?php

namespace BotMirzaPanel\Domain\ValueObjects\Common;

/**
 * Url Value Object
 * 
 * Represents a URL with validation and parsing capabilities.
 */
class Url
{
    private string $value;
    private array $components;

    public function __construct(string $url): void
    {
        $this->validate($url);
        $this->value = $url;
        $this->components = parse_url($url);
    }

    public static function fromString(string $url): self
    {
        return new self($url);
    }

    public static function create(string $scheme, string $host, ?int $port = null, ?string $path = null, ?string $query = null, ?string $fragment = null): self
    {
        $url = $scheme . '://' . $host;
        
        if ($port !== null) {
            $url .= ':' . $port;
        }
        
        if ($path !== null) {
            $url .= '/' . ltrim($path, '/');
        }
        
        if ($query !== null) {
            $url .= '?' . $query;
        }
        
        if ($fragment !== null) {
            $url .= '#' . $fragment;
        }
        
        return new self($url);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getScheme(): ?string
    {
        return $this->components['scheme'] ?? null;
    }

    public function getHost(): ?string
    {
        return $this->components['host'] ?? null;
    }

    public function getPort(): ?int
    {
        return $this->components['port'] ?? null;
    }

    public function getPath(): ?string
    {
        return $this->components['path'] ?? null;
    }

    public function getQuery(): ?string
    {
        return $this->components['query'] ?? null;
    }

    public function getFragment(): ?string
    {
        return $this->components['fragment'] ?? null;
    }

    public function getUser(): ?string
    {
        return $this->components['user'] ?? null;
    }

    public function getPass(): ?string
    {
        return $this->components['pass'] ?? null;
    }

    public function getDomain(): ?string
    {
        $host = $this->getHost();
        if (!$host) {
            return null;
        }
        
        // Remove www. prefix if present
        return preg_replace('/^www\./', '', $host);
    }

    public function getSubdomain(): ?string
    {
        $host = $this->getHost();
        if (!$host) {
            return null;
        }
        
        $parts = explode('.', $host);
        if (count($parts) > 2) {
            return $parts[0];
        }
        
        return null;
    }

    public function getTld(): ?string
    {
        $host = $this->getHost();
        if (!$host) {
            return null;
        }
        
        $parts = explode('.', $host);
        return end($parts);
    }

    public function getQueryParameters(): array
    {
        $query = $this->getQuery();
        if (!$query) {
            return [];
        }
        
        parse_str($query, $params);
        return $params;
    }

    public function getQueryParameter(string $name): ?string
    {
        $params = $this->getQueryParameters();
        return $params[$name] ?? null;
    }

    public function hasQueryParameter(string $name): bool
    {
        return array_key_exists($name, $this->getQueryParameters());
    }

    public function withScheme(string $scheme): self
    {
        $components = $this->components;
        $components['scheme'] = $scheme;
        return $this->buildFromComponents($components);
    }

    public function withHost(string $host): self
    {
        $components = $this->components;
        $components['host'] = $host;
        return $this->buildFromComponents($components);
    }

    public function withPort(?int $port): self
    {
        $components = $this->components;
        if ($port === null) {
            unset($components['port']);
        } else {
            $components['port'] = $port;
        }
        return $this->buildFromComponents($components);
    }

    public function withPath(string $path): self
    {
        $components = $this->components;
        $components['path'] = '/' . ltrim($path, '/');
        return $this->buildFromComponents($components);
    }

    public function withQuery(?string $query): self
    {
        $components = $this->components;
        if ($query === null) {
            unset($components['query']);
        } else {
            $components['query'] = $query;
        }
        return $this->buildFromComponents($components);
    }

    public function withQueryParameter(string $name, string $value): self
    {
        $params = $this->getQueryParameters();
        $params[$name] = $value;
        return $this->withQuery(http_build_query($params));
    }

    public function withoutQueryParameter(string $name): self
    {
        $params = $this->getQueryParameters();
        unset($params[$name]);
        return $this->withQuery(empty($params) ? null : http_build_query($params));
    }

    public function withFragment(?string $fragment): self
    {
        $components = $this->components;
        if ($fragment === null) {
            unset($components['fragment']);
        } else {
            $components['fragment'] = $fragment;
        }
        return $this->buildFromComponents($components);
    }

    public function equals(Url $other): bool
    {
        return $this->value === $other->value;
    }

    public function isSecure(): bool
    {
        return $this->getScheme() === 'https';
    }

    public function isHttp(): bool
    {
        return in_array($this->getScheme(), ['http', 'https']);
    }

    public function isFtp(): bool
    {
        return in_array($this->getScheme(), ['ftp', 'ftps']);
    }

    public function isLocalhost(): bool
    {
        $host = $this->getHost();
        return in_array($host, ['localhost', '127.0.0.1', '::1']);
    }

    public function isPrivateNetwork(): bool
    {
        $host = $this->getHost();
        if (!$host) {
            return false;
        }
        
        // Check for private IP ranges
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }
        
        // Check for localhost
        if ($this->isLocalhost()) {
            return true;
        }
        
        return false;
    }

    public function isAbsolute(): bool
    {
        return $this->getScheme() !== null && $this->getHost() !== null;
    }

    public function isRelative(): bool
    {
        return !$this->isAbsolute();
    }

    public function hasDefaultPort(): bool
    {
        $port = $this->getPort();
        $scheme = $this->getScheme();
        
        if ($port === null) {
            return true; // No explicit port means default port
        }
        
        $defaultPorts = [
            'http' => 80,
            'https' => 443,
            'ftp' => 21,
            'ftps' => 990,
            'ssh' => 22,
            'telnet' => 23,
        ];
        
        return isset($defaultPorts[$scheme]) && $defaultPorts[$scheme] === $port;
    }

    public function getEffectivePort(): int
    {
        $port = $this->getPort();
        if ($port !== null) {
            return $port;
        }
        
        $scheme = $this->getScheme();
        return match ($scheme) {
            'http' => 80,
            'https' => 443,
            'ftp' => 21,
            'ftps' => 990,
            'ssh' => 22,
            'telnet' => 23,
            default => 80,
        };
    }

    public function getBaseUrl(): self
    {
        return self::create(
            $this->getScheme() ?? 'http',
            $this->getHost() ?? 'localhost',
            $this->getPort()
        );
    }

    public function resolve(string $relativePath): self
    {
        if (str_starts_with($relativePath, 'http')) {
            return new self($relativePath);
        }
        
        $basePath = rtrim($this->getPath() ?? '', '/');
        $resolvedPath = $basePath . '/' . ltrim($relativePath, '/');
        
        return $this->withPath($resolvedPath);
    }

    public function normalize(): self
    {
        $url = $this->value;
        
        // Convert scheme to lowercase
        if ($this->getScheme()) {
            $url = strtolower($this->getScheme()) . substr($url, strlen($this->getScheme()));
        }
        
        // Convert host to lowercase
        if ($this->getHost()) {
            $url = str_replace($this->getHost(), strtolower($this->getHost()), $url);
        }
        
        // Remove default port
        if ($this->hasDefaultPort() && $this->getPort() !== null) {
            $url = str_replace(':' . $this->getPort(), '', $url);
        }
        
        // Remove trailing slash from path if it's just '/'
        if ($this->getPath() === '/') {
            $url = rtrim($url, '/');
        }
        
        return new self($url);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function toArray(): array
    {
        return [
            'url' => $this->value,
            'scheme' => $this->getScheme(),
            'host' => $this->getHost(),
            'port' => $this->getPort(),
            'path' => $this->getPath(),
            'query' => $this->getQuery(),
            'fragment' => $this->getFragment(),
            'user' => $this->getUser(),
            'pass' => $this->getPass(),
            'domain' => $this->getDomain(),
            'subdomain' => $this->getSubdomain(),
            'tld' => $this->getTld(),
            'query_parameters' => $this->getQueryParameters(),
            'is_secure' => $this->isSecure(),
            'is_http' => $this->isHttp(),
            'is_localhost' => $this->isLocalhost(),
            'is_private_network' => $this->isPrivateNetwork(),
            'is_absolute' => $this->isAbsolute(),
            'effective_port' => $this->getEffectivePort(),
        ];
    }

    private function buildFromComponents(array $components): self
    {
        $url = '';
        
        if (isset($components['scheme'])) {
            $url .= $components['scheme'] . '://';
        }
        
        if (isset($components['user'])) {
            $url .= $components['user'];
            if (isset($components['pass'])) {
                $url .= ':' . $components['pass'];
            }
            $url .= '@';
        }
        
        if (isset($components['host'])) {
            $url .= $components['host'];
        }
        
        if (isset($components['port'])) {
            $url .= ':' . $components['port'];
        }
        
        if (isset($components['path'])) {
            $url .= $components['path'];
        }
        
        if (isset($components['query'])) {
            $url .= '?' . $components['query'];
        }
        
        if (isset($components['fragment'])) {
            $url .= '#' . $components['fragment'];
        }
        
        return new self($url);
    }

    private function validate(string $url): void
    {
        if (empty($url)) {
            throw new \InvalidArgumentException('URL cannot be empty.');
        }
        
        if (strlen($url) > 2048) {
            throw new \InvalidArgumentException('URL is too long (max 2048 characters).');
        }
        
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('Invalid URL format.');
        }
    }
}