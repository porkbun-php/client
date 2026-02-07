<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;

final readonly class CreateDnsRecordData implements JsonSerializable
{
    public function __construct(
        public int $id,
        public ?string $createdAt = null,
        public ?array $validationWarnings = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            createdAt: isset($data['createdAt']) ? (string) $data['createdAt'] : null,
            validationWarnings: isset($data['validationWarnings']) && is_array($data['validationWarnings'])
                ? $data['validationWarnings']
                : null,
        );
    }

    public function hasValidId(): bool
    {
        return $this->id > 0;
    }

    public function hasValidationWarnings(): bool
    {
        return $this->validationWarnings !== null && $this->validationWarnings !== [];
    }

    public function toArray(): array
    {
        $data = ['id' => $this->id];

        if ($this->createdAt !== null) {
            $data['createdAt'] = $this->createdAt;
        }
        if ($this->validationWarnings !== null) {
            $data['validationWarnings'] = $this->validationWarnings;
        }

        return $data;
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
