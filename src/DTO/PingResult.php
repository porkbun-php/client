<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;

final class PingResult implements JsonSerializable
{
    public ?string $resolvedIp { get => $this->forwardedIp ?? $this->yourIp; }

    public bool $hasIp { get => $this->yourIp !== null && $this->yourIp !== ''; }

    public bool $hasForwardedIp { get => $this->forwardedIp !== null && $this->forwardedIp !== ''; }

    public function __construct(
        public readonly ?string $yourIp,
        public readonly ?string $forwardedIp,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            yourIp: isset($data['yourIp']) ? (string) $data['yourIp'] : null,
            forwardedIp: isset($data['forwardedIp']) || isset($data['xForwardedFor'])
                ? (string) ($data['forwardedIp'] ?? $data['xForwardedFor'])
                : null,
        );
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->yourIp !== null) {
            $data['yourIp'] = $this->yourIp;
        }

        if ($this->forwardedIp !== null) {
            $data['forwardedIp'] = $this->forwardedIp;
        }

        return $data;
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
