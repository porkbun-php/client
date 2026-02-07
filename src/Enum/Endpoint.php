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

        foreach (self::cases() as $endpoint) {
            if (mb_rtrim($endpoint->value, '/') === $normalizedUrl) {
                return $endpoint;
            }
        }

        return null;
    }

    public static function fromType(string $type): ?self
    {
        return match (mb_strtolower($type)) {
            'default' => self::DEFAULT,
            'ipv4' => self::IPV4,
            default => null,
        };
    }

    public static function getAll(): array
    {
        $endpoints = [];
        foreach (self::cases() as $endpoint) {
            $endpoints[$endpoint->getType()] = $endpoint->value;
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

    public static function getTypeFromUrl(string $url): string
    {
        $endpoint = self::fromUrl($url);

        return $endpoint?->getType() ?? 'custom';
    }

    public function getUrl(): string
    {
        return $this->value;
    }

    public function getType(): string
    {
        return match ($this) {
            self::DEFAULT => 'default',
            self::IPV4 => 'ipv4',
        };
    }
}
