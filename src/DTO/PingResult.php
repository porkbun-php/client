<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;

final readonly class PingResult implements JsonSerializable
{
    public ?string $resolvedIp;

    public bool $hasIp;

    public bool $hasForwardedIp;

    public function __construct(
        public ?string $yourIp,
        public ?string $xForwardedFor,
    ) {
        $this->resolvedIp = $this->xForwardedFor ?? $this->yourIp;
        $this->hasIp = $this->yourIp !== null && $this->yourIp !== '';
        $this->hasForwardedIp = $this->xForwardedFor !== null && $this->xForwardedFor !== '';
    }

    public static function fromArray(array $data): self
    {
        return new self(
            yourIp: isset($data['yourIp']) ? (string) $data['yourIp'] : null,
            xForwardedFor: isset($data['xForwardedFor']) ? (string) $data['xForwardedFor'] : null,
        );
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
