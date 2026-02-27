<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;

final readonly class CreateResult implements JsonSerializable
{
    public bool $hasValidId;

    public bool $hasValidationWarnings;

    public function __construct(
        public int $id,
        public ?string $createdAt = null,
        /** @var ?list<string> */
        public ?array $validationWarnings = null,
    ) {
        $this->hasValidId = $this->id > 0;
        $this->hasValidationWarnings = $this->validationWarnings !== null && $this->validationWarnings !== [];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            createdAt: isset($data['createdAt']) ? (string) $data['createdAt'] : null,
            validationWarnings: isset($data['validationWarnings']) && is_array($data['validationWarnings'])
                ? array_values(array_map(strval(...), $data['validationWarnings']))
                : null,
        );
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
