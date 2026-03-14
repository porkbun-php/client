<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Override;
use Porkbun\Enum\DnsRecordType;
use Traversable;

/**
 * @implements IteratorAggregate<int, DnsRecord>
 */
final class DnsRecordCollection implements Countable, IteratorAggregate, JsonSerializable
{
    /** @var list<DnsRecord> */
    public readonly array $rootRecords;

    public readonly bool $isCloudflareEnabled;

    /** @var list<DnsRecord> */
    private array $records;

    public function __construct(
        array $records = [],
        public readonly ?string $cloudflare = null,
    ) {
        $this->records = array_values($records);
        $this->rootRecords = array_values(array_filter($this->records, fn (DnsRecord $dnsRecord): bool => $dnsRecord->isRootRecord));
        $this->isCloudflareEnabled = $this->cloudflare === 'enabled';
    }

    /** @param array{records?: array<array<string, mixed>>, cloudflare?: string}|list<array<string, mixed>> $data */
    public static function fromArray(array $data): self
    {
        if (isset($data['records']) || isset($data['cloudflare'])) {
            /** @var array<array<string, mixed>> $recordsData */
            $recordsData = $data['records'] ?? [];
            /** @var ?string $cloudflare */
            $cloudflare = $data['cloudflare'] ?? null;
        } else {
            /** @var list<array<string, mixed>> $recordsData */
            $recordsData = $data;
            $cloudflare = null;
        }

        return new self(
            array_map(DnsRecord::fromArray(...), $recordsData),
            $cloudflare,
        );
    }

    /** @return list<DnsRecord> */
    public function items(): array
    {
        return $this->records;
    }

    public function find(int $id): ?DnsRecord
    {
        return array_find($this->records, fn (DnsRecord $dnsRecord): bool => $dnsRecord->id === $id);
    }

    public function byType(string|DnsRecordType $type): self
    {
        $resolved = DnsRecordType::resolve($type);

        return new self(array_values(array_filter($this->records, fn (DnsRecord $dnsRecord): bool => $dnsRecord->type === $resolved)));
    }

    public function byName(string $name): self
    {
        return new self(array_values(array_filter($this->records, fn (DnsRecord $dnsRecord): bool => $dnsRecord->name === $name)));
    }

    public function byTypeAndName(string|DnsRecordType $type, string $name): self
    {
        $resolved = DnsRecordType::resolve($type);

        return new self(array_values(array_filter(
            $this->records,
            fn (DnsRecord $dnsRecord): bool => $dnsRecord->type === $resolved && $dnsRecord->name === $name
        )));
    }

    public function first(): ?DnsRecord
    {
        return $this->records[0] ?? null;
    }

    public function last(): ?DnsRecord
    {
        return $this->records !== [] ? $this->records[array_key_last($this->records)] : null;
    }

    public function isEmpty(): bool
    {
        return $this->records === [];
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function filter(callable $callback): self
    {
        return new self(array_values(array_filter($this->records, $callback)));
    }

    #[Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->records);
    }

    #[Override]
    public function count(): int
    {
        return count($this->records);
    }

    public function has(int $id): bool
    {
        return $this->find($id) !== null;
    }

    public function toArray(): array
    {
        $records = array_map(fn (DnsRecord $dnsRecord): array => $dnsRecord->toArray(), $this->records);

        if ($this->cloudflare !== null) {
            return [
                'records' => $records,
                'cloudflare' => $this->cloudflare,
            ];
        }

        return $records;
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
