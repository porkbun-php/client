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

    /** @return list<string> */
    public function all(): array
    {
        return $this->nameservers;
    }

    public function first(): ?string
    {
        return $this->nameservers[0] ?? null;
    }

    public function last(): ?string
    {
        if ($this->nameservers === []) {
            return null;
        }

        return $this->nameservers[count($this->nameservers) - 1];
    }

    public function has(string $nameserver): bool
    {
        return in_array($nameserver, $this->nameservers, true);
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
