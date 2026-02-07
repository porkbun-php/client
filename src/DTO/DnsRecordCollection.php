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
    private array $records;

    public function __construct(array $records = [])
    {
        $this->records = array_values($records);
    }

    public static function fromArray(array $recordsData): self
    {
        $records = [];
        foreach ($recordsData as $recordData) {
            $records[] = DnsRecord::fromArray($recordData);
        }

        return new self($records);
    }

    public function getRecords(): array
    {
        return $this->records;
    }

    public function getRecordById(int|string $id): ?DnsRecord
    {
        $targetId = (int) $id;

        foreach ($this->records as $record) {
            if ($record->id === $targetId) {
                return $record;
            }
        }

        return null;
    }

    public function getRecordsByType(string $type): array
    {
        return array_values(array_filter($this->records, fn (DnsRecord $dnsRecord): bool => $dnsRecord->isType($type)));
    }

    public function getRecordsByName(string $name): array
    {
        return array_values(array_filter($this->records, fn (DnsRecord $dnsRecord): bool => $dnsRecord->name === $name));
    }

    public function getRootRecords(): array
    {
        return array_values(array_filter($this->records, fn (DnsRecord $dnsRecord): bool => $dnsRecord->isRootRecord()));
    }

    public function getRecordsByTypeAndName(string $type, string $name): array
    {
        return array_values(array_filter(
            $this->records,
            fn (DnsRecord $dnsRecord): bool => $dnsRecord->isType($type) && $dnsRecord->name === $name
        ));
    }

    public function first(): ?DnsRecord
    {
        return $this->records[0] ?? null;
    }

    public function firstOfType(string $type): ?DnsRecord
    {
        $matches = $this->getRecordsByType($type);

        return $matches[0] ?? null;
    }

    public function isEmpty(): bool
    {
        return $this->records === [];
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function filter(callable $callback): array
    {
        return array_values(array_filter($this->records, $callback));
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
