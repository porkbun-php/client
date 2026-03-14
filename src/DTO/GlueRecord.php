<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;

final class GlueRecord implements JsonSerializable
{
    /** @var list<string> */
    public array $ipv4Addresses {
        get => array_values(array_filter(
            $this->ips,
            static fn (string $ip): bool => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false
        ));
    }

    /** @var list<string> */
    public array $ipv6Addresses {
        get => array_values(array_filter(
            $this->ips,
            static fn (string $ip): bool => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false
        ));
    }

    public bool $hasIpv4 { get => $this->ipv4Addresses !== []; }

    public bool $hasIpv6 { get => $this->ipv6Addresses !== []; }

    public int $ipCount { get => count($this->ips); }

    public function __construct(
        public readonly string $host,
        /** @var list<string> */
        public readonly array $ips,
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

    public function fullHostname(string $domain): string
    {
        if ($this->host === '') {
            return $domain;
        }

        return "{$this->host}.{$domain}";
    }
}
