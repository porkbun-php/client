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
 * @implements IteratorAggregate<int, TransferRecord>
 */
final class TransferRecordCollection implements Countable, IteratorAggregate, JsonSerializable
{
    /** @var list<TransferRecord> */
    private array $transfers;

    /** @param array<TransferRecord> $transfers */
    public function __construct(array $transfers = [])
    {
        $this->transfers = array_values($transfers);
    }

    /** @param array<mixed> $transfersData */
    public static function fromArray(array $transfersData): self
    {
        return new self(array_map(
            TransferRecord::fromArray(...),
            array_filter($transfersData, is_array(...)),
        ));
    }

    /** @return list<TransferRecord> */
    public function items(): array
    {
        return $this->transfers;
    }

    public function first(): ?TransferRecord
    {
        return $this->transfers[0] ?? null;
    }

    public function last(): ?TransferRecord
    {
        return $this->transfers !== [] ? $this->transfers[array_key_last($this->transfers)] : null;
    }

    public function find(string $domainName): ?TransferRecord
    {
        return array_find(
            $this->transfers,
            fn (TransferRecord $transferRecord): bool => strcasecmp($transferRecord->domain, $domainName) === 0,
        );
    }

    public function has(string $domainName): bool
    {
        return $this->find($domainName) !== null;
    }

    public function filter(callable $callback): self
    {
        return new self(array_values(array_filter($this->transfers, $callback)));
    }

    public function isEmpty(): bool
    {
        return $this->transfers === [];
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    #[Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->transfers);
    }

    #[Override]
    public function count(): int
    {
        return count($this->transfers);
    }

    public function toArray(): array
    {
        return array_map(fn (TransferRecord $transferRecord): array => $transferRecord->toArray(), $this->transfers);
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
