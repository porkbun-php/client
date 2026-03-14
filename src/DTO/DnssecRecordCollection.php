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
 * @implements IteratorAggregate<int, DnssecRecord>
 */
final class DnssecRecordCollection implements Countable, IteratorAggregate, JsonSerializable
{
    /** @var list<DnssecRecord> */
    private array $records;

    /** @param array<DnssecRecord> $records */
    public function __construct(array $records = [])
    {
        $this->records = array_values($records);
    }

    /** @param array<array<string, mixed>> $recordsData */
    public static function fromArray(array $recordsData): self
    {
        return new self(array_map(DnssecRecord::fromArray(...), $recordsData));
    }

    /** @return list<DnssecRecord> */
    public function items(): array
    {
        return $this->records;
    }

    public function first(): ?DnssecRecord
    {
        return $this->records[0] ?? null;
    }

    public function last(): ?DnssecRecord
    {
        return $this->records !== [] ? $this->records[array_key_last($this->records)] : null;
    }

    public function find(int $keyTag): ?DnssecRecord
    {
        return array_find($this->records, fn (DnssecRecord $dnssecRecord): bool => $dnssecRecord->keyTag === $keyTag);
    }

    public function filter(callable $callback): self
    {
        return new self(array_values(array_filter($this->records, $callback)));
    }

    public function isEmpty(): bool
    {
        return $this->records === [];
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
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

    public function has(int $keyTag): bool
    {
        return $this->find($keyTag) !== null;
    }

    public function toArray(): array
    {
        return array_map(fn (DnssecRecord $dnssecRecord): array => $dnssecRecord->toArray(), $this->records);
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
