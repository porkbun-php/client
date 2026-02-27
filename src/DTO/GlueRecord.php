<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;

final readonly class GlueRecord implements JsonSerializable
{
    /** @var list<string> */
    public array $ipv4Addresses;

    /** @var list<string> */
    public array $ipv6Addresses;

    public bool $hasIpv4;

    public bool $hasIpv6;

    public int $ipCount;

    public function __construct(
        public string $host,
        public array $ips,
    ) {
        $this->ipv4Addresses = array_values(array_filter(
            $this->ips,
            static fn (string $ip): bool => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false
        ));
        $this->ipv6Addresses = array_values(array_filter(
            $this->ips,
            static fn (string $ip): bool => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false
        ));
        $this->hasIpv4 = $this->ipv4Addresses !== [];
        $this->hasIpv6 = $this->ipv6Addresses !== [];
        $this->ipCount = count($this->ips);
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
