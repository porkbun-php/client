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
        $records = [];
        foreach ($recordsData as $recordData) {
            $records[] = DnssecRecord::fromArray($recordData);
        }

        return new self($records);
    }

    /** @return list<DnssecRecord> */
    public function all(): array
    {
        return $this->records;
    }

    public function first(): ?DnssecRecord
    {
        return $this->records[0] ?? null;
    }

    public function last(): ?DnssecRecord
    {
        if ($this->records === []) {
            return null;
        }

        return $this->records[count($this->records) - 1];
    }

    public function find(int $keyTag): ?DnssecRecord
    {
        foreach ($this->records as $record) {
            if ($record->keyTag === $keyTag) {
                return $record;
            }
        }

        return null;
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

    #[Override]
    public function jsonSerialize(): array
    {
        return array_map(fn (DnssecRecord $dnssecRecord): array => $dnssecRecord->toArray(), $this->records);
    }

    public function toArray(): array
    {
        return $this->jsonSerialize();
    }
}
