<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use DateTimeImmutable;
use Exception;
use JsonSerializable;
use Override;

final class CreateResult implements JsonSerializable
{
    public bool $hasValidId { get => $this->id > 0; }

    public bool $hasValidationWarnings { get => $this->validationWarnings !== null && $this->validationWarnings !== []; }

    public function __construct(
        public readonly int $id,
        public readonly ?DateTimeImmutable $createdAt = null,
        /** @var ?list<string> */
        public readonly ?array $validationWarnings = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $createdAt = null;
        if (isset($data['createdAt'])) {
            if ($data['createdAt'] instanceof DateTimeImmutable) {
                $createdAt = $data['createdAt'];
            } else {
                try {
                    $createdAt = new DateTimeImmutable((string) $data['createdAt']);
                } catch (Exception) {
                    // Invalid date format — leave as null
                }
            }
        }

        return new self(
            id: (int) ($data['id'] ?? 0),
            createdAt: $createdAt,
            validationWarnings: isset($data['validationWarnings']) && is_array($data['validationWarnings'])
                ? array_values(array_map(strval(...), $data['validationWarnings']))
                : null,
        );
    }

    public function toArray(): array
    {
        $data = ['id' => $this->id];

        if ($this->createdAt instanceof DateTimeImmutable) {
            $data['createdAt'] = $this->createdAt->format('c');
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
