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
 * @implements IteratorAggregate<int, string>
 */
final class NameserverCollection implements Countable, IteratorAggregate, JsonSerializable
{
    /** @var list<string> */
    private array $nameservers;

    /** @param array<string> $nameservers */
    public function __construct(array $nameservers = [])
    {
        $this->nameservers = array_values($nameservers);
    }

    /** @param array<string> $data */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /** @return list<string> */
    public function items(): array
    {
        return $this->nameservers;
    }

    public function first(): ?string
    {
        return $this->nameservers[0] ?? null;
    }

    public function last(): ?string
    {
        return $this->nameservers !== [] ? $this->nameservers[array_key_last($this->nameservers)] : null;
    }

    public function find(string $nameserver): ?string
    {
        return array_find($this->nameservers, fn (string $ns): bool => strcasecmp($ns, $nameserver) === 0);
    }

    public function has(string $nameserver): bool
    {
        return $this->find($nameserver) !== null;
    }

    public function filter(callable $callback): self
    {
        return new self(array_values(array_filter($this->nameservers, $callback)));
    }

    public function isEmpty(): bool
    {
        return $this->nameservers === [];
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    #[Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->nameservers);
    }

    #[Override]
    public function count(): int
    {
        return count($this->nameservers);
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->nameservers;
    }

    public function toArray(): array
    {
        return $this->nameservers;
    }
}
