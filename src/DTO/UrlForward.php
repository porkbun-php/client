<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;

final readonly class UrlForward implements JsonSerializable
{
    public bool $isPermanent;

    public bool $isTemporary;

    public bool $isRootDomain;

    public function __construct(
        public int $id,
        public string $subdomain,
        public string $location,
        public string $type,
        public bool $includePath,
        public bool $wildcard,
    ) {
        $this->isPermanent = $this->type === 'permanent';
        $this->isTemporary = $this->type === 'temporary';
        $this->isRootDomain = $this->subdomain === '';
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            subdomain: (string) ($data['subdomain'] ?? ''),
            location: (string) ($data['location'] ?? ''),
            type: (string) ($data['type'] ?? ''),
            includePath: ($data['includePath'] ?? 'no') === 'yes',
            wildcard: ($data['wildcard'] ?? 'no') === 'yes',
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'subdomain' => $this->subdomain,
            'location' => $this->location,
            'type' => $this->type,
            'includePath' => $this->includePath ? 'yes' : 'no',
            'wildcard' => $this->wildcard ? 'yes' : 'no',
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
}
