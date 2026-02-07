<?php

declare(strict_types=1);

namespace Porkbun\DTO;

final readonly class DomainLabel
{
    public function __construct(
        public string $id,
        public string $title,
        public string $color,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            title: (string) ($data['title'] ?? ''),
            color: (string) ($data['color'] ?? ''),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'color' => $this->color,
        ];
    }
}
