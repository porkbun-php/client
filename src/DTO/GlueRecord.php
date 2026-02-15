<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;

final readonly class GlueRecord implements JsonSerializable
{
    public function __construct(
        public string $host,
        public array $ips,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $ips = [];

        if (isset($data['ip'])) {
            $ips = is_array($data['ip']) ? $data['ip'] : [$data['ip']];
        } elseif (isset($data['ips'])) {
            $ips = is_array($data['ips']) ? $data['ips'] : [$data['ips']];
        }

        // Also check for numbered IP fields (ip1, ip2, etc.)
        $i = 1;
        while (isset($data["ip{$i}"])) {
            $ips[] = (string) $data["ip{$i}"];
            $i++;
        }

        return new self(
            host: (string) ($data['host'] ?? $data['subdomain'] ?? ''),
            ips: array_values(array_unique(array_filter($ips, static fn ($ip): bool => $ip !== '' && $ip !== null))),
        );
    }

    public function toArray(): array
    {
        return [
            'host' => $this->host,
            'ips' => $this->ips,
        ];
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function hasIpv4(): bool
    {
        return array_any($this->ips, fn ($ip): bool => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false);
    }

    public function hasIpv6(): bool
    {
        return array_any($this->ips, fn ($ip): bool => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false);
    }

    public function getIpv4Addresses(): array
    {
        return array_values(array_filter(
            $this->ips,
            static fn (string $ip): bool => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false
        ));
    }

    public function getIpv6Addresses(): array
    {
        return array_values(array_filter(
            $this->ips,
            static fn (string $ip): bool => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false
        ));
    }

    public function getIpCount(): int
    {
        return count($this->ips);
    }

    public function getFullHostname(string $domain): string
    {
        return "{$this->host}.{$domain}";
    }
}
