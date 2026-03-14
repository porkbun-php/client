<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;
use Porkbun\Enum\UrlForwardType;
use Porkbun\Exception\InvalidArgumentException;
use Porkbun\Internal\TypeCaster;

final class UrlForward implements JsonSerializable
{
    public bool $isPermanent { get => $this->type === UrlForwardType::PERMANENT; }

    public bool $isTemporary { get => $this->type === UrlForwardType::TEMPORARY; }

    public bool $isRootDomain { get => $this->subdomain === ''; }

    public function __construct(
        public readonly int $id,
        public readonly string $subdomain,
        public readonly string $location,
        public readonly UrlForwardType $type,
        public readonly bool $includePath,
        public readonly bool $wildcard,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            subdomain: (string) ($data['subdomain'] ?? ''),
            location: (string) ($data['location'] ?? ''),
            type: self::parseType((string) ($data['type'] ?? 'temporary')),
            includePath: TypeCaster::toBool($data['includePath'] ?? false),
            wildcard: TypeCaster::toBool($data['wildcard'] ?? false),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'subdomain' => $this->subdomain,
            'location' => $this->location,
            'type' => $this->type->value,
            'includePath' => $this->includePath,
            'wildcard' => $this->wildcard,
        ];
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function fullUrl(string $domain): string
    {
        if ($this->isRootDomain) {
            return $domain;
        }

        return "{$this->subdomain}.{$domain}";
    }

    private static function parseType(string $type): UrlForwardType
    {
        $result = UrlForwardType::tryFrom($type);

        if (!$result instanceof UrlForwardType) {
            throw new InvalidArgumentException("Unknown URL forward type: '{$type}'");
        }

        return $result;
    }
}
