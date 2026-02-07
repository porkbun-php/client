<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;

final readonly class PingData implements JsonSerializable
{
    public function __construct(
        public ?string $yourIp,
        public ?string $xForwardedFor,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            yourIp: $data['yourIp'] ?? null,
            xForwardedFor: $data['xForwardedFor'] ?? null,
        );
    }

    public function ip(): ?string
    {
        return $this->xForwardedFor ?? $this->yourIp;
    }

    public function hasIp(): bool
    {
        return $this->yourIp !== null && $this->yourIp !== '';
    }

    public function hasForwardedIp(): bool
    {
        return $this->xForwardedFor !== null;
    }

    public function toArray(): array
    {
        return [
            'yourIp' => $this->yourIp,
            'xForwardedFor' => $this->xForwardedFor,
        ];
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
