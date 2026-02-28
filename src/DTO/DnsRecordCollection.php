<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Override;
use Traversable;

/**
 * @implements IteratorAggregate<int, DnsRecord>
 */
final class DnsRecordCollection implements Countable, IteratorAggregate, JsonSerializable
{
    /** @var list<DnsRecord> */
    public readonly array $rootRecords;

    /** @var list<DnsRecord> */
    private array $records;

    public function __construct(array $records = [])
    {
        $this->records = array_values($records);
        $this->rootRecords = array_values(array_filter($this->records, fn (DnsRecord $dnsRecord): bool => $dnsRecord->isRootRecord));
    }

    public static function fromArray(array $recordsData): self
    {
        $records = [];
        foreach ($recordsData as $recordData) {
            $records[] = DnsRecord::fromArray($recordData);
        }

        return new self($records);
    }

    /** @return list<DnsRecord> */
    public function all(): array
    {
        return $this->records;
    }

    public function find(int $id): ?DnsRecord
    {
        foreach ($this->records as $record) {
            if ($record->id === $id) {
                return $record;
            }
        }

        return null;
    }

    public function byType(string $type): self
    {
        return new self(array_values(array_filter($this->records, fn (DnsRecord $dnsRecord): bool => $dnsRecord->isType($type))));
    }

    public function byName(string $name): self
    {
        return new self(array_values(array_filter($this->records, fn (DnsRecord $dnsRecord): bool => $dnsRecord->name === $name)));
    }

    public function byTypeAndName(string $type, string $name): self
    {
        return new self(array_values(array_filter(
            $this->records,
            fn (DnsRecord $dnsRecord): bool => $dnsRecord->isType($type) && $dnsRecord->name === $name
        )));
    }

    public function first(): ?DnsRecord
    {
        return $this->records[0] ?? null;
    }

    public function last(): ?DnsRecord
    {
        if ($this->records === []) {
            return null;
        }

        return $this->records[count($this->records) - 1];
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

    #[Override]
    public function jsonSerialize(): array
    {
        return array_map(fn (DnsRecord $dnsRecord): array => $dnsRecord->toArray(), $this->records);
    }

    public function toArray(): array
    {
        return $this->jsonSerialize();
    }
}
