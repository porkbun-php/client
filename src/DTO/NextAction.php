<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;
use Porkbun\Internal\TypeCaster;

final readonly class NextAction implements JsonSerializable
{
    public function __construct(
        public string $type,
        public ?string $hint = null,
        public ?string $url = null,
        public bool $retryable = false,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            type: (string) ($data['type'] ?? 'none'),
            hint: isset($data['hint']) ? (string) $data['hint'] : null,
            url: isset($data['url']) ? (string) $data['url'] : null,
            retryable: TypeCaster::toBool($data['retryable'] ?? false),
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'hint' => $this->hint,
            'url' => $this->url,
            'retryable' => $this->retryable,
        ];
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
