<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;
use Porkbun\Enum\DnsRecordType;
use Porkbun\Exception\InvalidArgumentException;

final readonly class DnsRecord implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $name,
        public DnsRecordType $dnsRecordType,
        public string $content,
        public int $ttl,
        public int $priority,
        public ?string $notes = null
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
            dnsRecordType: $type,
            content: (string) ($data['content'] ?? ''),
            ttl: (int) ($data['ttl'] ?? 600),
            priority: (int) ($data['prio'] ?? 0),
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->dnsRecordType->value,
            'content' => $this->content,
            'ttl' => $this->ttl,
            'prio' => $this->priority,
            'notes' => $this->notes,
        ];
    }

    public function isRootRecord(): bool
    {
        return $this->name === '' || $this->name === '@';
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function isType(string|DnsRecordType $type): bool
    {
        if (is_string($type)) {
            $enumValue = DnsRecordType::tryFrom(mb_strtoupper($type));
            if (!$enumValue instanceof DnsRecordType) {
                return false;
            }

            return $this->dnsRecordType === $enumValue;
        }

        return $this->dnsRecordType === $type;
    }
}
