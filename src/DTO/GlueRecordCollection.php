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
        return new self(array_map(GlueRecord::fromArray(...), $recordsData));
    }

    /** @return list<GlueRecord> */
    public function items(): array
    {
        return $this->records;
    }

    public function first(): ?GlueRecord
    {
        return $this->records[0] ?? null;
    }

    public function last(): ?GlueRecord
    {
        return $this->records !== [] ? $this->records[array_key_last($this->records)] : null;
    }

    public function find(string $host): ?GlueRecord
    {
        $normalizedHost = mb_strtolower($host);

        return array_find($this->records, fn (GlueRecord $glueRecord): bool => mb_strtolower($glueRecord->host) === $normalizedHost);
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

    public function has(string $host): bool
    {
        return $this->find($host) !== null;
    }

    public function toArray(): array
    {
        return array_map(fn (GlueRecord $glueRecord): array => $glueRecord->toArray(), $this->records);
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
