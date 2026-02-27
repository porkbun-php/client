<?php

declare(strict_types=1);

namespace Porkbun\Enum;

enum Endpoint: string
{
    case DEFAULT = 'https://api.porkbun.com/api/json/v3';
    case IPV4 = 'https://api-ipv4.porkbun.com/api/json/v3';

    public static function fromUrl(string $url): ?self
    {
        $normalizedUrl = mb_rtrim($url, '/');

        return array_find(self::cases(), fn (self $endpoint): bool => mb_rtrim($endpoint->value, '/') === $normalizedUrl);
    }

    public static function fromType(string $type): ?self
    {
        return match (mb_strtolower($type)) {
            'default' => self::DEFAULT,
            'ipv4' => self::IPV4,
            default => null,
        };
    }

    public static function all(): array
    {
        $endpoints = [];
        foreach (self::cases() as $endpoint) {
            $endpoints[$endpoint->type()] = $endpoint->value;
        }

        return $endpoints;
    }

    public static function getDefault(): self
    {
        return self::DEFAULT;
    }

    public static function isKnownUrl(string $url): bool
    {
        return self::fromUrl($url) instanceof Endpoint;
    }

    public static function typeFromUrl(string $url): string
    {
        $endpoint = self::fromUrl($url);

        return $endpoint?->type() ?? 'custom';
    }

    public function url(): string
    {
        return $this->value;
    }

    public function type(): string
    {
        return match ($this) {
            self::DEFAULT => 'default',
            self::IPV4 => 'ipv4',
        };
    }
}
