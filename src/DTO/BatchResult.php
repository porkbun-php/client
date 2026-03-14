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
 * @implements IteratorAggregate<int, BatchOperationResult>
 */
final readonly class BatchResult implements Countable, IteratorAggregate, JsonSerializable
{
    /** @param list<BatchOperationResult> $results */
    public function __construct(
        private array $results = [],
    ) {
    }

    /** @return list<BatchOperationResult> */
    public function items(): array
    {
        return $this->results;
    }

    public function first(): ?BatchOperationResult
    {
        return $this->results[0] ?? null;
    }

    public function last(): ?BatchOperationResult
    {
        return $this->results !== [] ? $this->results[array_key_last($this->results)] : null;
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /** @return list<BatchOperationResult> */
    public function successes(): array
    {
        return array_values(array_filter($this->results, fn (BatchOperationResult $batchOperationResult): bool => $batchOperationResult->success));
    }

    /** @return list<BatchOperationResult> */
    public function failures(): array
    {
        return array_values(array_filter($this->results, fn (BatchOperationResult $batchOperationResult): bool => $batchOperationResult->isFailure));
    }

    public function hasFailures(): bool
    {
        return $this->failures() !== [];
    }

    public function isEmpty(): bool
    {
        return $this->results === [];
    }

    #[Override]
    public function count(): int
    {
        return count($this->results);
    }

    #[Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->results);
    }

    /** @return list<array<string, mixed>> */
    public function toArray(): array
    {
        return array_map(fn (BatchOperationResult $batchOperationResult): array => $batchOperationResult->toArray(), $this->results);
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
