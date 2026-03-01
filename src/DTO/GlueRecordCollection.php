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
 * @implements IteratorAggregate<int, GlueRecord>
 */
final class GlueRecordCollection implements Countable, IteratorAggregate, JsonSerializable
{
    /** @var list<GlueRecord> */
    private array $records;

    /** @param array<GlueRecord> $records */
    public function __construct(array $records = [])
    {
        $this->records = array_values($records);
    }

    /** @param array<array<string, mixed>> $recordsData */
    public static function fromArray(array $recordsData): self
    {
        $records = [];
        foreach ($recordsData as $recordData) {
            $records[] = GlueRecord::fromArray($recordData);
        }

        return new self($records);
    }

    /** @return list<GlueRecord> */
    public function all(): array
    {
        return $this->records;
    }

    public function first(): ?GlueRecord
    {
        return $this->records[0] ?? null;
    }

    public function last(): ?GlueRecord
    {
        if ($this->records === []) {
            return null;
        }

        return $this->records[count($this->records) - 1];
    }

    public function find(string $host): ?GlueRecord
    {
        foreach ($this->records as $record) {
            if ($record->host === $host) {
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
        return array_map(fn (GlueRecord $glueRecord): array => $glueRecord->toArray(), $this->records);
    }

    public function toArray(): array
    {
        return $this->jsonSerialize();
    }
}
