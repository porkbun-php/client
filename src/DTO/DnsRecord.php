<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;
use Porkbun\Enum\DnsRecordType;
use Porkbun\Exception\InvalidArgumentException;

final class DnsRecord implements JsonSerializable
{
    public bool $isRootRecord { get => $this->name === '' || $this->name === '@'; }

    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly DnsRecordType $type,
        public readonly string $content,
        public readonly int $ttl,
        public readonly int $priority,
        public readonly ?string $notes = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        $typeString = (string) ($data['type'] ?? '');
        $type = DnsRecordType::tryFrom($typeString);

        if (!$type instanceof DnsRecordType) {
            throw new InvalidArgumentException("Unknown DNS record type: '{$typeString}'");
        }

        return new self(
            id: (int) ($data['id'] ?? 0),
            name: (string) ($data['name'] ?? ''),
            type: $type,
            content: (string) ($data['content'] ?? ''),
            ttl: (int) ($data['ttl'] ?? 600),
            priority: (int) ($data['priority'] ?? $data['prio'] ?? 0),
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->value,
            'content' => $this->content,
            'ttl' => $this->ttl,
            'priority' => $this->priority,
        ];

        if ($this->notes !== null) {
            $data['notes'] = $this->notes;
        }

        return $data;
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function isType(string|DnsRecordType $type): bool
    {
        return $this->type === DnsRecordType::resolve($type);
    }
}
